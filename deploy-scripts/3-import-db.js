/**
 * LANGKAH 3 — Import database via phpMyAdmin (fully automated)
 * Flow: Login InfinityFree (dash.infinityfree.com) → akun → phpMyAdmin SSO → import
 *
 *   node 3-import-db.js
 */

const { chromium } = require('playwright');
const { spawn, exec } = require('child_process');
const http  = require('fs').existsSync ? require('http') : require('http');
const fs    = require('fs');
const path  = require('path');
const os    = require('os');

const ROOT         = path.resolve(__dirname, '..');
const CONFIG_PATH  = path.join(__dirname, 'deploy.config.json');
const SECRETS_PATH = path.join(__dirname, 'secrets.json');
const DB_DUMP_PATH = path.join(ROOT, 'database_dump.sql');
const TMP_PROFILE  = path.join(os.tmpdir(), 'hrd-pma-v3');
const DEBUG_PORT   = 9225;
const SCREENSHOT   = path.join(__dirname, '_state.png');

const C = {
  reset: '\x1b[0m', bold: '\x1b[1m', cyan: '\x1b[36m',
  green: '\x1b[32m', yellow: '\x1b[33m', red: '\x1b[31m', dim: '\x1b[2m',
};
const log   = (e, m, c = C.reset) => console.log(`\n${c}${e}  ${m}${C.reset}`);
const logOk   = m => console.log(`  ${C.green}✓${C.reset}  ${m}`);
const logWarn = m => console.log(`  ${C.yellow}⚠${C.reset}  ${m}`);
const logInfo = m => console.log(`  ${C.cyan}·${C.reset}  ${C.dim}${m}${C.reset}`);
const delay   = ms => new Promise(r => setTimeout(r, ms));

function findChrome() {
  return [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    path.join(process.env.LOCALAPPDATA || '', 'Google\\Chrome\\Application\\chrome.exe'),
  ].find(p => fs.existsSync(p)) || null;
}

function waitForPort(port, ms = 25000) {
  return new Promise((resolve, reject) => {
    const http2 = require('http');
    const end = Date.now() + ms;
    const try_ = () => http2.get(`http://127.0.0.1:${port}/json/version`, r => { r.resume(); resolve(); })
      .on('error', () => Date.now() < end ? setTimeout(try_, 600) : reject(new Error('Chrome timeout')));
    try_();
  });
}

async function ss(page, label) {
  await page.screenshot({ path: SCREENSHOT }).catch(() => {});
  logInfo(`[${label}] ${page.url().slice(0, 75)}`);
}

async function goto(page, url) {
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 25000 }).catch(() => {});
  await delay(2500);
}

