<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// Cargar todas las categorías para el JS
$stmt = $db->query('SELECT id, nombre, tipo FROM categorias ORDER BY tipo, nombre');
$categorias_todas = $stmt->fetchAll();

$errores  = [];
$exito    = false;
$datos    = [
    'tipo'         => 'ingreso',
    'categoria_id' => '',
    'monto'        => '',
    'cantidad'     => '1',
    'fecha'        => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo         = $_POST['tipo']         ?? '';
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $monto        = $_POST['monto']        ?? '';
    $cantidad     = $_POST['cantidad']     ?? '';
    $fecha        = $_POST['fecha']        ?? '';

    $datos = compact('tipo', 'categoria_id', 'monto', 'cantidad', 'fecha');

    // Validaciones
    if (!in_array($tipo, ['ingreso', 'egreso'], true)) {
        $errores[] = 'Selecciona un tipo válido (Ingreso o Egreso).';
    }

    if ($categoria_id <= 0) {
        $errores[] = 'Selecciona una categoría.';
    } else {
        $stmtCat = $db->prepare('SELECT id FROM categorias WHERE id = ? AND tipo = ?');
        $stmtCat->execute([$categoria_id, $tipo]);
        if (!$stmtCat->fetch()) {
            $errores[] = 'La categoría no corresponde al tipo seleccionado.';
        }
    }

    $montoFloat = filter_var(str_replace(',', '.', $monto), FILTER_VALIDATE_FLOAT);
    if ($montoFloat === false || $montoFloat <= 0) {
        $errores[] = 'El monto debe ser un número mayor a cero.';
    }

    $cantidadFloat = filter_var(str_replace(',', '.', $cantidad), FILTER_VALIDATE_FLOAT);
    if ($cantidadFloat === false || $cantidadFloat <= 0) {
        $errores[] = 'La cantidad debe ser un número mayor a cero.';
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate(
        (int)substr($fecha, 5, 2),
        (int)substr($fecha, 8, 2),
        (int)substr($fecha, 0, 4)
    )) {
        $errores[] = 'La fecha no es válida.';
    }

    if (empty($errores)) {
        $stmt = $db->prepare('
            INSERT INTO transacciones (usuario_id, tipo, categoria_id, monto, cantidad, fecha)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $_SESSION['usuario_id'],
            $tipo,
            $categoria_id,
            round($montoFloat, 2),
            round($cantidadFloat, 3),
            $fecha,
        ]);
        $exito = true;
        $datos = ['tipo' => 'ingreso', 'categoria_id' => '', 'monto' => '', 'cantidad' => '1', 'fecha' => date('Y-m-d')];
    }
}

$titulo = 'Nueva transacción';
require '_layout.php';
?>

<div class="section-title">➕ Nueva transacción</div>

<?php if ($exito): ?>
  <div class="alert alert-success">✅ Transacción registrada correctamente.</div>
<?php endif; ?>

<?php if ($errores): ?>
  <div class="alert alert-error">
    <?php foreach ($errores as $e): ?>
      <div>• <?= h($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card">
  <form method="POST" action="" id="formTransaccion" novalidate>

    <!-- Tipo -->
    <input type="hidden" name="tipo" id="tipo" value="<?= h($datos['tipo']) ?>">
    <div class="form-group">
      <label>Tipo de transacción</label>
      <div style="display:flex;gap:.75rem;">
        <button type="button" id="btn-ingreso" onclick="setTipo('ingreso')"
          style="flex:1;padding:.6rem;border-radius:8px;border:2px solid;cursor:pointer;font-size:.95rem;font-weight:600;transition:.15s;">
          💰 Ingreso
        </button>
        <button type="button" id="btn-egreso" onclick="setTipo('egreso')"
          style="flex:1;padding:.6rem;border-radius:8px;border:2px solid;cursor:pointer;font-size:.95rem;font-weight:600;transition:.15s;">
          💸 Egreso
        </button>
      </div>
    </div>

    <!-- Categoría -->
    <div class="form-group">
      <label for="categoria_id">Categoría</label>
      <select name="categoria_id" id="categoria_id" required>
        <option value="">— Selecciona una categoría —</option>
      </select>
    </div>

    <!-- Monto y Cantidad -->
    <div class="form-row">
      <div class="form-group">
        <label for="monto">Monto (Bs)</label>
        <input type="number" name="monto" id="monto"
               step="0.01" min="0.01"
               placeholder="0.00"
               value="<?= h($datos['monto']) ?>" required>
      </div>
      <div class="form-group">
        <label for="cantidad">Cantidad</label>
        <input type="number" name="cantidad" id="cantidad"
               step="0.001" min="0.001"
               placeholder="1.000"
               value="<?= h($datos['cantidad']) ?>" required>
      </div>
    </div>

    <!-- Fecha -->
    <div class="form-group">
      <label for="fecha">Fecha</label>
      <input type="date" name="fecha" id="fecha"
             value="<?= h($datos['fecha']) ?>" required
             max="<?= date('Y-m-d') ?>">
    </div>

    <button type="submit" class="btn btn-success btn-block">
      Registrar transacción
    </button>
  </form>
</div>

<!-- Datos de categorías para JS -->
<script>
const CATEGORIAS = <?= json_encode($categorias_todas, JSON_UNESCAPED_UNICODE) ?>;
const SELECCIONADA = <?= json_encode((string)$datos['categoria_id']) ?>;

function actualizarCategorias() {
  const tipo = document.querySelector('input[name="tipo"]:checked')?.value;
  const sel  = document.getElementById('categoria_id');
  sel.innerHTML = '<option value="">— Selecciona una categoría —</option>';

  CATEGORIAS.filter(c => c.tipo === tipo).forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = c.nombre;
    if (String(c.id) === SELECCIONADA) opt.selected = true;
    sel.appendChild(opt);
  });
}

function setTipo(tipo) {
  document.getElementById('tipo').value = tipo;
  actualizarCategorias();

  const btnIng = document.getElementById('btn-ingreso');
  const btnEgr = document.getElementById('btn-egreso');

  if (tipo === 'ingreso') {
    btnIng.style.cssText += 'background:#e6f4ea;color:#188038;border-color:#188038;';
    btnEgr.style.cssText += 'background:#fff;color:#5f6368;border-color:#dadce0;';
  } else {
    btnEgr.style.cssText += 'background:#fce8e6;color:#c5221f;border-color:#c5221f;';
    btnIng.style.cssText += 'background:#fff;color:#5f6368;border-color:#dadce0;';
  }
}

// Init
setTipo(document.getElementById('tipo').value || 'ingreso');
</script>

<?php require '_layout_end.php'; ?>
