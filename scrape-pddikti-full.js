const BASE = 'https://pddikti.fastapicloud.dev/api';
const fs = require('fs');
const path = require('path');
const OUTPUT_DIR = path.join(__dirname);
const PROGRESS_FILE = path.join(OUTPUT_DIR, 'scrape_progress.json');
const OUTPUT_FILE = path.join(OUTPUT_DIR, 'pddikti_full_data.json');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function fetchJSON(url, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url);
      if (res.status === 429) {
        console.log('  [RATE LIMIT] Menunggu 15s...');
        await sleep(15000);
        continue;
      }
      if (res.status === 503) {
        await sleep(8000);
        continue;
      }
      if (!res.ok) {
        if (i < retries - 1) { await sleep(2000); continue; }
        return null;
      }
      return await res.json();
    } catch (e) {
      if (i < retries - 1) { await sleep(3000); continue; }
      return null;
    }
  }
  return null;
}

async function discoverAllPTs() {
  console.log('\n=== TAHAP 1: DISCOVERY SEMUA PERGURUAN TINGGI ===\n');

  const ptMap = new Map();

  const searchTerms = [
    'universitas', 'institut', 'sekolah tinggi', 'politeknik', 'akademi',
    'stai', 'stie', 'stik', 'stis', 'stit', 'stmi', 'stt', 'stpn',
    'stain', 'stikes', 'stkip', 'sttk', 'sttn', 'sttal', 'sttd',
    'uin', 'iain', 'its', 'itb', 'ui', 'ugm', 'unair', 'unej',
    'uns', 'undip', 'unpad', 'uin', 'uny', 'unesa', 'unsoed',
    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
    'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    '1', '2', '3', '4', '5', '6', '7', '8', '9',
    'nasional', 'negeri', 'swasta', 'islam', 'kristen', 'katolik',
    'indonesia', 'malang', 'surabaya', 'jakarta', 'bandung', 'yogyakarta',
    'medan', 'makassar', 'semarang', 'palembang', 'manado', 'denpasar',
    'lampung', 'pontianak', 'banjarmasin', 'pekanbaru', 'padang',
  ];

  for (let i = 0; i < searchTerms.length; i++) {
    const term = searchTerms[i];
    process.stdout.write(`  [${i + 1}/${searchTerms.length}] Searching "${term}"... `);

    const data = await fetchJSON(`${BASE}/search/pt/${encodeURIComponent(term)}/`);
    if (data && Array.isArray(data)) {
      let newCount = 0;
      for (const pt of data) {
        if (!ptMap.has(pt.id)) {
          ptMap.set(pt.id, {
            id: pt.id,
            kode_pt: (pt.kode || '').trim(),
            nama_pt: pt.nama || '',
            nama_singkat: pt.nama_singkat || '',
          });
          newCount++;
        }
      }
      console.log(`+${newCount} baru (total: ${ptMap.size})`);
    } else {
      console.log('tidak ada hasil');
    }
    await sleep(300);
  }

  console.log(`\n  Total PT ditemukan: ${ptMap.size}`);
  return ptMap;
}

async function scrapeDetailAndProdi(ptMap) {
  console.log('\n=== TAHAP 2: SCRAPING DETAIL & PRODI ===\n');

  let completed = {};
  try {
    completed = JSON.parse(fs.readFileSync(PROGRESS_FILE, 'utf-8'));
  } catch {}

  const doneSet = new Set(Object.keys(completed));
  const allIds = [...ptMap.keys()];
  const remaining = allIds.filter((id) => !doneSet.has(id));

  console.log(`  Total: ${allIds.length} | Sudah: ${doneSet.size} | Sisa: ${remaining.length}\n`);

  const CONCURRENT = 5;
  const SAVE_EVERY = 50;

  for (let i = 0; i < remaining.length; i += CONCURRENT) {
    const batch = remaining.slice(i, i + CONCURRENT);
    const count = doneSet.size + batch.length;

    const promises = batch.map(async (id) => {
      const pt = ptMap.get(id);
      const name = (pt.nama_pt || '').substring(0, 45);

      try {
        const [detail, prodiList] = await Promise.all([
          fetchJSON(`${BASE}/pt/detail/${encodeURIComponent(id)}/`),
          fetchJSON(`${BASE}/pt/prodi/${encodeURIComponent(id)}/20241`),
        ]);

        const prodis = [];
        if (Array.isArray(prodiList)) {
          for (const p of prodiList) {
            prodis.push({
              id_prodi: p.id_sms || '',
              kode_prodi: (p.kode_prodi || '').trim(),
              nama_prodi: p.nama_prodi || '',
              jenjang: p.jenjang_prodi || p.jenj_didik || '',
              akreditasi: p.akreditasi || '',
              status: p.status_prodi || p.status || '',
              jumlah_mahasiswa: p.jumlah_mahasiswa || 0,
              jumlah_dosen: p.jumlah_dosen || 0,
            });
          }
        }

        const result = {
          id_pt: id,
          kode_pt: detail ? (detail.kode_pt || pt.kode_pt || '').trim() : pt.kode_pt,
          nama_pt: detail ? (detail.nama_pt || pt.nama_pt) : pt.nama_pt,
          nama_singkat: detail ? (detail.nm_singkat || pt.nama_singkat) : pt.nama_singkat,
          kelompok: (detail && detail.kelompok) || '',
          pembina: (detail && detail.pembina) || '',
          status_pt: (detail && detail.status_pt) || '',
          akreditasi: (detail && detail.akreditasi_pt) || '',
          alamat: (detail && detail.alamat) || '',
          provinsi: (detail && detail.provinsi_pt) || '',
          kab_kota: (detail && detail.kab_kota_pt) || '',
          kecamatan: (detail && detail.kecamatan_pt) || '',
          kode_pos: (detail && detail.kode_pos) || '',
          website: (detail && detail.website) || '',
          email: (detail && detail.email) || '',
          telepon: (detail && detail.no_tel) || '',
          tanggal_berdiri: (detail && detail.tgl_berdiri_pt) || '',
          prodi: prodis,
        };

        const prodiCount = prodis.length;
        const akred = (detail && detail.akreditasi_pt) || '-';
        console.log(`  [${Object.keys(completed).length + 1}/${allIds.length}] ${name}... OK (${prodiCount} prodi, akred: ${akred})`);
        return { id, result };
      } catch (e) {
        console.log(`  [${Object.keys(completed).length + 1}/${allIds.length}] ${name}... ERROR: ${e.message}`);
        return {
          id,
          result: {
            id_pt: id,
            kode_pt: pt.kode_pt,
            nama_pt: pt.nama_pt,
            nama_singkat: pt.nama_singkat,
            kelompok: '', pembina: '', status_pt: '', akreditasi: '',
            alamat: '', provinsi: '', kab_kota: '', kecamatan: '',
            kode_pos: '', website: '', email: '', telepon: '',
            tanggal_berdiri: '',
            prodi: [],
          },
        };
      }
    });

    const results = await Promise.all(promises);
    for (const { id, result } of results) {
      completed[id] = result;
    }

    if ((doneSet.size + i) % SAVE_EVERY < CONCURRENT) {
      fs.writeFileSync(PROGRESS_FILE, JSON.stringify(completed));
      console.log(`  -- Progress disimpan (${Object.keys(completed).length}/${allIds.length}) --`);
    }

    await sleep(200);
  }

  fs.writeFileSync(PROGRESS_FILE, JSON.stringify(completed));
  return completed;
}

