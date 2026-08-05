// =============================================================================
// screenshot.mjs - Otomatisasi screenshot SIPINTAR untuk Manual Book
// Cara pakai:  node screenshot.mjs            (desktop 1440x900)
//              node screenshot.mjs --mobile   (mobile 390x844)
// =============================================================================
import { chromium } from 'playwright'
import { mkdir, writeFile } from 'node:fs/promises'
import path from 'node:path'

const FRONT = 'http://localhost:5173'
const API   = 'http://localhost:8000/api'

const args = process.argv.slice(2)
const isMobile = args.includes('--mobile')

// Viewport
const VIEWPORT = isMobile ? { width: 390, height: 844 } : { width: 1440, height: 900 }
const DEVICE   = isMobile ? 'mobile' : 'desktop'
const ROOT     = path.resolve('screenshots', DEVICE)

// Akun demo
const ACCOUNTS = {
  pemohon:  { identity: 'drajat@disdik.go.id',    password: 'password' },
  atasan:   { identity: 'bkpsdm@sipintar.go.id',  password: 'password' },
  admin:    { identity: 'admin@bkpsdm.go.id',     password: 'password' },
  kepala:   { identity: 'kepala@bkpsdm.go.id',    password: 'password' },
}

// ---- Helper: login via API, kembalikan token + user ----
async function apiLogin(creds) {
  const res = await fetch(`${API}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ identity: creds.identity, password: creds.password }),
  })
  const data = await res.json()
  if (!res.ok || !data.token) throw new Error(`Login gagal (${creds.identity}): ${JSON.stringify(data)}`)
  return { token: data.token, user: data.user }
}

// ---- Helper: GET API dengan token ----
async function apiGet(token, urlPath) {
  const res = await fetch(`${API}${urlPath}`, {
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
  })
  if (!res.ok) return null
  return res.json()
}

// ---- Helper: siapkan localStorage auth di halaman ----
async function setupAuth(page, token, user) {
  // Pergi dulu ke origin supaya localStorage tersedia
  await page.goto(`${FRONT}/login`, { waitUntil: 'domcontentloaded' })
  const expiry = new Date(Date.now() + 3 * 60 * 60 * 1000).toISOString()
  await page.evaluate(({ token, user, expiry }) => {
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(user))
    localStorage.setItem('tokenExpiryTime', expiry)
  }, { token, user, expiry })
}

// ---- Helper: ambil screenshot satu halaman ----
async function shoot(page, folder, filename, pathAfterFront) {
  const dir = path.resolve(ROOT, folder)
  await mkdir(dir, { recursive: true })
  const url = `${FRONT}${pathAfterFront}`
  try {
    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 })
  } catch {
    // fallback: load biasa
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 })
  }
  // tunggu animasi/toast reda
  await page.waitForTimeout(1800)
  const out = path.resolve(dir, filename)
  await page.screenshot({ path: out, fullPage: true })
  console.log(`  📷 ${folder}/${filename}  ←  ${pathAfterFront}`)
  return out
}

// =============================================================================
async function main() {
  console.log(`\n=== SIPINTAR Screenshot (${DEVICE} ${VIEWPORT.width}x${VIEWPORT.height}) ===\n`)
  await mkdir(ROOT, { recursive: true })

  const browser = await chromium.launch()
  const context = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 1 })
  const page = await context.newPage()
  // abaikan error console agar tidak noisy
  page.on('pageerror', () => {})

  const manifest = []

  // ---------------------------------------------------------------------------
  // 0. HALAMAN PUBLIK
  // ---------------------------------------------------------------------------
  console.log('▶ Halaman Publik & Autentikasi')
  await page.goto(`${FRONT}/login`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(1200)
  await shoot(page, '00-autentikasi', '01-halaman-login.png', '/login')

  // login terisi (ketik tanpa submit)
  await page.goto(`${FRONT}/login`, { waitUntil: 'domcontentloaded' })
  await page.waitForTimeout(800)
  await page.fill('#identity', ACCOUNTS.pemohon.identity)
  await page.fill('#password', ACCOUNTS.pemohon.password)
  await page.waitForTimeout(500)
  await page.screenshot({ path: path.resolve(ROOT, '00-autentikasi', '02-login-terisi.png'), fullPage: true })
  console.log('  📷 00-autentikasi/02-login-terisi.png  ←  /login (terisi)')

  // halaman verifikasi QR publik
  await shoot(page, '05-publik', '01-verifikasi-qr.png', '/verify')

  // ---------------------------------------------------------------------------
  // 1. ROLE: PEMOHON
  // ---------------------------------------------------------------------------
  console.log('\n▶ Role: Pemohon (PNS)')
  const pAuth = await apiLogin(ACCOUNTS.pemohon)
  await setupAuth(page, pAuth.token, pAuth.user)

  await shoot(page, '01-pemohon', '01-dashboard.png', '/dashboard')
  await shoot(page, '01-pemohon', '02-riwayat-pengajuan.png', '/pengajuan')
  await shoot(page, '01-pemohon', '03-buat-pengajuan-baru.png', '/pengajuan/baru')
  await shoot(page, '01-pemohon', '06-profil.png', '/profile')
  await shoot(page, '01-pemohon', '07-notifikasi.png', '/notifications')

  // detail & edit butuh ID pengajuan milik pemohon
  const pList = await apiGet(pAuth.token, '/pengajuan?include_deleted=1')
  const pItems = pList?.data || pList || []
  if (pItems.length) {
    const any = pItems[0]
    await shoot(page, '01-pemohon', '04-detail-pengajuan.png', `/pengajuan/${any.id}`)
    const draft = pItems.find((x) => x.status === 'draft')
    if (draft) {
      await shoot(page, '01-pemohon', '05-edit-pengajuan.png', `/pengajuan/${draft.id}/edit`)
    } else {
      console.log('  ⚠️  Tidak ada pengajuan draft untuk halaman edit (dilewati)')
    }
  } else {
    console.log('  ⚠️  Pemohon belum punya pengajuan (detail & edit dilewati)')
  }

  // ---------------------------------------------------------------------------
  // 2. ROLE: ATASAN
  // ---------------------------------------------------------------------------
  console.log('\n▶ Role: Atasan')
  const aAuth = await apiLogin(ACCOUNTS.atasan)
  await setupAuth(page, aAuth.token, aAuth.user)
  await shoot(page, '02-atasan', '01-dashboard.png', '/dashboard')
  await shoot(page, '02-atasan', '02-riwayat-pengajuan.png', '/pengajuan')
  await shoot(page, '02-atasan', '03-profil.png', '/profile')
  // atasan yang is_kepala_unit bisa akses surat-tugas dinas
  if (aAuth.user?.is_kepala_unit) {
    await shoot(page, '02-atasan', '04-surat-tugas-dinas.png', '/kepala/surat-tugas')
  }

  // ---------------------------------------------------------------------------
  // 3. ROLE: ADMIN BKPSDM
  // ---------------------------------------------------------------------------
  console.log('\n▶ Role: Admin BKPSDM')
  const adAuth = await apiLogin(ACCOUNTS.admin)
  await setupAuth(page, adAuth.token, adAuth.user)

  await shoot(page, '03-admin-bkpsdm', '01-verifikasi-dokumen.png', '/admin/verifikasi')
  await shoot(page, '03-admin-bkpsdm', '03-riwayat-verifikasi.png', '/admin/riwayat-verifikasi')
  await shoot(page, '03-admin-bkpsdm', '04-surat-izin-belajar.png', '/admin/surat-izin')
  await shoot(page, '03-admin-bkpsdm', '05-surat-tugas-belajar.png', '/admin/surat-tugas')
  await shoot(page, '03-admin-bkpsdm', '06-surat-tugas-mandiri.png', '/admin/surat-tugas-mandiri')
  await shoot(page, '03-admin-bkpsdm', '08-data-pegawai.png', '/admin/pegawai')
  await shoot(page, '03-admin-bkpsdm', '09-jenis-dokumen.png', '/admin/jenis-dokumen')
  await shoot(page, '03-admin-bkpsdm', '10-pddikti-sync.png', '/admin/pddikti-sync')
  await shoot(page, '03-admin-bkpsdm', '11-pdf-editor.png', '/admin/pdf-editor')

  // detail verifikasi: cari pengajuan pending_admin
  const adList = await apiGet(adAuth.token, '/pengajuan')
  const adItems = adList?.data || adList || []
  const pending = adItems.find((x) => x.status === 'pending_admin' || x.status === 'pending_atasan')
  if (pending) {
    await shoot(page, '03-admin-bkpsdm', '02-detail-verifikasi.png', `/admin/verifikasi/${pending.id}`)
  } else {
    console.log('  ⚠️  Tidak ada pengajuan pending untuk detail verifikasi (dilewati)')
  }

  // detail surat tugas mandiri
  const stm = await apiGet(adAuth.token, '/admin/surat-tugas-mandiri')
  const stmItems = stm?.data || stm || []
  if (stmItems.length) {
    await shoot(page, '03-admin-bkpsdm', '07-detail-surat-tugas-mandiri.png', `/admin/surat-tugas-mandiri/${stmItems[0].id}`)
  } else {
    console.log('  ⚠️  Tidak ada Surat Tugas Mandiri (detail dilewati)')
  }

  // ---------------------------------------------------------------------------
  // 4. ROLE: KEPALA BKPSDM
  // ---------------------------------------------------------------------------
  console.log('\n▶ Role: Kepala BKPSDM')
  const kAuth = await apiLogin(ACCOUNTS.kepala)
  await setupAuth(page, kAuth.token, kAuth.user)
  await shoot(page, '04-kepala-bkpsdm', '01-surat-perlu-tte.png', '/kepala/signing')
  await shoot(page, '04-kepala-bkpsdm', '03-riwayat-tte.png', '/kepala/riwayat')

  // detail signing: ambil surat izin pertama
  const sib = await apiGet(kAuth.token, '/admin/surat-izin')
  const sibItems = sib?.data || sib || []
  if (sibItems.length) {
    await shoot(page, '04-kepala-bkpsdm', '02-detail-signing.png', `/kepala/signing/${sibItems[0].id}`)
  } else {
    console.log('  ⚠️  Tidak ada Surat Izin Belajar (detail signing dilewati)')
  }

  await browser.close()

  // ---------------------------------------------------------------------------
  // Tulis manifest (daftar semua screenshot) untuk manual book
  // ---------------------------------------------------------------------------
  await writeFile(path.resolve(ROOT, '_manifest.json'), JSON.stringify(manifest, null, 2))
  console.log(`\n✅ Selesai. Screenshot tersimpan di: ${ROOT}\n`)
}

main().catch((e) => { console.error('\n❌ ERROR:', e.message); process.exit(1) })
