/* ============================================================
   Caja — Shared JavaScript
   ============================================================ */

'use strict';

/* ── Toast helper ────────────────────────────────────────── */
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill' };
  const colors = { success: '#059669', danger: '#dc2626', warning: '#d97706' };

  const id = 'toast-' + Date.now();
  const html = `
    <div id="${id}" class="toast align-items-center text-white border-0 mb-2" role="alert" aria-live="assertive"
         style="background:${colors[type] || colors.success}; min-width:260px;">
      <div class="d-flex align-items-center p-3 gap-2">
        <i class="bi ${icons[type] || icons.success} fs-5"></i>
        <div class="me-auto">${message}</div>
        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast"></button>
      </div>
    </div>`;

  container.insertAdjacentHTML('beforeend', html);
  const el = document.getElementById(id);
  const toast = new bootstrap.Toast(el, { delay: 3500 });
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ── AJAX helper ─────────────────────────────────────────── */
async function apiRequest(action, data = {}) {
  const body = new FormData();
  body.append('action', action);
  for (const [k, v] of Object.entries(data)) body.append(k, v);

  const res = await fetch('api.php', { method: 'POST', body });
  if (!res.ok) throw new Error('Error de red: ' + res.status);
  return res.json();
}

/* ── Sidebar toggle (móvil) ──────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebar-overlay');
  const btnToggle = document.getElementById('btn-sidebar-toggle');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('open');
    document.body.style.overflow = '';
  }

  btnToggle?.addEventListener('click', openSidebar);
  overlay?.addEventListener('click', closeSidebar);

  // Cerrar con Escape
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
});

/* ── Confirm delete modal ────────────────────────────────── */
let _deleteId = null;

function confirmDelete(id, descripcion) {
  _deleteId = id;
  const el = document.getElementById('delete-desc');
  if (el) el.textContent = descripcion;
  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmDelete'));
  modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btn-confirm-delete')?.addEventListener('click', async () => {
    if (!_deleteId) return;

    const btn = document.getElementById('btn-confirm-delete');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Eliminando…';

    try {
      const res = await apiRequest('eliminar', { id: _deleteId });
      if (res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('modalConfirmDelete')).hide();
        showToast('Transacción eliminada.');
        // Remove row from table
        document.getElementById('row-' + _deleteId)?.remove();
        // Update counter
        const counter = document.getElementById('result-count');
        if (counter) {
          const n = parseInt(counter.dataset.total) - 1;
          counter.dataset.total = n;
          counter.textContent = n + ' resultado' + (n !== 1 ? 's' : '');
        }
      } else {
        showToast(res.error || 'No se pudo eliminar.', 'danger');
      }
    } catch {
      showToast('Error de conexión.', 'danger');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-trash me-1"></i>Eliminar';
      _deleteId = null;
    }
  });
});

/* ── Edit transaction modal ──────────────────────────────── */
let CATEGORIAS_CACHE = [];

async function openEditModal(id) {
  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEdit'));
  modal.show();

  const body = document.getElementById('edit-modal-body');
  body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

  try {
    const res = await apiRequest('obtener', { id });
    if (!res.ok) throw new Error(res.error);

    // Load categories if not cached
    if (!CATEGORIAS_CACHE.length) {
      const catRes = await apiRequest('categorias_todas');
      CATEGORIAS_CACHE = catRes.data || [];
    }

    renderEditForm(res.data, CATEGORIAS_CACHE);
  } catch (e) {
    body.innerHTML = `<div class="alert alert-danger">${e.message || 'Error al cargar.'}</div>`;
  }
}

