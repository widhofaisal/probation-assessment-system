/**
 * LANGKAH 2 — Deploy ke InfinityFree (FTP + Database Import)
 *
 * Cara pakai:
 *   node 2-deploy.js
 *
 * Pastikan sudah menjalankan node 1-login.js terlebih dahulu.
 * Jika deploy.config.json sudah ada, hanya akan ditanya password.
 * Jika belum ada, script akan scrape konfigurasi dari InfinityFree otomatis.
 */

const { chromium } = require('playwright');
const ftp          = require('basic-ftp');
const fs           = require('fs');
const path         = require('path');
const readline     = require('readline');
const crypto       = require('crypto');

// ── Paths ─────────────────────────────────────────────────────────────
const ROOT         = path.resolve(__dirname, '..');
const SESSION_PATH = path.join(__dirname, 'session.json');
const CONFIG_PATH  = path.join(__dirname, 'deploy.config.json');
const DB_DUMP_PATH = path.join(ROOT, 'database_dump.sql');

// ── ANSI colors ───────────────────────────────────────────────────────
const C = {
  reset:  '\x1b[0m',
  bold:   '\x1b[1m',
  cyan:   '\x1b[36m',
  green:  '\x1b[32m',
  yellow: '\x1b[33m',
  red:    '\x1b[31m',
  dim:    '\x1b[2m',
};

function log(emoji, msg, color = C.reset) {
  console.log(`\n${color}${emoji}  ${msg}${C.reset}`);
}
function logStep(n, total, title) {
  console.log(`\n${C.bold}${'─'.repeat(58)}`);
  console.log(`LANGKAH ${n}/${total}: ${title}${C.reset}`);
  console.log(C.dim + '─'.repeat(58) + C.reset);
}
function logOk(msg)   { console.log(`  ${C.green}✓${C.reset}  ${msg}`); }
function logInfo(msg) { console.log(`  ${C.cyan}·${C.reset}  ${C.dim}${msg}${C.reset}`); }
function logWarn(msg) { console.log(`\n  ${C.yellow}⚠${C.reset}  ${msg}`); }

let rl;
function ask(label, defaultVal = '') {
  const hint = defaultVal ? ` ${C.dim}[${defaultVal}]${C.reset}` : '';
  return new Promise(resolve => {
    rl.question(`  ${label}${hint}: `, ans => {
      resolve(ans.trim() || defaultVal);
    });
  });
}
function askPassword(label) {
  return new Promise(resolve => {
    rl.question(`  ${label}: `, ans => resolve(ans.trim()));
  });
}
function pressEnterToContinue(msg = 'Tekan Enter untuk melanjutkan...') {
  return new Promise(resolve => rl.question(`\n  ${C.yellow}${msg}${C.reset} `, resolve));
}
function delay(ms) { return new Promise(r => setTimeout(r, ms)); }

// ── Config ────────────────────────────────────────────────────────────
const SECRETS_PATH = path.join(__dirname, 'secrets.json');

