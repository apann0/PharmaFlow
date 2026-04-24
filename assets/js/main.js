/**
 * Pharmacy Management System – Main JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
  // ─── Sidebar Toggle ────────────────────────────────────
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('main-content');
  const toggleBtn = document.getElementById('sidebar-toggle');
  const overlay = document.getElementById('sidebar-overlay');

  function toggleSidebar() {
    if (window.innerWidth <= 768) {
      sidebar?.classList.toggle('show');
      overlay?.classList.toggle('show');
    } else {
      sidebar?.classList.toggle('hidden');
      mainContent?.classList.toggle('expanded');
    }
  }

  toggleBtn?.addEventListener('click', toggleSidebar);
  overlay?.addEventListener('click', () => {
    sidebar?.classList.remove('show');
    overlay?.classList.remove('show');
  });

  // ─── Auto-dismiss flash messages ───────────────────────
  document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(-8px)';
      setTimeout(() => el.remove(), 300);
    }, 4000);
  });

  // ─── Delete confirmation modals ────────────────────────
  document.querySelectorAll('[data-delete-url]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const url = btn.dataset.deleteUrl;
      const name = btn.dataset.deleteName || 'this item';
      const modal = document.getElementById('delete-modal');
      const form = document.getElementById('delete-form');
      const label = document.getElementById('delete-item-name');
      if (modal && form) {
        form.action = url;
        if (label) label.textContent = name;
        modal.classList.add('show');
      }
    });
  });

  document.getElementById('delete-cancel')?.addEventListener('click', () => {
    document.getElementById('delete-modal')?.classList.remove('show');
  });

  document.getElementById('delete-modal')?.addEventListener('click', (e) => {
    if (e.target.id === 'delete-modal') e.target.classList.remove('show');
  });

  // ─── Auto-calc total on finance forms ──────────────────
  const qtyInput = document.getElementById('quantity');
  const priceInput = document.getElementById('unit_price');
  const totalDisplay = document.getElementById('total_display');

  function calcTotal() {
    const qty = parseFloat(qtyInput?.value) || 0;
    const price = parseFloat(priceInput?.value) || 0;
    const total = qty * price;
    if (totalDisplay) {
      totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }
  }

  qtyInput?.addEventListener('input', calcTotal);
  priceInput?.addEventListener('input', calcTotal);

  // ─── Medicine select auto-fill price ───────────────────
  const medSelect = document.getElementById('medicine_id');
  if (medSelect && priceInput) {
    medSelect.addEventListener('change', () => {
      const opt = medSelect.options[medSelect.selectedIndex];
      const price = opt?.dataset.price;
      if (price) {
        priceInput.value = price;
        calcTotal();
      }
    });
  }
});
