const BASE = 'https://pddikti.fastapicloud.dev/api';
const fs = require('fs');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function fetchJSON(url, retries = 2) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url);
      if (res.status === 503) {
        await sleep(8000);
        continue;
      }
      if (!res.ok) return null;
      return await res.json();
    } catch (e) {
      await sleep(3000);
    }
  }
  return null;
}

async function main() {
  const discovered = JSON.parse(fs.readFileSync('pts_discovered.json', 'utf-8'));
  const ptIds = Object.keys(discovered);

  let completed = {};
  try {
    completed = JSON.parse(fs.readFileSync('pts_completed.json', 'utf-8'));
  } catch {}

  const doneSet = new Set(Object.keys(completed));
  const remaining = ptIds.filter((id) => !doneSet.has(id));

  console.log('Total PTs:', ptIds.length, '| Done:', doneSet.size, '| Remaining:', remaining.length);

  const BATCH_SIZE = 50;
  for (let b = 0; b < remaining.length; b += BATCH_SIZE) {
    const batch = remaining.slice(b, b + BATCH_SIZE);

    for (let i = 0; i < batch.length; i++) {
      const id = batch[i];
      const pt = discovered[id];
      const count = Object.keys(completed).length + 1;
      process.stdout.write('[' + count + '/' + ptIds.length + '] ' + (pt.nama_pt || '').substring(0, 50) + '...');

      try {
        const [detail, prodiList] = await Promise.all([
          fetchJSON(BASE + '/pt/detail/' + encodeURIComponent(id) + '/'),
          fetchJSON(BASE + '/pt/prodi/' + encodeURIComponent(id) + '/20241'),
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

        completed[id] = {
          id: id,
          kode_pt: detail ? (detail.kode_pt || pt.kode_pt || '').trim() : pt.kode_pt,
          nama_pt: detail ? (detail.nama_pt || pt.nama_pt) : pt.nama_pt,
          nama_singkat: detail ? (detail.nm_singkat || pt.nama_singkat) : pt.nama_singkat,
          kelompok: (detail && detail.kelompok) || '',
          pembina: (detail && detail.pembina) || '',
          status_pt: (detail && detail.status_pt) || '',
          akreditasi_pt: (detail && detail.akreditasi_pt) || '',
          alamat: (detail && detail.alamat) || '',
          provinsi: (detail && detail.provinsi_pt) || '',
          kab_kota: (detail && detail.kab_kota_pt) || '',
          kecamatan: (detail && detail.kecamatan_pt) || '',
          kode_pos: (detail && detail.kode_pos) || '',
          website: (detail && detail.website) || '',
          email: (detail && detail.email) || '',
          telepon: (detail && detail.no_tel) || '',
          latitude: (detail && detail.lintang_pt) || 0,
          longitude: (detail && detail.bujur_pt) || 0,
          tanggal_berdiri: (detail && detail.tgl_berdiri_pt) || '',
          prodi: prodis,
        };
        console.log(
          (detail ? 'OK' : 'DETAIL_FAIL') +
            ' (' +
            prodis.length +
            ' prodi, akred:' +
            ((detail && detail.akreditasi_pt) || '-') +
            ')'
        );
      } catch (e) {
        console.log('ERROR: ' + e.message);
      }
      await sleep(200);
    }

    fs.writeFileSync('pts_completed.json', JSON.stringify(completed));
  }

  console.log('\n=== FINALIZE ===');
  const results = Object.values(completed);
  const summary = {
    total_perguruan_tinggi: results.length,
    total_prodi: results.reduce((s, p) => s + p.prodi.length, 0),
    generated_at: new Date().toISOString(),
    semester: '20241',
    source: 'PDDikti API (pddikti.fastapicloud.dev)',
  };

  fs.writeFileSync('pddikti_all_data.json', JSON.stringify({ summary, data: results }, null, 2));
  console.log('Saved ' + results.length + ' PTs, ' + summary.total_prodi + ' prodi to pddikti_all_data.json');

  const byProvinsi = {};
  const byAkreditasi = {};
  for (const pt of results) {
    const prov = pt.provinsi || 'Tidak Diketahui';
    byProvinsi[prov] = (byProvinsi[prov] || 0) + 1;
    const ak = pt.akreditasi_pt || 'Tidak Terakreditasi';
    byAkreditasi[ak] = (byAkreditasi[ak] || 0) + 1;
  }
  console.log('\nPer Provinsi:');
  Object.entries(byProvinsi)
    .sort((a, b) => b[1] - a[1])
    .forEach(([k, v]) => console.log('  ' + k + ': ' + v));
  console.log('\nPer Akreditasi PT:');
  Object.entries(byAkreditasi)
    .sort((a, b) => b[1] - a[1])
    .forEach(([k, v]) => console.log('  ' + k + ': ' + v));
}

main().catch(console.error);