function loadConfig() {
  if (fs.existsSync(CONFIG_PATH)) {
    try { return JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8')); } catch { return {}; }
  }
  return {};
}

function loadSecrets() {
  if (fs.existsSync(SECRETS_PATH)) {
    try { return JSON.parse(fs.readFileSync(SECRETS_PATH, 'utf8')); } catch { return {}; }
  }
  return {};
}
function saveConfig(cfg) {
  const safe = { ...cfg };
  delete safe.ftpPass;
  delete safe.dbPass;
  fs.writeFileSync(CONFIG_PATH, JSON.stringify(safe, null, 2));
}

// ── Scrape config dari InfinityFree (hanya jika config belum ada) ─────
async function scrapeInfinityFreeConfig(savedState) {
  log('🌐', 'Membaca konfigurasi dari InfinityFree ...', C.cyan);
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ storageState: savedState });
  const page    = await context.newPage();

  const result = { ftpHost: 'ftpupload.net' };

  try {
    // Coba berbagai URL akun InfinityFree
    for (const url of [
      'https://www.infinityfree.com/accounts/',
      'https://www.infinityfree.com/hosting/',
      'https://app.infinityfree.com/',
    ]) {
      await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {});
      const body = await page.evaluate(() => document.body.innerText).catch(() => '');
      const uMatch = body.match(/if0_[a-zA-Z0-9]+/);
      if (uMatch) {
        result.ftpUser = uMatch[0];
        result.dbUser  = uMatch[0];
        result.dbName  = uMatch[0]; // default — user ganti sendiri jika beda
        logOk(`Username ditemukan: ${result.ftpUser}`);

        // Cari MySQL hostname
        const sqlMatch = body.match(/sql\d+\.infinityfree\.com/i);
        if (sqlMatch) {
          result.dbHost = sqlMatch[0];
          logOk(`MySQL host: ${result.dbHost}`);
        }

        // Cari subdomain / base URL
        const domainMatch = body.match(/[a-zA-Z0-9-]+\.infinityfreeapp\.com/i);
        if (domainMatch) {
          result.baseURL = `https://${domainMatch[0]}/`;
          logOk(`Base URL: ${result.baseURL}`);
        }
        break;
      }
    }

    // Jika MySQL host belum ketemu, coba masuk ke vpanel
    if (!result.dbHost && result.ftpUser) {
      const links = await page.$$eval(
        'a[href*="vpanel"], a[href*="cpanel"], a:has-text("Control Panel")',
        els => els.map(e => e.href)
      ).catch(() => []);
      for (const href of links.slice(0, 3)) {
        await page.goto(href, { waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {});
        const body2 = await page.evaluate(() => document.body.innerText).catch(() => '');
        const sqlM  = body2.match(/sql\d+\.infinityfree\.com/i);
        if (sqlM) { result.dbHost = sqlM[0]; logOk(`MySQL host: ${result.dbHost}`); break; }
      }
    }
  } catch (err) {
    logWarn(`Scraping gagal: ${err.message}. Lanjut dengan config manual.`);
  }

  await browser.close();
  return result;
}

// ── env.local.php ─────────────────────────────────────────────────────
function buildEnvLocalPhp(cfg) {
  return `<?php
$_ENV['CI_ENVIRONMENT']            = 'production';
$_ENV['app.baseURL']               = '${cfg.baseURL}';
$_ENV['app.indexPage']             = '';
$_ENV['app.forceGlobalSecureRequests'] = 'false';
$_ENV['app.CSPEnabled']            = 'false';
$_ENV['database.default.hostname'] = '${cfg.dbHost}';
$_ENV['database.default.database'] = '${cfg.dbName}';
$_ENV['database.default.username'] = '${cfg.dbUser}';
$_ENV['database.default.password'] = '${cfg.dbPass}';
$_ENV['database.default.DBDriver'] = 'MySQLi';
$_ENV['database.default.port']     = '3306';
$_ENV['database.default.charset']  = 'utf8mb4';
$_ENV['database.default.collation'] = 'utf8mb4_unicode_ci';
$_ENV['encryption.key']            = '${cfg.encKey}';
$_ENV['session.driver']            = 'CodeIgniter\\\\Session\\\\Handlers\\\\FileHandler';
$_ENV['session.cookieName']        = 'hrd_session';
$_ENV['session.expiration']        = '28800';
$_ENV['session.matchIP']           = 'false';
$_ENV['session.regenerateDestroy'] = 'true';
$_ENV['logger.threshold']          = '4';
`;
}

// ── FTP helpers ───────────────────────────────────────────────────────
async function createFtpClient(cfg) {
  const client = new ftp.Client();
  client.ftp.verbose = false;
  await client.access({
    host:     cfg.ftpHost,
    user:     cfg.ftpUser,
    password: cfg.ftpPass,
    secure:   false,
    timeout:  30000,
  });
  return client;
}

/** Walk local directory → flat list of file paths */
function walkLocalDir(dir) {
  const results = [];
  function walk(d) {
    const entries = fs.readdirSync(d, { withFileTypes: true });
    for (const e of entries) {
      const full = path.join(d, e.name);
      if (e.isDirectory()) walk(full);
      else results.push(full);
    }
  }
  walk(dir);
  return results;
}

