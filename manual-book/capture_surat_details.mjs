// capture_surat_details.mjs - lengkapi 2 halaman detail surat
import { chromium } from 'playwright'
import path from 'node:path'

const FRONT = 'http://localhost:5173'
const API = 'http://localhost:8000/api'
const ROOT = path.resolve('screenshots', 'desktop')

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

async function shoot(page, folder, filename, p) {
  const dir = path.resolve(ROOT, folder)
  const { mkdir } = await import('node:fs/promises')
  await mkdir(dir, { recursive: true })
  try { await page.goto(`${FRONT}${p}`, { waitUntil: 'networkidle', timeout: 30000 }) }
  catch { await page.goto(`${FRONT}${p}`, { waitUntil: 'domcontentloaded', timeout: 30000 }) }
  await page.waitForTimeout(2200)
  await page.screenshot({ path: path.resolve(dir, filename), fullPage: true })
  console.log(`  📷 ${folder}/${filename}  ←  ${p}`)
}

const browser = await chromium.launch()
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } })
const page = await ctx.newPage()
page.on('pageerror', () => {})

console.log('▶ Lengkapi halaman detail surat')

// Admin: detail surat tugas mandiri (id=11)
const ad = await apiLogin({ identity: 'admin@bkpsdm.go.id', password: 'password' })
await setupAuth(page, ad.token, ad.user)
await shoot(page, '03-admin-bkpsdm', '07-detail-surat-tugas-mandiri.png', '/admin/surat-tugas-mandiri/11')

// Kepala: detail signing (surat izin id=20)
const kp = await apiLogin({ identity: 'kepala@bkpsdm.go.id', password: 'password' })
await setupAuth(page, kp.token, kp.user)
await shoot(page, '04-kepala-bkpsdm', '02-detail-signing.png', '/kepala/signing/20')

await browser.close()
console.log('✅ Selesai')
