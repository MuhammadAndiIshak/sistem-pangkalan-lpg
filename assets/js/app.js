/**
 * SISTEM INFORMASI PENGELOLAAN PANGKALAN GAS LPG KOTA PALU
 * Vanilla JS Application Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
  initMobileSidebar();
  initDeleteModal();
  initSalesCalculator();
  initFormSubmitState();
});

/**
 * 1. Mobile Sidebar Toggle & Drawer Auto-close
 */
function initMobileSidebar() {
  const toggleBtn = document.querySelector('.mobile-menu-btn');
  const sidebar = document.querySelector('.app-sidebar');
  const backdrop = document.querySelector('.sidebar-backdrop');
  const closeBtn = document.querySelector('.sidebar-close-btn');

  if (!sidebar) return;

  function openSidebar() {
    sidebar.classList.add('show');
    if (backdrop) backdrop.classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('show');
    if (backdrop) backdrop.classList.remove('show');
    document.body.style.overflow = '';
  }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (sidebar.classList.contains('show')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      closeSidebar();
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  // Menutup drawer otomatis setelah pengguna memilih menu di HP/Tablet
  sidebar.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function () {
      if (window.innerWidth < 1024) {
        closeSidebar();
      }
    });
  });

  // Tutup dengan tombol Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('show')) {
      closeSidebar();
    }
  });

  // Reset otomatis saat layar diubah ke ukuran desktop
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 1024 && sidebar.classList.contains('show')) {
      closeSidebar();
    }
  });
}

/**
 * 2. Toast Notification Engine
 */
function showToast(message, type = 'info', duration = 4500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;

  // Ikon SVG berdasarkan jenis notifikasi
  let iconSvg = '';
  if (type === 'success') {
    iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-success);flex-shrink:0"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
  } else if (type === 'danger') {
    iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-danger);flex-shrink:0"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
  } else if (type === 'warning') {
    iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-warning);flex-shrink:0"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
  } else {
    iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-info);flex-shrink:0"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
  }

  toast.innerHTML = `
    ${iconSvg}
    <div class="toast-body">${message}</div>
    <button type="button" class="toast-close" aria-label="Tutup">&times;</button>
  `;

  const closeBtn = toast.querySelector('.toast-close');
  closeBtn.addEventListener('click', () => removeToast(toast));

  container.appendChild(toast);

  if (duration > 0) {
    setTimeout(() => removeToast(toast), duration);
  }
}

function removeToast(toast) {
  toast.style.transition = 'opacity 200ms ease, transform 200ms ease';
  toast.style.opacity = '0';
  toast.style.transform = 'translateY(-8px)';
  setTimeout(() => {
    if (toast.parentNode) {
      toast.parentNode.removeChild(toast);
    }
  }, 220);
}

/**
 * 3. Custom Modal Konfirmasi Hapus Data
 */
function initDeleteModal() {
  const modalBackdrop = document.getElementById('deleteModal');
  const modalForm = document.getElementById('deleteModalForm');
  const modalItemName = document.getElementById('deleteModalItemName');
  const cancelBtn = document.getElementById('deleteModalCancel');

  if (!modalBackdrop || !modalForm) return;

  function closeModal() {
    modalBackdrop.classList.remove('show');
    document.body.style.overflow = '';
  }

  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-delete-url]');
    if (trigger) {
      e.preventDefault();
      const deleteUrl = trigger.getAttribute('data-delete-url');
      const itemName = trigger.getAttribute('data-item-name') || 'data ini';

      modalForm.setAttribute('action', deleteUrl);
      if (modalItemName) {
        modalItemName.textContent = itemName;
      }

      modalBackdrop.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  });

  if (cancelBtn) {
    cancelBtn.addEventListener('click', closeModal);
  }

  modalBackdrop.addEventListener('click', function (e) {
    if (e.target === modalBackdrop) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modalBackdrop.classList.contains('show')) {
      closeModal();
    }
  });
}

/**
 * 4. Perhitungan Penjualan Real-time & Validasi Stok
 */
