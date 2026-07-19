/**
 * LANGKAH 1 — Simpan session InfinityFree
 *
 * Cara pakai:
 *   node 1-login.js
 *
 * Strategi: buka Chrome ASLI (bukan Playwright Chromium) via remote debugging
 * sehingga Cloudflare tidak bisa mendeteksinya sebagai bot.
 * Playwright hanya terhubung SETELAH login untuk membaca & menyimpan cookies.
 */

const { chromium } = require('playwright');
const { spawn }    = require('child_process');
const http         = require('http');
const fs           = require('fs');
const path         = require('path');
const os           = require('os');

const SESSION_PATH  = path.join(__dirname, 'session.json');
const DEBUG_PORT    = 9222;
const TMP_PROFILE   = path.join(os.tmpdir(), 'hrd-deploy-chrome');

const C = {
  cyan:   '\x1b[36m',
  green:  '\x1b[32m',
  yellow: '\x1b[33m',
  red:    '\x1b[31m',
  dim:    '\x1b[2m',
  reset:  '\x1b[0m',
};
function log(emoji, msg, color = C.reset) {
  console.log(`\n${color}${emoji}  ${msg}${C.reset}`);
}

// ── Cari path Chrome ─────────────────────────────────────────────────
function findChrome() {
  const candidates = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    path.join(process.env.LOCALAPPDATA || '', 'Google\\Chrome\\Application\\chrome.exe'),
  ];
  return candidates.find(p => fs.existsSync(p)) || null;
}

// ── Tunggu Chrome debug port siap ───────────────────────────────────
function waitForDebugPort(port, timeoutMs = 15000) {
  return new Promise((resolve, reject) => {
    const deadline = Date.now() + timeoutMs;

    function tryConnect() {
      http.get(`http://127.0.0.1:${port}/json/version`, res => {
        res.resume();
        resolve(true);
      }).on('error', () => {
        if (Date.now() >= deadline) {
          reject(new Error(`Chrome debug port ${port} tidak merespons dalam ${timeoutMs}ms`));
        } else {
          setTimeout(tryConnect, 500);
        }
      });
    }
    tryConnect();
  });
}

// ── MAIN ─────────────────────────────────────────────────────────────
(async () => {
  // 1. Cari Chrome
  const chromePath = findChrome();
  if (!chromePath) {
    console.error(C.red + '\n❌ Google Chrome tidak ditemukan di komputer ini.' + C.reset);
    console.error('   Install Chrome dari: https://www.google.com/chrome/');
    process.exit(1);
  }
  log('✅', `Chrome ditemukan: ${C.dim}${chromePath}${C.reset}`);

  // 2. Pastikan tidak ada Chrome lain di port ini
  // (Jika ada, sambungkan ke sana saja; jika tidak ada, launch baru)
  let chromeProcess = null;
  let alreadyRunning = false;

  try {
    await waitForDebugPort(DEBUG_PORT, 1000);
    alreadyRunning = true;
    log('ℹ️', `Port ${DEBUG_PORT} sudah ada Chrome yang running — langsung sambungkan.`, C.cyan);
  } catch {
    // Port belum ada — launch Chrome baru
    log('🚀', 'Membuka Chrome asli (bukan Playwright Chromium) ...', C.cyan);
    log('ℹ️', `Profile sementara: ${C.dim}${TMP_PROFILE}${C.reset}`);

    chromeProcess = spawn(
      chromePath,
      [
        `--remote-debugging-port=${DEBUG_PORT}`,
        `--user-data-dir=${TMP_PROFILE}`,
        '--no-first-run',
        '--no-default-browser-check',
        '--disable-default-apps',
        '--start-maximized',
        'https://www.infinityfree.com/login/',
      ],
      { detached: false, stdio: 'ignore' }
    );

    chromeProcess.on('error', err => {
      console.error(C.red + '\n❌ Gagal membuka Chrome: ' + err.message + C.reset);
      process.exit(1);
    });

    log('⏳', 'Menunggu Chrome siap ...', C.dim);
    await waitForDebugPort(DEBUG_PORT, 20000);
  }

  log(
    '👤',
    C.yellow +
      'Chrome sudah terbuka di halaman login InfinityFree.\n\n' +
      '   → Jika muncul halaman Cloudflare / "Verify you are human",\n' +
      '     selesaikan tantangan tersebut secara normal.\n\n' +
      '   → Setelah itu login dengan email & password InfinityFree Anda.\n\n' +
      '   Script otomatis lanjut begitu login berhasil (max 5 menit).' +
      C.reset
  );

  // 3. Sambungkan Playwright ke Chrome via CDP (hanya untuk baca cookies)
  const browser = await chromium.connectOverCDP(`http://127.0.0.1:${DEBUG_PORT}`);

  // 4. Ambil context & page yang sudah ada
  const [defaultCtx] = browser.contexts();
  const pages = defaultCtx.pages();
  let page = pages.find(p => p.url().includes('infinityfree')) ?? pages[0];

  if (!page) {
    page = await defaultCtx.newPage();
    await page.goto('https://www.infinityfree.com/login/');
  }

  // 5. Tunggu login sukses — deteksi URL berubah ke halaman accounts
  try {
    await page.waitForURL(
      url =>
        url.includes('/accounts') ||
        url.includes('/hosting')  ||
        url.includes('/dashboard'),
      { timeout: 5 * 60 * 1000 } // 5 menit
    );
  } catch {
    // Fallback: tunggu elemen yang muncul setelah login
    await page.waitForSelector(
      '[class*="account"], a[href*="/accounts"], .client-navbar',
      { timeout: 5 * 60 * 1000 }
    );
  }

  log('✅', 'Login berhasil terdeteksi!', C.green);

  // 6. Simpan cookies ke session.json
  log('💾', 'Menyimpan session ...', C.cyan);

  const cookies = await defaultCtx.cookies();

  // Simpan dalam format yang bisa dibaca Playwright lagi
  const sessionData = {
    cookies,
    origins: [],
  };

  fs.writeFileSync(SESSION_PATH, JSON.stringify(sessionData, null, 2));
  log('✅', `Session tersimpan → ${SESSION_PATH}  (${cookies.length} cookies)`, C.green);

  // 7. Tutup koneksi CDP (Chrome ditutup juga)
  await browser.close();
  if (chromeProcess) {
    chromeProcess.kill();
  }

  log(
    '🚀',
    C.green +
      'Langkah 1 selesai!\n\n' +
      '   Selanjutnya jalankan:\n' +
      '     node 2-deploy.js' +
      C.reset
  );

})().catch(err => {
  console.error(C.red + '\n❌ Error: ' + err.message + C.reset);
  process.exit(1);
});