/**
 * Upload satu direktori ke remote.
 * - Per-file retry dengan reconnect saat ETIMEDOUT / connection reset
 * - Tidak pakai uploadFromDir agar bisa reconnect di tengah jalan
 */
async function uploadDirRobust(cfg, localDir, remoteBase) {
  const allFiles = walkLocalDir(localDir);
  const total    = allFiles.length;
  let uploaded   = 0;
  let failed     = 0;
  const ensured  = new Set(); // dir paths yang sudah dicreate di session ini

  let client = await createFtpClient(cfg);

  async function ensureRemoteDir(dir) {
    if (!ensured.has(dir)) {
      await client.ensureDir(dir);
      ensured.add(dir);
    } else {
      await client.cd(dir);
    }
  }

  for (const localFile of allFiles) {
    const rel        = path.relative(localDir, localFile).replace(/\\/g, '/');
    const remotePath = `${remoteBase}/${rel}`;
    const remoteDir  = remotePath.substring(0, remotePath.lastIndexOf('/'));
    const remoteName = path.basename(remotePath);

    let success = false;
    for (let attempt = 1; attempt <= 4 && !success; attempt++) {
      try {
        await ensureRemoteDir(remoteDir);
        await client.uploadFrom(localFile, remoteName);
        success = true;
        uploaded++;
      } catch (err) {
        if (attempt < 4) {
          process.stdout.write(`\r  ${C.yellow}⚠ Reconnect (${attempt}/3)... ${rel.slice(-45).padEnd(46)}${C.reset}`);
          try { client.close(); } catch {}
          await delay(2000 * attempt);
          client = await createFtpClient(cfg);
          ensured.clear(); // reset cache setelah reconnect
        } else {
          failed++;
          process.stdout.write(`\r  ${C.red}✗ SKIP: ${rel.slice(-50).padEnd(50)}${C.reset}\n`);
        }
      }
    }

    if (success) {
      const short = rel.length > 52 ? '…' + rel.slice(-51) : rel;
      process.stdout.write(`\r  ${C.dim}📦 ${String(uploaded).padStart(4)}/${total}  ${short.padEnd(54)}${C.reset}`);
    }
  }

  process.stdout.write('\n');
  try { client.close(); } catch {}
  return { uploaded, failed };
}