(async () => {
  console.log('\n' + C.bold + C.cyan + '  === Import Database (fully automated) ===' + C.reset);

  const cfg = fs.existsSync(CONFIG_PATH) ? JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8')) : {};
  const sec = fs.existsSync(SECRETS_PATH) ? JSON.parse(fs.readFileSync(SECRETS_PATH, 'utf8')) : {};

  const IFEmail = sec.infinityfreeEmail;
  const IFPass  = sec.infinityfreePass;
  const ftpUser = cfg.ftpUser || 'if0_41805346';
  const dbUser  = cfg.dbUser  || ftpUser;
  const dbPass  = sec.dbPass;
  const dbName  = cfg.dbName  || 'if0_41805346_hrd_system';
  const fileMB  = (fs.statSync(DB_DUMP_PATH).size / 1024 / 1024).toFixed(2);
  logInfo(`DB: ${dbName} | File: ${fileMB} MB`);

  // ── 0. Buka Chrome asli ──────────────────────────────────────────────
  const chromePath = findChrome();
  if (!chromePath) { console.error(C.red + '❌ Chrome tidak ditemukan.' + C.reset); process.exit(1); }

  await new Promise(r => exec(`for /f "tokens=5" %a in ('netstat -ano ^| findstr :${DEBUG_PORT} ') do taskkill /F /PID %a`, { shell: 'cmd.exe' }, () => r()));
  await delay(1000);

  log('🚀', 'Membuka Chrome...', C.cyan);
  const chromeProc = spawn(chromePath, [
    `--remote-debugging-port=${DEBUG_PORT}`,
    `--user-data-dir=${TMP_PROFILE}`,
    '--no-first-run', '--no-default-browser-check', '--disable-default-apps',
    '--start-maximized', 'https://www.infinityfree.com/',
  ], { detached: false, stdio: 'ignore' });
  chromeProc.on('error', e => { console.error(C.red + e.message + C.reset); process.exit(1); });
  await waitForPort(DEBUG_PORT);
  logOk('Chrome siap');

  const browser = await chromium.connectOverCDP(`http://127.0.0.1:${DEBUG_PORT}`);
  const [ctx] = browser.contexts();
  const page  = ctx.pages()[0] || await ctx.newPage();

  // ── 1. Login InfinityFree ────────────────────────────────────────────
  log('🔐', 'Login ke InfinityFree...', C.cyan);

  for (let i = 0; i < 25; i++) {
    const url = page.url();
    if (url.includes('dash.infinityfree.com/accounts') || url.includes('/dashboard')) break;

    const emailVis = await page.locator('input[type="email"], input[name="email"]').first().isVisible({ timeout: 800 }).catch(() => false);
    if (emailVis) {
      logOk('Form login tersedia, isi credentials...');
      await page.locator('input[type="email"], input[name="email"]').first().fill(IFEmail);
      await page.locator('input[type="password"], input[name="password"]').first().fill(IFPass);
      await page.locator('button[type="submit"], input[type="submit"]').first().click();
      logInfo('Submit, tunggu redirect...');
      await page.waitForURL(u => u.includes('/accounts') || u.includes('/dashboard'), { timeout: 15000 }).catch(() => {});
      break;
    }

    // Klik tombol Login jika di homepage
    const loginBtn = await page.locator('a[href*="login"], a:has-text("Login")').first().isVisible({ timeout: 800 }).catch(() => false);
    if (loginBtn) {
      await page.locator('a[href*="login"], a:has-text("Login")').first().click();
      await delay(3000);
      continue;
    }

    logInfo(`[${i+1}/25] ${url.slice(0, 60)}`);
    await delay(2000);
  }

  await ss(page, 'after-login');
  logInfo(`URL: ${page.url()}`);

  if (!page.url().includes('/accounts') && !page.url().includes('/dashboard')) {
    logWarn('Login tidak berhasil terdeteksi. Coba lanjut...');
  } else {
    logOk('Login berhasil!');
  }

  // ── 2. Buka halaman akun spesifik ────────────────────────────────────
  log('🌐', `Buka halaman akun: ${ftpUser}`, C.cyan);
  await goto(page, `https://dash.infinityfree.com/accounts/${ftpUser}`);
  await ss(page, 'account-detail');

  // Log semua link
  const allLinks = await page.$$eval('a', els =>
    els.map(e => ({ href: e.href || '', text: (e.textContent || '').trim().slice(0, 50) }))
       .filter(l => l.href && l.text)
  ).catch(() => []);

  logInfo(`Links di halaman akun (${allLinks.length} total):`);
  allLinks.forEach(l => logInfo(`  ${l.text.padEnd(40)} | ${l.href.slice(0, 65)}`));

  // ── 3. Cari & klik link phpMyAdmin ───────────────────────────────────
  log('🔗', 'Cari link phpMyAdmin...', C.cyan);

  let pmaUrl = null;

  // Prioritas: link yang langsung ke phpMyAdmin
  const pmaLink = allLinks.find(l =>
    l.href.includes('phpmyadmin') || l.href.includes('php-myadmin') ||
    l.text.toLowerCase().includes('phpmyadmin') || l.text.toLowerCase().includes('php my admin')
  );
  if (pmaLink) { pmaUrl = pmaLink.href; logOk(`phpMyAdmin link: ${pmaUrl}`); }

  // Jika tidak, cari tombol/link MySQL
  if (!pmaUrl) {
    const mysqlLinks = allLinks.filter(l =>
      l.href.toLowerCase().includes('mysql') ||
      l.text.toLowerCase().match(/\bmysql\b|\bdatabase\b/)
    );
    logInfo(`MySQL links: ${mysqlLinks.length}`);
    for (const ml of mysqlLinks) {
      logInfo(`  Coba: ${ml.href}`);
      await goto(page, ml.href);
      await ss(page, 'mysql-page');
      const links2 = await page.$$eval('a', els =>
        els.map(e => ({ href: e.href || '', text: (e.textContent || '').trim().slice(0, 50) }))
      ).catch(() => []);
      const pma2 = links2.find(l => l.href.includes('phpmyadmin') || l.href.includes('php-myadmin') || l.text.toLowerCase().includes('phpmyadmin'));
      if (pma2) { pmaUrl = pma2.href; logOk(`phpMyAdmin: ${pmaUrl}`); break; }
    }
  }

  // Coba klik tombol MySQL yang mungkin trigger popup atau navigasi
  if (!pmaUrl) {
    logInfo('Coba klik elemen MySQL di halaman...');
    for (const sel of [
      'a:has-text("MySQL")', 'button:has-text("MySQL")',
      'a:has-text("Database")', '[href*="mysql"]',
      'a:has-text("phpMyAdmin")', '[href*="phpmyadmin"]',
    ]) {
      const el = page.locator(sel).first();
      const vis = await el.isVisible({ timeout: 1000 }).catch(() => false);
      if (vis) {
        logInfo(`Klik: ${sel}`);
        await el.click().catch(() => {});
        await delay(3000);
        await ss(page, `clicked-${sel.slice(0, 20)}`);
        const links3 = await page.$$eval('a', els =>
          els.map(e => ({ href: e.href || '', text: (e.textContent || '').trim().slice(0, 50) }))
        ).catch(() => []);
        const pma3 = links3.find(l => l.href.includes('phpmyadmin') || l.href.includes('php-myadmin'));
        if (pma3) { pmaUrl = pma3.href; logOk(`phpMyAdmin: ${pmaUrl}`); break; }
        // Cek apakah navigasi ke phpMyAdmin
        if (page.url().includes('phpmyadmin') || page.url().includes('php-myadmin')) {
          pmaUrl = page.url(); logOk(`Langsung di phpMyAdmin: ${pmaUrl}`); break;
        }
      }
    }
  }

  // ── 4. Buka phpMyAdmin ────────────────────────────────────────────────
  if (pmaUrl) {
    log('🔗', `Buka phpMyAdmin: ${pmaUrl.slice(0, 70)}`, C.cyan);
    await goto(page, pmaUrl);
    await ss(page, 'pma-opened');
    logInfo(`URL phpMyAdmin: ${page.url().slice(0, 80)}`);
  } else {
    logWarn('Link phpMyAdmin tidak ditemukan. Lihat screenshot: ' + SCREENSHOT);
    // Coba login direct ke php-myadmin.net dengan MySQL credentials
    logInfo('Coba akses langsung php-myadmin.net...');
    await goto(page, 'https://php-myadmin.net/');
    await ss(page, 'pma-direct');
    logInfo(`URL: ${page.url()}`);
    if (dbPass) {
      const passVis = await page.locator('input[type="password"]').first().isVisible({ timeout: 3000 }).catch(() => false);
      if (passVis) {
        await page.locator('input[name="pma_username"], input[type="text"]').first().fill(dbUser).catch(() => {});
        await page.locator('input[type="password"]').first().fill(dbPass).catch(() => {});
        await page.locator('input#input_go, input[value="Go"], button[type="submit"]').first().click().catch(() => {});
        await delay(4000);
        await ss(page, 'pma-after-direct-login');
      }
    }
  }

  // ── 5. Navigasi ke halaman Import ────────────────────────────────────
  log('📥', `Buka Import page: ${dbName}`, C.cyan);
  const importUrl = `https://php-myadmin.net/index.php?route=/database/import&db=${encodeURIComponent(dbName)}`;
  await goto(page, importUrl);
  await ss(page, 'import-page');
  logInfo(`URL: ${page.url()}`);

  let hasFile = await page.locator('input[type="file"]').isVisible({ timeout: 5000 }).catch(() => false);

  if (!hasFile) {
    // Coba via nav kiri
    try {
      await page.locator(`a:has-text("${dbName}")`).first().click({ timeout: 5000 });
      await delay(2000);
      await page.locator('#topmenu a:has-text("Import"), a[href*="import"]').first().click({ timeout: 5000 });
      await delay(2000);
      hasFile = await page.locator('input[type="file"]').isVisible({ timeout: 3000 }).catch(() => false);
    } catch { /* ignore */ }
    await ss(page, 'import-via-nav');
  }

  if (!hasFile) {
    logWarn(`Halaman Import tidak terbuka. URL: ${page.url()}`);
    logInfo(`Screenshot: ${SCREENSHOT}`);
    await browser.close(); chromeProc.kill(); process.exit(1);
  }
  logOk('Halaman Import terbuka');

  // ── 6. Upload & Import ────────────────────────────────────────────────
  log('📤', `Upload database_dump.sql (${fileMB} MB)...`, C.cyan);
  await page.locator('input[type="file"]').first().setInputFiles(DB_DUMP_PATH);
  logOk('File dipilih');

  log('▶️', 'Klik tombol Import/Go...', C.cyan);
  await page.locator('input#buttonGo, input[name="do_import"], input[value="Go"], input[type="submit"]').first().click();
  logInfo('Tunggu selesai...');
  await page.waitForSelector('.alert-success, .success, #result_query, .alert', { timeout: 120000 }).catch(() => {});
  await ss(page, 'import-result');
  logOk('Import selesai!');

  log('✅', 'Database import SELESAI!', C.green);

  if (cfg.baseURL) {
    log('🌐', `Buka aplikasi: ${cfg.baseURL}`, C.cyan);
    await goto(page, cfg.baseURL);
    await ss(page, 'app');
    logOk(`Judul: ${await page.title().catch(() => '')}`);
  }

  await browser.close();
  chromeProc.kill();
  if (fs.existsSync(SCREENSHOT)) fs.unlinkSync(SCREENSHOT);

  console.log('\n' + C.bold + C.green + '  DEPLOYMENT LENGKAP!' + C.reset);
  console.log(`  URL: ${C.cyan}${cfg.baseURL || ''}${C.reset}\n`);

})().catch(err => {
  console.error(C.red + '\n❌ Fatal: ' + err.message + C.reset);
  process.exit(1);
});
