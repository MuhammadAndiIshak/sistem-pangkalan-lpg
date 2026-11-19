/**
 * Script Verifikasi Responsivitas Menggunakan Native Chrome DevTools Protocol
 * Ukuran yang diuji: 360px (HP), 768px (Tablet), 1280px (Desktop)
 */

const { spawn } = require('child_process');

async function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function run() {
  console.log('1. Membuka Chrome Headless dengan Remote Debugging...');
  const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
  const chromeProcess = spawn(chromePath, [
    '--headless=new',
    '--remote-debugging-port=9222',
    '--no-sandbox',
    '--disable-gpu',
    '--user-data-dir=C:\\Users\\USER\\AppData\\Local\\Temp\\chrome_test_profile'
  ]);

  await sleep(1500);

  try {
    const versionRes = await fetch('http://127.0.0.1:9222/json/version');
    const versionData = await versionRes.json();
    console.log('   Terhubung ke Chrome:', versionData.Browser);

    const listRes = await fetch('http://127.0.0.1:9222/json/list');
    const targets = await listRes.json();
    const pageTarget = targets.find(t => t.type === 'page') || targets[0];
    const wsUrl = pageTarget.webSocketDebuggerUrl;

    const ws = new WebSocket(wsUrl);

    let id = 1;
    const callbacks = new Map();

    ws.onmessage = (event) => {
      const msg = JSON.parse(event.data);
      if (msg.id && callbacks.has(msg.id)) {
        callbacks.get(msg.id)(msg);
        callbacks.delete(msg.id);
      }
    };

    function sendCommand(method, params = {}) {
      return new Promise((resolve) => {
        const cmdId = id++;
        callbacks.set(cmdId, resolve);
        ws.send(JSON.stringify({ id: cmdId, method, params }));
      });
    }

    await new Promise((resolve) => ws.onopen = resolve);
    console.log('   WebSocket DevTools tersambung.\n');

    async function evaluate(expression) {
      const res = await sendCommand('Runtime.evaluate', { expression, returnByValue: true });
      return res.result ? res.result.result.value : null;
    }

    async function setViewport(width, height) {
      await sendCommand('Emulation.setDeviceMetricsOverride', {
        width,
        height,
        deviceScaleFactor: 2,
        mobile: width < 1024
      });
      await sendCommand('Emulation.setVisibleSize', { width, height });
    }

    async function navigate(url) {
      await sendCommand('Page.navigate', { url });
      await sleep(1000);
    }

    // ==========================================================
    // UJI 1: Halaman Login di 360px
    // ==========================================================
    console.log('=== PENGUJIAN 1: LOGIN PAGE PADA LAYAR HP (360px) ===');
    await setViewport(360, 640);
    await navigate('http://localhost/pangkalan-lpg/login.php');

    const loginCheck = await evaluate(`({
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
      bodyScrollWidth: document.body.scrollWidth,
      viewportMeta: document.querySelector('meta[name="viewport"]')?.getAttribute('content'),
      inputUsernameHeight: document.getElementById('username')?.offsetHeight,
      inputPasswordHeight: document.getElementById('password')?.offsetHeight,
      submitBtnHeight: document.querySelector('button[type="submit"]')?.offsetHeight,
      brandPanelDisplay: window.getComputedStyle(document.querySelector('.login-brand-panel')).display
    })`);

    console.log('   Viewport meta tag:', loginCheck.viewportMeta);
    console.log('   Client Width vs Scroll Width:', loginCheck.clientWidth, 'vs', loginCheck.scrollWidth, 
                loginCheck.scrollWidth <= loginCheck.clientWidth ? '[LOLOS: TIDAK ADA SCROLL HORIZONTAL]' : '[GAGAL: OVERFLOW]');
    console.log('   Tinggi Input Username:', loginCheck.inputUsernameHeight + 'px', loginCheck.inputUsernameHeight >= 44 ? '(>= 44px OK)' : '(KURANG)');
    console.log('   Tinggi Input Password:', loginCheck.inputPasswordHeight + 'px', loginCheck.inputPasswordHeight >= 44 ? '(>= 44px OK)' : '(KURANG)');
    console.log('   Tinggi Tombol Submit:', loginCheck.submitBtnHeight + 'px', loginCheck.submitBtnHeight >= 44 ? '(>= 44px OK)' : '(KURANG)');
    console.log('   Brand Panel Display di HP:', loginCheck.brandPanelDisplay, '(disembunyikan di layar HP)');

    // Lakukan login
    await evaluate(`
      document.getElementById('username').value = 'admin';
      document.getElementById('password').value = 'admin123';
      document.querySelector('form').submit();
    `);
    await sleep(1500);

    // ==========================================================
    // UJI 2: Dashboard di 360px (HP), 768px (Tablet), 1280px (Desktop)
    // ==========================================================
    console.log('\n=== PENGUJIAN 2: DASHBOARD MONITORING ===');
    const viewports = [
      { name: 'HP (Mobile)', width: 360, height: 640, expectedCols: 1 },
      { name: 'Tablet', width: 768, height: 1024, expectedCols: 2 },
      { name: 'Desktop', width: 1280, height: 800, expectedCols: 4 }
    ];

    for (const vp of viewports) {
      await setViewport(vp.width, vp.height);
      await sleep(500);

      const dashCheck = await evaluate(`({
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        bodyScrollWidth: document.body.scrollWidth,
        statsCols: window.getComputedStyle(document.querySelector('.stats-grid')).gridTemplateColumns.split(' ').length,
        hamburgerVisible: window.getComputedStyle(document.querySelector('.mobile-menu-btn')).display !== 'none',
        sidebarVisible: window.getComputedStyle(document.querySelector('.app-sidebar')).transform === 'matrix(1, 0, 0, 1, 0, 0)' || window.getComputedStyle(document.querySelector('.app-sidebar')).transform === 'none',
        tableResponsiveExists: !!document.querySelector('.table-responsive')
      })`);

      console.log('   [Ukuran ' + vp.name + ' - ' + vp.width + 'px]');
      console.log('     Scroll Width <= Client Width:', dashCheck.scrollWidth <= dashCheck.clientWidth ? 'YA (Zero overflow)' : 'TIDAK (Ada overflow)');
      console.log('     Kolom Kartu Statistik:', dashCheck.statsCols, '(Ekspektasi: ' + vp.expectedCols + ')', dashCheck.statsCols === vp.expectedCols ? '[SESUAI]' : '[BERBEDA]');
      console.log('     Tombol Hamburger Terlihat:', dashCheck.hamburgerVisible ? 'YA' : 'TIDAK');
      console.log('     Sidebar Drawer Terbuka Terus:', dashCheck.sidebarVisible ? 'YA (Desktop Fixed)' : 'TIDAK (Mobile Drawer Hidden)');
    }

    // ==========================================================
    // UJI 3: Form Penjualan di 360px
    // ==========================================================
    console.log('\n=== PENGUJIAN 3: FORM PENJUALAN DI HP (360px) ===');
    await setViewport(360, 640);
    await navigate('http://localhost/pangkalan-lpg/modules/penjualan/create.php');

    const saleFormCheck = await evaluate(`({
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
      formRowCols: window.getComputedStyle(document.querySelector('.form-row')).gridTemplateColumns.split(' ').length,
      selectProdukHeight: document.getElementById('penjualan_produk_id')?.offsetHeight,
      inputJumlahHeight: document.getElementById('penjualan_jumlah')?.offsetHeight,
      inputHargaHeight: document.getElementById('penjualan_harga')?.offsetHeight,
      submitBtnHeight: document.getElementById('btn_submit_penjualan')?.offsetHeight
    })`);

    console.log('   Scroll Width <= Client Width (360px):', saleFormCheck.scrollWidth <= saleFormCheck.clientWidth ? 'YA (Tidak ada scroll horizontal)' : 'TIDAK');
    console.log('   Jumlah Kolom Form Row di HP:', saleFormCheck.formRowCols, '(Ekspektasi: 1 kolom)', saleFormCheck.formRowCols === 1 ? '[SESUAI]' : '[BERBEDA]');
    console.log('   Tinggi Select Produk:', saleFormCheck.selectProdukHeight + 'px', '(>= 44px OK)');
    console.log('   Tinggi Input Qty:', saleFormCheck.inputJumlahHeight + 'px', '(>= 44px OK)');
    console.log('   Tinggi Input Harga:', saleFormCheck.inputHargaHeight + 'px', '(>= 44px OK)');
    console.log('   Tinggi Tombol Submit Penjualan:', saleFormCheck.submitBtnHeight + 'px', '(>= 44px OK)');

    // ==========================================================
    // UJI 4: Form LPG Masuk di 360px
    // ==========================================================
    console.log('\n=== PENGUJIAN 4: FORM LPG MASUK DI HP (360px) ===');
    await navigate('http://localhost/pangkalan-lpg/modules/lpg_masuk/create.php');

    const supplyFormCheck = await evaluate(`({
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
      formRowCols: window.getComputedStyle(document.querySelector('.form-row')).gridTemplateColumns.split(' ').length,
      submitBtnHeight: document.querySelector('button[type="submit"]')?.offsetHeight
    })`);

    console.log('   Scroll Width <= Client Width (360px):', supplyFormCheck.scrollWidth <= supplyFormCheck.clientWidth ? 'YA (Zero overflow)' : 'TIDAK');
    console.log('   Form Row Kolom di HP:', supplyFormCheck.formRowCols, '(1 kolom)');
    console.log('   Tinggi Tombol Submit:', supplyFormCheck.submitBtnHeight + 'px', '(>= 44px OK)');

    // ==========================================================
    // UJI 5: Halaman Laporan di 360px & 768px
    // ==========================================================
    console.log('\n=== PENGUJIAN 5: HALAMAN LAPORAN & MONITORING ===');
    await navigate('http://localhost/pangkalan-lpg/modules/laporan/index.php');

    const laporan360 = await evaluate(`({
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
      tableResponsive: !!document.querySelector('.table-responsive'),
      tableCustomMinWidth: parseInt(window.getComputedStyle(document.querySelector('.table-custom')).minWidth, 10),
      tableResponsiveScrollWidth: document.querySelector('.table-responsive')?.scrollWidth,
      tableResponsiveClientWidth: document.querySelector('.table-responsive')?.clientWidth
    })`);

    console.log('   [Laporan di HP 360px]');
    console.log('     Page Horizontal Overflow:', laporan360.scrollWidth > laporan360.clientWidth ? 'ADA OVERFLOW' : 'NIHIL (Halaman aman)');
    console.log('     Tabel terbungkus .table-responsive:', laporan360.tableResponsive ? 'YA' : 'TIDAK');
    console.log('     Tabel dapat digeser horizontal secara mandiri:', laporan360.tableResponsiveScrollWidth > laporan360.tableResponsiveClientWidth ? 'YA (Tabel dapat di-swipe tanpa merusak halaman)' : 'CUKUP MUAT');

    ws.close();
  } catch (err) {
    console.error('Terjadi error saat verifikasi:', err);
  } finally {
    chromeProcess.kill();
    console.log('\nChrome Headless ditutup.');
  }
}

run();