// ── FTP upload (semua fase) ───────────────────────────────────────────
async function runFtpUpload(cfg) {
  log('🔌', 'Menghubungkan ke FTP server...', C.cyan);
  let testClient;
  try {
    testClient = await createFtpClient(cfg);
  } catch (err) {
    throw new Error(`Gagal konek FTP (${cfg.ftpHost}): ${err.message}`);
  }
  logOk(`Terhubung ke FTP: ${cfg.ftpHost}`);

  // ── 1. Buat folder writable/ ────────────────────────────────────
  log('📁', 'Membuat struktur folder writable/ ...', C.cyan);
  const dirs = [
    '/htdocs/writable', '/htdocs/writable/cache', '/htdocs/writable/logs',
    '/htdocs/writable/pdfs', '/htdocs/writable/session',
    '/htdocs/writable/tmp', '/htdocs/writable/uploads',
  ];
  for (const d of dirs) {
    try { await testClient.ensureDir(d); logOk(d); }
    catch { logInfo(`${d} mungkin sudah ada`); }
  }

  // ── 2. Upload public/ → htdocs/ ──────────────────────────────────
  log('📤', 'Upload file public/ → htdocs/ ...', C.cyan);
  await testClient.cd('/htdocs');
  const publicFiles = ['.htaccess', 'index.php', 'favicon.svg', 'favicon.ico', 'robots.txt'];
  for (const f of publicFiles) {
    const local = path.join(ROOT, 'public', f);
    if (!fs.existsSync(local)) { logInfo(`Skip (tidak ada): ${f}`); continue; }
    await testClient.uploadFrom(local, f);
    logOk(f);
  }
  testClient.close();

  // ── 3. Upload app/ ─────────────────────────────────────────────
  log('📤', 'Upload app/ → htdocs/app/ ...', C.cyan);
  console.log(C.dim + '  (Mohon tunggu...)' + C.reset);
  const r1 = await uploadDirRobust(cfg, path.join(ROOT, 'app'), '/htdocs/app');
  logOk(`app/ selesai — ${r1.uploaded} files${r1.failed ? `, ${r1.failed} gagal` : ''}`);

  // ── 4. Upload vendor/ — yang terbesar ───────────────────────────
  log('📤', 'Upload vendor/ → htdocs/vendor/ ...', C.cyan);
  console.log(C.dim + '  (Membutuhkan beberapa menit — koneksi akan reconnect otomatis jika putus)' + C.reset);
  const r2 = await uploadDirRobust(cfg, path.join(ROOT, 'vendor'), '/htdocs/vendor');
  logOk(`vendor/ selesai — ${r2.uploaded} files${r2.failed ? `, ${r2.failed} gagal` : ''}`);

  // ── 5. Upload env.local.php ──────────────────────────────────────
  log('📝', 'Upload env.local.php ...', C.cyan);
  const tmpEnvPath = path.join(__dirname, '_env_tmp.php');
  fs.writeFileSync(tmpEnvPath, buildEnvLocalPhp(cfg));
  const envClient = await createFtpClient(cfg);
  await envClient.cd('/htdocs');
  await envClient.uploadFrom(tmpEnvPath, 'env.local.php');
  envClient.close();
  fs.unlinkSync(tmpEnvPath);
  logOk('env.local.php diupload');

  log('✅', 'FTP upload SELESAI!', C.green);
}

