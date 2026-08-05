// capture_kepala_unit_wakil.mjs - screenshot fitur kepala unit mewakili pegawai
import { chromium } from 'playwright'
import path from 'node:path'
import { mkdir } from 'node:fs/promises'

const FRONT = 'http://localhost:5173'
const API = 'http://localhost:8000/api'
const ROOT = path.resolve('screenshots', 'desktop')
const FOLDER = '06-kepala-unit-wakil'

async function apiLogin(creds) {
  const res = await fetch(`${API}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(creds),
  })
  const data = await res.json()
  if (!res.ok || !data.token) throw new Error('login gagal: ' + JSON.stringify(data))
  return { token: data.token, user: data.user }
}

async function setupAuth(page, token, user) {
  await page.goto(`${FRONT}/login`, { waitUntil: 'domcontentloaded' })
  const expiry = new Date(Date.now() + 3 * 60 * 60 * 1000).toISOString()
  await page.evaluate(({ token, user, expiry }) => {
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(user))
    localStorage.setItem('tokenExpiryTime', expiry)
  }, { token, user, expiry })
}

async function shoot(page, filename, p, opts = {}) {
  const dir = path.resolve(ROOT, FOLDER)
  await mkdir(dir, { recursive: true })
  if (p) {
    try { await page.goto(`${FRONT}${p}`, { waitUntil: 'networkidle', timeout: 30000 }) }
    catch { await page.goto(`${FRONT}${p}`, { waitUntil: 'domcontentloaded', timeout: 30000 }) }
    await page.waitForTimeout(2000)
  }
  await page.screenshot({ path: path.resolve(dir, filename), fullPage: opts.full !== false })
  console.log(`  📷 ${FOLDER}/${filename}`)
}

const browser = await chromium.launch()
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } })
const page = await ctx.newPage()
page.on('pageerror', () => {})

console.log('▶ Kepala Unit: mewakili pegawai')
const auth = await apiLogin({ identity: 'bidang-kdh@sipintar.go.id', password: 'password' })
await setupAuth(page, auth.token, auth.user)

// 1. Dashboard kepala unit
await shoot(page, '01-dashboard-kepala-unit.png', '/dashboard')

// 2. Form buat pengajuan — pilih pegawai (dropdown tertutup, ada nama terpilih)
await page.goto(`${FRONT}/pengajuan/baru`, { waitUntil: 'networkidle', timeout: 30000 })
await page.waitForTimeout(2500) // tunggu load staff
// pilih pegawai pertama dari dropdown
try {
  await page.locator('select').first().selectOption({ index: 1 })
  await page.waitForTimeout(500)
} catch (e) { console.log('  (select gagal:', e.message, ')') }
await shoot(page, '02-form-pilih-pegawai.png', null)

// 3. Dropdown terbuka memperlihatkan daftar pegawai di unit
await page.locator('select').first().click()
await page.waitForTimeout(400)
await page.screenshot({ path: path.resolve(ROOT, FOLDER, '03-dropdown-daftar-pegawai.png'), fullPage: true })
console.log(`  📷 ${FOLDER}/03-dropdown-daftar-pegawai.png`)

// 4. Riwayat pengajuan — memperlihatkan pengajuan mewakili beberapa pegawai
await shoot(page, '04-riwayat-mewakili-pegawai.png', '/pengajuan')

// 5. Detail pengajuan mewakili — banner "dibuat oleh"
await shoot(page, '05-detail-mewakili-pegawai.png', '/pengajuan/25')

await browser.close()
console.log('✅ Selesai')
