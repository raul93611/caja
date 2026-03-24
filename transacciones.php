<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// ── Filtros ──────────────────────────────────────────────────
$filtro_tipo      = $_GET['tipo']        ?? '';
$filtro_cat       = (int)($_GET['categoria'] ?? 0);
$filtro_fecha_ini = $_GET['fecha_ini']   ?? '';
$filtro_fecha_fin = $_GET['fecha_fin']   ?? '';

// Página
$por_pagina = 20;
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$offset     = ($pagina - 1) * $por_pagina;

// ── Construir WHERE dinámico ─────────────────────────────────
$where  = ['1=1'];
$params = [];

if (in_array($filtro_tipo, ['ingreso', 'egreso'], true)) {
    $where[]  = 't.tipo = ?';
    $params[] = $filtro_tipo;
}
if ($filtro_cat > 0) {
    $where[]  = 't.categoria_id = ?';
    $params[] = $filtro_cat;
}
if ($filtro_fecha_ini !== '') {
    $where[]  = 't.fecha >= ?';
    $params[] = $filtro_fecha_ini;
}
if ($filtro_fecha_fin !== '') {
    $where[]  = 't.fecha <= ?';
    $params[] = $filtro_fecha_fin;
}

$sqlWhere = implode(' AND ', $where);

// Total de registros
$stmtCount = $db->prepare("SELECT COUNT(*) FROM transacciones t WHERE $sqlWhere");
$stmtCount->execute($params);
$total     = (int)$stmtCount->fetchColumn();
$paginas   = max(1, (int)ceil($total / $por_pagina));

// Filas de la página
$stmtRows = $db->prepare("
    SELECT t.id, t.tipo, t.monto, t.cantidad, t.fecha, t.creado_en,
           c.nombre AS categoria
    FROM transacciones t
    JOIN categorias c ON c.id = t.categoria_id
    WHERE $sqlWhere
    ORDER BY t.fecha DESC, t.creado_en DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmtRows->execute($params);
$transacciones = $stmtRows->fetchAll();

// Categorías para filtro
$cats = $db->query('SELECT id, nombre, tipo FROM categorias ORDER BY tipo, nombre')->fetchAll();

// Función para construir URL de paginación conservando filtros
function urlPagina(int $p): string {
    $q = $_GET;
    $q['pagina'] = $p;
    return '?' . http_build_query($q);
}

$titulo = 'Transacciones';
require '_layout.php';
?>

<div class="section-title">📋 Transacciones</div>

<!-- Filtros -->
<div class="card">
  <form method="GET" action="" id="formFiltros">
    <div class="filtros-form">

      <div class="form-group" style="flex:1;min-width:100px;">
        <label>Tipo</label>
        <select name="tipo" onchange="this.form.submit()">
          <option value="">Todos</option>
          <option value="ingreso" <?= $filtro_tipo === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
          <option value="egreso"  <?= $filtro_tipo === 'egreso'  ? 'selected' : '' ?>>Egreso</option>
        </select>
      </div>

      <div class="form-group" style="flex:2;min-width:150px;">
        <label>Categoría</label>
        <select name="categoria" onchange="this.form.submit()">
          <option value="">Todas</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filtro_cat === (int)$c['id'] ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?> (<?= $c['tipo'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="flex:1;min-width:130px;">
        <label>Desde</label>
        <input type="date" name="fecha_ini" value="<?= h($filtro_fecha_ini) ?>"
               onchange="this.form.submit()">
      </div>

      <div class="form-group" style="flex:1;min-width:130px;">
        <label>Hasta</label>
        <input type="date" name="fecha_fin" value="<?= h($filtro_fecha_fin) ?>"
               onchange="this.form.submit()">
      </div>

      <div class="form-group" style="flex:0;min-width:auto;align-self:flex-end;">
        <a href="transacciones.php" class="btn btn-outline btn-sm" style="white-space:nowrap;">
          Limpiar
        </a>
      </div>

    </div>
  </form>
</div>

<!-- Tabla -->
<div class="card">
  <h2><?= number_format($total) ?> transaccione<?= $total !== 1 ? 's' : '' ?> encontrada<?= $total !== 1 ? 's' : '' ?></h2>

  <?php if (empty($transacciones)): ?>
    <p style="color:#5f6368;text-align:center;padding:1.5rem 0;">No se encontraron transacciones con los filtros seleccionados.</p>
  <?php else: ?>
    <div class="tabla-wrap">
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Categoría</th>
            <th>Tipo</th>
            <th>Cant.</th>
            <th>Monto</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transacciones as $t): ?>
          <tr>
            <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
            <td><?= h($t['categoria']) ?></td>
            <td>
              <span class="badge badge-<?= $t['tipo'] ?>">
                <?= $t['tipo'] === 'ingreso' ? 'Ingreso' : 'Egreso' ?>
              </span>
            </td>
            <td><?= rtrim(rtrim(number_format($t['cantidad'], 3), '0'), '.') ?></td>
            <td style="font-weight:600;color:<?= $t['tipo'] === 'ingreso' ? '#188038' : '#c5221f' ?>;white-space:nowrap;">
              <?= moneda($t['monto']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <?php if ($paginas > 1): ?>
    <div class="paginacion">
      <?php if ($pagina > 1): ?>
        <a href="<?= urlPagina($pagina - 1) ?>">‹ Anterior</a>
      <?php endif; ?>

      <?php
      $ini = max(1, $pagina - 2);
      $fin = min($paginas, $pagina + 2);
      for ($i = $ini; $i <= $fin; $i++):
      ?>
        <?php if ($i === $pagina): ?>
          <span class="activo"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= urlPagina($i) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($pagina < $paginas): ?>
        <a href="<?= urlPagina($pagina + 1) ?>">Siguiente ›</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php require '_layout_end.php'; ?>