function initSalesCalculator() {
  const selectProduk = document.getElementById('penjualan_produk_id');
  const inputJumlah = document.getElementById('penjualan_jumlah');
  const inputHarga = document.getElementById('penjualan_harga');
  const inputTotal = document.getElementById('penjualan_total');
  const displayTotal = document.getElementById('display_penjualan_total');
  const stockInfo = document.getElementById('produk_stock_info');
  const submitBtn = document.getElementById('btn_submit_penjualan');
  const errorAlert = document.getElementById('stock_warning_alert');

  if (!selectProduk || !inputJumlah || !inputHarga) return;

  function formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
  }

  function updatePricing() {
    const selectedOption = selectProduk.options[selectProduk.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
      if (stockInfo) stockInfo.textContent = '';
      if (errorAlert) errorAlert.style.display = 'none';
      if (inputHarga) inputHarga.value = '';
      if (inputTotal) inputTotal.value = '';
      if (displayTotal) displayTotal.textContent = 'Rp 0';
      return;
    }

    const defaultHarga = parseFloat(selectedOption.getAttribute('data-harga') || 0);
    const stokTersedia = parseInt(selectedOption.getAttribute('data-stok') || 0, 10);

    // Isi harga otomatis jika input kosong atau baru memilih produk
    if (!inputHarga.value || inputHarga.dataset.autofilled === 'true') {
      inputHarga.value = defaultHarga;
      inputHarga.dataset.autofilled = 'true';
    }

    if (stockInfo) {
      stockInfo.innerHTML = `Stok tersedia: <strong>${stokTersedia} tabung</strong>`;
    }

    recalcTotal(stokTersedia);
  }

  function recalcTotal(stokTersedia) {
    if (stokTersedia === undefined) {
      const selectedOption = selectProduk.options[selectProduk.selectedIndex];
      stokTersedia = selectedOption ? parseInt(selectedOption.getAttribute('data-stok') || 0, 10) : 0;
    }

    const jumlah = parseInt(inputJumlah.value || 0, 10);
    const harga = parseFloat(inputHarga.value || 0);
    const total = jumlah * harga;

    if (inputTotal) inputTotal.value = total;
    if (displayTotal) displayTotal.textContent = formatRupiah(total);

    // Validasi stok langsung di form (UX)
    if (jumlah > stokTersedia) {
      if (errorAlert) {
        errorAlert.style.display = 'block';
        errorAlert.textContent = `Jumlah melebihi stok tersedia (${stokTersedia} tabung)! Transaksi tidak dapat diproses.`;
      }
      if (submitBtn) submitBtn.disabled = true;
    } else {
      if (errorAlert) errorAlert.style.display = 'none';
      if (submitBtn) submitBtn.disabled = false;
    }
  }

  selectProduk.addEventListener('change', function () {
    if (inputHarga) inputHarga.dataset.autofilled = 'true';
    updatePricing();
  });

  inputHarga.addEventListener('input', function () {
    inputHarga.dataset.autofilled = 'false';
    recalcTotal();
  });

  inputJumlah.addEventListener('input', function () {
    recalcTotal();
  });

  // Jalankan kalkulasi inisial saat halaman dimuat jika ada nilai terpilih
  if (selectProduk.value) {
    updatePricing();
  }
}

/**
 * 5. Mencegah submit ganda pada tombol form
 */
function initFormSubmitState() {
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function () {
      const submitBtn = this.querySelector('button[type="submit"]:not([disabled])');
      if (submitBtn && !submitBtn.classList.contains('no-spin')) {
        submitBtn.dataset.originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
          <svg style="animation: spin 1s linear infinite; width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="2" x2="12" y2="6"></line>
            <line x1="12" y1="18" x2="12" y2="22"></line>
            <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
            <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
            <line x1="2" y1="12" x2="6" y2="12"></line>
            <line x1="18" y1="12" x2="22" y2="12"></line>
            <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
            <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
          </svg>
          Memproses...
        `;
      }
    });
  });
}
