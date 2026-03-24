<?php
require_once 'config.php';
requireLogin();

$db = getDB();

$errores = [];
$exito   = false;
$datos   = ['tipo' => 'ingreso', 'categoria_id' => '', 'monto' => '', 'cantidad' => '1', 'detalles' => '', 'fecha' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo         = $_POST['tipo']         ?? '';
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $monto        = $_POST['monto']        ?? '';
    $cantidad     = $_POST['cantidad']     ?? '';
    $detalles     = trim($_POST['detalles'] ?? '');
    $fecha        = $_POST['fecha']        ?? '';

    $datos = compact('tipo', 'categoria_id', 'monto', 'cantidad', 'detalles', 'fecha');

    if (!in_array($tipo, ['ingreso', 'egreso'], true)) $errores[] = 'Selecciona un tipo válido.';
    if ($categoria_id <= 0) {
        $errores[] = 'Selecciona una categoría.';
    } else {
        $st = $db->prepare('SELECT id FROM categorias WHERE id = ? AND tipo = ?');
        $st->execute([$categoria_id, $tipo]);
        if (!$st->fetch()) $errores[] = 'Categoría no válida para el tipo seleccionado.';
    }
    $montoF = filter_var(str_replace(',', '.', $monto), FILTER_VALIDATE_FLOAT);
    if (!$montoF || $montoF <= 0) $errores[] = 'El monto debe ser mayor a cero.';

    $cantidadF = filter_var(str_replace(',', '.', $cantidad), FILTER_VALIDATE_FLOAT);
    if (!$cantidadF || $cantidadF <= 0) $errores[] = 'La cantidad debe ser mayor a cero.';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ||
        !checkdate((int)substr($fecha,5,2),(int)substr($fecha,8,2),(int)substr($fecha,0,4))) {
        $errores[] = 'La fecha no es válida.';
    }

    if (empty($errores)) {
        $stmt = $db->prepare('INSERT INTO transacciones (usuario_id,tipo,categoria_id,monto,cantidad,detalles,fecha) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$_SESSION['usuario_id'], $tipo, $categoria_id, round($montoF,2), round($cantidadF,3), $detalles ?: null, $fecha]);
        $exito = true;
        $datos = ['tipo' => 'ingreso', 'categoria_id' => '', 'monto' => '', 'cantidad' => '1', 'detalles' => '', 'fecha' => date('Y-m-d')];
    }
}

$titulo = 'Nueva transacción';
require '_layout.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Nueva transacción</h1>
    <p class="page-subtitle">Registra un ingreso o egreso</p>
  </div>
</div>

<?php if ($exito): ?>
  <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><strong>¡Listo!</strong> Transacción registrada correctamente.</div>
  </div>
<?php endif; ?>