function renderEditForm(t, cats) {
  const body = document.getElementById('edit-modal-body');
  const ingCats = cats.filter(c => c.tipo === 'ingreso');
  const egrCats = cats.filter(c => c.tipo === 'egreso');

  const optGroup = (list, selectedId) => list.map(c =>
    `<option value="${c.id}" ${String(c.id) === String(selectedId) ? 'selected' : ''}>${c.nombre}</option>`
  ).join('');

  body.innerHTML = `
    <input type="hidden" id="edit-id" value="${t.id}">
    <div class="mb-3">
      <label class="form-label">Tipo</label>
      <select class="form-select" id="edit-tipo">
        <option value="ingreso" ${t.tipo === 'ingreso' ? 'selected' : ''}>💰 Ingreso</option>
        <option value="egreso"  ${t.tipo === 'egreso'  ? 'selected' : ''}>💸 Egreso</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Categoría</label>
      <select class="form-select" id="edit-categoria">
        ${t.tipo === 'ingreso' ? optGroup(ingCats, t.categoria_id) : optGroup(egrCats, t.categoria_id)}
      </select>
    </div>
    <div class="row g-3 mb-2">
      <div class="col-6">
        <label class="form-label">Precio unitario (Bs)</label>
        <input type="number" class="form-control" id="edit-monto" value="${t.monto}" step="0.01" min="0.01" oninput="calcEditTotal()">
      </div>
      <div class="col-6">
        <label class="form-label">Cantidad</label>
        <input type="number" class="form-control" id="edit-cantidad" value="${t.cantidad}" step="0.001" min="0.001" oninput="calcEditTotal()">
      </div>
    </div>
    <div id="edit-total-preview" class="mb-3">
      <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:8px;padding:.5rem .9rem;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:.78rem;font-weight:600;color:#166534;text-transform:uppercase;letter-spacing:.04em;">Total</span>
        <span id="edit-total-valor" style="font-size:1rem;font-weight:800;color:#059669;"></span>
      </div>
    </div>`;
    <div class="mb-3">
      <label class="form-label">Fecha</label>
      <input type="date" class="form-control" id="edit-fecha" value="${t.fecha}" max="${new Date().toISOString().split('T')[0]}">
    </div>
    <div class="mb-1">
      <label class="form-label">Detalles <span class="text-muted fw-normal" style="font-size:.75rem">(opcional)</span></label>
      <textarea class="form-control" id="edit-detalles" rows="2" style="resize:none" placeholder="Ej: 2kg de arroz…">${t.detalles || ''}</textarea>
    </div>`;

  // Update categories when tipo changes
  document.getElementById('edit-tipo').addEventListener('change', function() {
    const list = this.value === 'ingreso' ? ingCats : egrCats;
    document.getElementById('edit-categoria').innerHTML = optGroup(list, '');
  });

  // Show initial total
  calcEditTotal();
}

function calcEditTotal() {
  const precio   = parseFloat(document.getElementById('edit-monto')?.value)   || 0;
  const cantidad = parseFloat(document.getElementById('edit-cantidad')?.value) || 0;
  const el = document.getElementById('edit-total-valor');
  if (el && precio > 0 && cantidad > 0) {
    el.textContent = 'Bs ' + (precio * cantidad).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('btn-save-edit')?.addEventListener('click', async () => {
    const id       = document.getElementById('edit-id')?.value;
    const tipo     = document.getElementById('edit-tipo')?.value;
    const catId    = document.getElementById('edit-categoria')?.value;
    const monto    = document.getElementById('edit-monto')?.value;
    const cantidad = document.getElementById('edit-cantidad')?.value;
    const fecha    = document.getElementById('edit-fecha')?.value;
    const detalles = document.getElementById('edit-detalles')?.value ?? '';

    if (!id || !tipo || !catId || !monto || !cantidad || !fecha) {
      showToast('Completa todos los campos.', 'warning'); return;
    }

    const btn = document.getElementById('btn-save-edit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando…';

    try {
      const res = await apiRequest('actualizar', { id, tipo, categoria_id: catId, monto, cantidad, detalles, fecha });
      if (res.ok) {
        bootstrap.Modal.getInstance(document.getElementById('modalEdit')).hide();
        showToast('Transacción actualizada.');
        setTimeout(() => location.reload(), 600);
      } else {
        showToast(res.error || 'No se pudo guardar.', 'danger');
      }
    } catch {
      showToast('Error de conexión.', 'danger');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar cambios';
    }
  });
});