// ── phpMyAdmin import ─────────────────────────────────────────────────
async function runDatabaseImport(cfg, savedState) {
  if (!fs.existsSync(DB_DUMP_PATH)) {
    logWarn('database_dump.sql tidak ditemukan — skip import database.');
    return;
  }

  const fileSizeMB = (fs.statSync(DB_DUMP_PATH).size / 1024 / 1024).toFixed(2);
  const pmaUrl = `https://${cfg.dbHost}/phpmyadmin/`;
  log('🌐', `Membuka phpMyAdmin: ${pmaUrl}`, C.cyan);

  const browser = await chromium.launch({ headless: false, args: ['--start-maximized'] });
  const context = savedState
    ? await browser.newContext({ storageState: savedState, viewport: null })
    : await browser.newContext({ viewport: null });
  const page = await context.newPage();
  await page.goto(pmaUrl, { waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {});

  // Login phpMyAdmin
  log('🔐', 'Login ke phpMyAdmin...', C.cyan);
  try {
    const userF = page.locator('input[name="pma_username"], input#input_username').first();
    const passF = page.locator('input[name="pma_password"], input#input_password').first();
    await userF.waitFor({ timeout: 15000 });
    await userF.fill(cfg.dbUser);
    await passF.fill(cfg.dbPass);
    await page.locator('input[type="submit"], button[type="submit"], #input_go').first().click();
    await page.waitForLoadState('networkidle', { timeout: 20000 });
    logOk('Login phpMyAdmin berhasil');
  } catch {
    logWarn('Auto-login gagal. Silakan login manual di browser.');
    console.log(`   Username : ${cfg.dbUser}`);
    await pressEnterToContinue('Tekan Enter setelah berhasil login ke phpMyAdmin...');
  }

  // Pilih database
  log('🗄️', `Memilih database: ${cfg.dbName}`, C.cyan);
  try {
    const dbLink = page.locator(`a:has-text("${cfg.dbName}")`).first();
    await dbLink.waitFor({ timeout: 10000 });
    await dbLink.click();
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    logOk(`Database ${cfg.dbName} dipilih`);
  } catch {
    logWarn(`Tidak bisa otomatis pilih database "${cfg.dbName}".`);
    console.log('   Klik nama database di panel kiri phpMyAdmin.');
    await pressEnterToContinue('Tekan Enter setelah memilih database...');
  }

  // Import
  log('📥', 'Membuka tab Import...', C.cyan);
  try {
    const importTab = page.locator(
      'a#topmenu_import, a[href*="import"], #topmenu a:has-text("Import")'
    ).first();
    await importTab.waitFor({ timeout: 10000 });
    await importTab.click();
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    logOk('Tab Import terbuka');
  } catch {
    logWarn('Klik tab "Import" secara manual.');
    await pressEnterToContinue('Tekan Enter setelah membuka tab Import...');
  }

  log('📤', `Upload database_dump.sql (${fileSizeMB} MB)...`, C.cyan);
  try {
    const fileInput = page.locator('input[type="file"]').first();
    await fileInput.waitFor({ timeout: 10000 });
    await fileInput.setInputFiles(DB_DUMP_PATH);
    logOk('File dipilih');
  } catch {
    logWarn(`Upload manual file: ${DB_DUMP_PATH}`);
    await pressEnterToContinue('Tekan Enter setelah memilih file SQL...');
  }

  log('▶️', 'Menjalankan import...', C.cyan);
  try {
    const goBtn = page.locator('input[value="Go"], button:has-text("Go"), #buttonGo').first();
    await goBtn.waitFor({ timeout: 10000 });
    await goBtn.click();
    await page.waitForSelector(
      '.alert-success, .success, [class*="success"], #result_query',
      { timeout: 120000 }
    );
    logOk('Database berhasil diimport! ✅');
  } catch {
    logWarn('Klik tombol "Go" secara manual di phpMyAdmin.');
    await pressEnterToContinue('Tekan Enter setelah import selesai...');
  }

  log('✅', 'Database import SELESAI!', C.green);
  await browser.close();
}

// ── Verifikasi ────────────────────────────────────────────────────────
async function runVerification(cfg) {
  log('🌐', `Membuka aplikasi: ${cfg.baseURL}`, C.cyan);
  const browser = await chromium.launch({ headless: false, args: ['--start-maximized'] });
  const page    = await (await browser.newContext({ viewport: null })).newPage();
  await page.goto(cfg.baseURL, { waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {});
  logInfo('Browser terbuka. Cek apakah halaman login muncul dengan benar.');
  await pressEnterToContinue('Tekan Enter untuk menutup dan selesai...');
  await browser.close();
}

// ── MAIN ──────────────────────────────────────────────────────────────
async function main() {
  console.clear();
  console.log(
    C.bold + C.cyan +
    '\n╔════════════════════════════════════════════════════╗' +
    '\n║   DEPLOY — Sistem Penilaian Probation              ║' +
    '\n║   InfinityFree Deployment Automation               ║' +
    '\n╚════════════════════════════════════════════════════╝' +
    C.reset
  );

  if (!fs.existsSync(SESSION_PATH)) {
    console.error(C.red + '\n❌ session.json tidak ditemukan! Jalankan dulu: node 1-login.js\n' + C.reset);
    process.exit(1);
  }
  logOk('session.json ditemukan');

  const sessionData = JSON.parse(fs.readFileSync(SESSION_PATH, 'utf8'));

  // ── Database dump ──────────────────────────────────────────────────
  if (!fs.existsSync(DB_DUMP_PATH)) {
    logWarn('database_dump.sql tidak ditemukan — database tidak akan diimport.');
  } else {
    const sizeMB = (fs.statSync(DB_DUMP_PATH).size / 1024 / 1024).toFixed(2);
    logOk(`database_dump.sql ditemukan (${sizeMB} MB)`);
  }

  // ── Konfigurasi ────────────────────────────────────────────────────
  logStep(1, 4, 'Konfigurasi koneksi');

  let saved = loadConfig();
  const isPlaceholder = v => !v || v.includes('XXX') || v === 'https://';
  const configComplete = saved.ftpHost && saved.ftpUser && saved.dbHost
    && saved.dbName && !isPlaceholder(saved.dbName)
    && saved.dbUser && !isPlaceholder(saved.baseURL) && saved.encKey;

  // Jika config belum ada, scrape dari InfinityFree
  if (!configComplete) {
    const scraped = await scrapeInfinityFreeConfig(sessionData);
    saved = { ...scraped, ...saved }; // saved values override scraped
  } else {
    logOk('deploy.config.json ditemukan — melewati scraping');
    logInfo(`FTP: ${saved.ftpUser}@${saved.ftpHost}`);
    logInfo(`DB:  ${saved.dbUser}@${saved.dbHost} / ${saved.dbName}`);
    logInfo(`URL: ${saved.baseURL}`);
  }

  // Load secrets (tidak disimpan ke git)
  const secrets = loadSecrets();
  const autoMode = !!(secrets.ftpPass && secrets.dbPass);

  if (autoMode) {
    log('🔑', 'secrets.json ditemukan — mode otomatis (tidak perlu input password)', C.green);
  }

  rl = readline.createInterface({ input: process.stdin, output: process.stdout });

  if (!saved.ftpHost) saved.ftpHost = autoMode ? 'ftpupload.net' : await ask('FTP Host', 'ftpupload.net');
  if (!saved.ftpUser) saved.ftpUser = autoMode ? (secrets.ftpUser || '') : await ask('FTP Username (if0_XXXXX)', '');
  const ftpPass = secrets.ftpPass || await askPassword(`Password FTP (${saved.ftpUser})`);

  if (!saved.dbHost)  saved.dbHost  = autoMode ? (secrets.dbHost || 'sql105.infinityfree.com') : await ask('MySQL Hostname', 'sql105.infinityfree.com');
  if (!saved.dbName)  saved.dbName  = autoMode ? (secrets.dbName || saved.ftpUser || '') : await ask('Database Name', saved.ftpUser || '');
  if (!saved.dbUser)  saved.dbUser  = autoMode ? (secrets.dbUser || saved.ftpUser || '') : await ask('DB Username', saved.ftpUser || '');
  const dbPass = secrets.dbPass || await askPassword(`Password MySQL (${saved.dbUser})`);

  if (!saved.baseURL) saved.baseURL = autoMode ? (secrets.baseURL || '') : await ask('Base URL app (dengan trailing slash)', 'https://');
  if (!saved.encKey)  saved.encKey  = secrets.encKey || saved.encKey || crypto.randomBytes(32).toString('hex');

  const cfg = { ...saved, ftpPass, dbPass };
  saveConfig(cfg);
  logOk('Konfigurasi disimpan ke deploy.config.json (tanpa password)');

  // ── LANGKAH 2: FTP Upload ──────────────────────────────────────────
  logStep(2, 4, 'Upload file via FTP (auto-reconnect on timeout)');
  await runFtpUpload(cfg);

  // ── LANGKAH 3: Database Import ─────────────────────────────────────
  logStep(3, 4, 'Import database via phpMyAdmin');
  await runDatabaseImport(cfg, sessionData);

  // ── LANGKAH 4: Verifikasi ──────────────────────────────────────────
  logStep(4, 4, 'Verifikasi aplikasi');
  await runVerification(cfg);

  rl.close();

  console.log(
    '\n' + C.bold + C.green +
    '╔════════════════════════════════════════════════════╗\n' +
    '║   ✅  DEPLOYMENT SELESAI!                          ║\n' +
    '╚════════════════════════════════════════════════════╝' +
    C.reset
  );
  console.log(`\n  URL Aplikasi : ${C.cyan}${cfg.baseURL}${C.reset}`);
  console.log(
    C.dim +
    '\n  Catatan:\n' +
    '  · Fitur PDF menggunakan DomPDF (bukan Word COM) di server\n' +
    '  · Jika ada error, cek writable/logs/ via FTP\n' +
    C.reset
  );
}

main().catch(err => {
  console.error('\n' + C.red + '❌ Error: ' + err.message + C.reset);
  if (rl) { try { rl.close(); } catch {} }
  process.exit(1);
});