<?php if ($errores): ?>
  <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
    <div>
      <?php foreach ($errores as $e): ?>
        <div>• <?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-6">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-plus-circle me-2"></i>Datos de la transacción
      </div>
      <div class="card-body">
        <form method="POST" id="formTransaccion" novalidate>

          <!-- Tipo -->
          <input type="hidden" name="tipo" id="tipo" value="<?= h($datos['tipo']) ?>">
          <div class="mb-4">
            <label class="form-label d-block mb-2">TIPO DE TRANSACCIÓN</label>
            <div class="tipo-selector">
              <div class="tipo-option <?= $datos['tipo'] === 'ingreso' ? 'selected-income' : '' ?>"
                   id="opt-ingreso" onclick="setTipo('ingreso')">
                <span class="tipo-icon">💰</span>
                <span class="tipo-label">Ingreso</span>
              </div>
              <div class="tipo-option <?= $datos['tipo'] === 'egreso' ? 'selected-expense' : '' ?>"
                   id="opt-egreso" onclick="setTipo('egreso')">
                <span class="tipo-icon">💸</span>
                <span class="tipo-label">Egreso</span>
              </div>
            </div>
          </div>

          <!-- Categoría -->
          <div class="mb-3">
            <label for="categoria_id" class="form-label">CATEGORÍA</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-tag text-muted"></i>
              </span>
              <select class="form-select border-start-0" name="categoria_id" id="categoria_id" required
                      style="border-radius:0 8px 8px 0">
              </select>
            </div>
          </div>

          <!-- Monto / Cantidad -->
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label for="monto" class="form-label">MONTO (Bs)</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted fw-600" style="font-size:.85rem">Bs</span>
                <input type="number" class="form-control border-start-0" name="monto" id="monto"
                       step="0.01" min="0.01" placeholder="0.00"
                       value="<?= h($datos['monto']) ?>" required
                       style="border-radius:0 8px 8px 0">
              </div>
            </div>
            <div class="col-6">
              <label for="cantidad" class="form-label">CANTIDAD</label>
              <input type="number" class="form-control" name="cantidad" id="cantidad"
                     step="0.001" min="0.001" placeholder="1.000"
                     value="<?= h($datos['cantidad']) ?>" required>
            </div>
          </div>

          <!-- Detalles (opcional) -->
          <div class="mb-3">
            <label for="detalles" class="form-label">
              DETALLES
              <span class="text-muted fw-normal ms-1" style="font-size:.72rem;text-transform:none;letter-spacing:0">(opcional)</span>
            </label>
            <textarea class="form-control" name="detalles" id="detalles"
                      rows="2" placeholder="Ej: 2kg de arroz, 1 botella aceite…"
                      style="resize:none"><?= h($datos['detalles']) ?></textarea>
          </div>

          <!-- Fecha -->
          <div class="mb-4">
            <label for="fecha" class="form-label">FECHA</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-calendar3 text-muted"></i>
              </span>
              <input type="date" class="form-control border-start-0" name="fecha" id="fecha"
                     value="<?= h($datos['fecha']) ?>" required
                     max="<?= date('Y-m-d') ?>"
                     style="border-radius:0 8px 8px 0">
            </div>
          </div>

          <hr class="my-3">

          <button type="submit" class="btn btn-success w-100 py-2">
            <i class="bi bi-check-lg me-2"></i>Registrar transacción
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
const SELECTED_CAT = <?= json_encode((string)$datos['categoria_id']) ?>;

async function loadCategorias(tipo, selectedId = '') {
  const sel = document.getElementById('categoria_id');
  sel.innerHTML = '<option value="">Cargando…</option>';
  sel.disabled = true;

  try {
    const form = new FormData();
    form.append('action', 'categorias');
    form.append('tipo', tipo);
    const res = await fetch('api.php', { method: 'POST', body: form });
    const json = await res.json();

    if (json.ok && json.data.length) {
      const target = selectedId || SELECTED_CAT;
      sel.innerHTML = json.data.map(c =>
        `<option value="${c.id}" ${String(c.id) === target ? 'selected' : ''}>${c.nombre}</option>`
      ).join('');
      // If nothing was pre-selected, pick the first option automatically
      if (!target) sel.selectedIndex = 0;
    } else {
      sel.innerHTML = '<option value="">Sin categorías disponibles</option>';
    }
  } catch {
    sel.innerHTML = '<option value="">Error al cargar</option>';
  } finally {
    sel.disabled = false;
  }
}

function setTipo(tipo) {
  document.getElementById('tipo').value = tipo;

  const optIng = document.getElementById('opt-ingreso');
  const optEgr = document.getElementById('opt-egreso');

  optIng.className = 'tipo-option' + (tipo === 'ingreso' ? ' selected-income'  : '');
  optEgr.className = 'tipo-option' + (tipo === 'egreso'  ? ' selected-expense' : '');

  loadCategorias(tipo);
}

// Init
loadCategorias(document.getElementById('tipo').value || 'ingreso');
</script>

<?php require '_layout_end.php'; ?>
