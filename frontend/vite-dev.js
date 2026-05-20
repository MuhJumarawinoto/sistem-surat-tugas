import { fileURLToPath } from 'url'
import { dirname } from 'path'
import { spawn } from 'child_process'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

const vitePath = import.meta.resolve('vite/bin/vite.js')

const child = spawn('node', [vitePath, ...process.argv.slice(2)], {
  stdio: 'inherit',
  shell: true
})

child.on('exit', (code) => process.exit(code ?? 0))