function generateFinalOutput(completed) {
  console.log('\n=== TAHAP 3: GENERATE FILE JSON FINAL ===\n');

  const results = Object.values(completed);

  const validPTs = results.filter((pt) => pt.kode_pt && pt.kode_pt !== '0' && pt.nama_pt && pt.nama_pt !== 'N/A');

  const totalProdi = validPTs.reduce((s, p) => s + p.prodi.length, 0);

  const summary = {
    total_perguruan_tinggi: validPTs.length,
    total_prodi: totalProdi,
    generated_at: new Date().toISOString(),
    semester: '20241',
    source: 'PDDikti API (pddikti.kemdiktisaintek.go.id)',
    api_proxy: 'pddikti.fastapicloud.dev',
  };

  const output = { summary, data: validPTs };

  fs.writeFileSync(OUTPUT_FILE, JSON.stringify(output, null, 2));
  console.log(`  File: ${OUTPUT_FILE}`);
  console.log(`  Total PT: ${validPTs.length}`);
  console.log(`  Total Prodi: ${totalProdi}`);

  const byProvinsi = {};
  const byAkreditasi = {};
  const byKelompok = {};
  const byJenjangProdi = {};

  for (const pt of validPTs) {
    const prov = pt.provinsi || 'Tidak Diketahui';
    byProvinsi[prov] = (byProvinsi[prov] || 0) + 1;

    const ak = pt.akreditasi || 'Tidak Terakreditasi';
    byAkreditasi[ak] = (byAkreditasi[ak] || 0) + 1;

    const kel = pt.kelompok || 'Tidak Diketahui';
    byKelompok[kel] = (byKelompok[kel] || 0) + 1;

    for (const prodi of pt.prodi) {
      const jenjang = prodi.jenjang || 'N/A';
      byJenjangProdi[jenjang] = (byJenjangProdi[jenjang] || 0) + 1;
    }
  }

  console.log('\n  Statistik per Provinsi (top 10):');
  Object.entries(byProvinsi)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
    .forEach(([k, v]) => console.log(`    ${k}: ${v}`));

  console.log('\n  Statistik per Akreditasi PT:');
  Object.entries(byAkreditasi)
    .sort((a, b) => b[1] - a[1])
    .forEach(([k, v]) => console.log(`    ${k}: ${v}`));

  console.log('\n  Statistik per Kelompok PT:');
  Object.entries(byKelompok)
    .sort((a, b) => b[1] - a[1])
    .forEach(([k, v]) => console.log(`    ${k}: ${v}`));

  console.log('\n  Statistik per Jenjang Prodi:');
  Object.entries(byJenjangProdi)
    .sort((a, b) => b[1] - a[1])
    .forEach(([k, v]) => console.log(`    ${k}: ${v}`));

  return output;
}

async function main() {
  console.log('╔══════════════════════════════════════════════╗');
  console.log('║   PDDikti Full Data Scraper                  ║');
  console.log('║   Universitas + Prodi + Akreditasi + Alamat  ║');
  console.log('╚══════════════════════════════════════════════╝');
  console.log(`  Mulai: ${new Date().toLocaleString()}`);

  const ptMap = await discoverAllPTs();

  const completed = await scrapeDetailAndProdi(ptMap);

  const output = generateFinalOutput(completed);

  console.log(`\n  Selesai: ${new Date().toLocaleString()}`);
  console.log(`  Output: ${OUTPUT_FILE}`);
  console.log(`  Ukuran: ${(fs.statSync(OUTPUT_FILE).size / 1024 / 1024).toFixed(1)} MB`);
}

main().catch(console.error);
