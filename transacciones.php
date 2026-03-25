<?php
require_once 'config.php';
requireLogin();

$db = getDB();

$filtro_tipo      = $_GET['tipo']      ?? '';
$filtro_cat       = (int)($_GET['categoria'] ?? 0);
$filtro_fecha_ini = $_GET['fecha_ini'] ?? '';
$filtro_fecha_fin = $_GET['fecha_fin'] ?? '';
$por_pagina       = 20;
$pagina           = max(1, (int)($_GET['pagina'] ?? 1));
$offset           = ($pagina - 1) * $por_pagina;

$where  = ['1=1'];
$params = [];
if (in_array($filtro_tipo, ['ingreso','egreso'], true)) { $where[] = 't.tipo = ?';         $params[] = $filtro_tipo; }
if ($filtro_cat > 0)       { $where[] = 't.categoria_id = ?'; $params[] = $filtro_cat; }
if ($filtro_fecha_ini)     { $where[] = 't.fecha >= ?';        $params[] = $filtro_fecha_ini; }
if ($filtro_fecha_fin)     { $where[] = 't.fecha <= ?';        $params[] = $filtro_fecha_fin; }
$sqlWhere = implode(' AND ', $where);

$stmtCount = $db->prepare("SELECT COUNT(*) FROM transacciones t WHERE $sqlWhere");
$stmtCount->execute($params);
$total   = (int)$stmtCount->fetchColumn();
$paginas = max(1, (int)ceil($total / $por_pagina));

$stmtRows = $db->prepare("
    SELECT t.id, t.tipo, t.monto, t.cantidad, t.fecha, t.detalles, c.nombre AS categoria, c.id AS categoria_id
    FROM transacciones t JOIN categorias c ON c.id = t.categoria_id
    WHERE $sqlWhere ORDER BY t.fecha DESC, t.creado_en DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmtRows->execute($params);
$transacciones = $stmtRows->fetchAll();

$cats      = $db->query('SELECT id, nombre, tipo FROM categorias ORDER BY tipo, nombre')->fetchAll();
$hayFiltros = $filtro_tipo || $filtro_cat || $filtro_fecha_ini || $filtro_fecha_fin;

function urlPagina(int $p): string {
    $q = $_GET; $q['pagina'] = $p;
    return '?' . http_build_query($q);
}

$titulo = 'Movimientos';
require '_layout.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Movimientos</h1>
    <p class="page-subtitle">Historial de transacciones</p>
  </div>
  <a href="nueva-transaccion.php" class="btn btn-success">
    <i class="bi bi-plus-lg me-1"></i> Nueva
  </a>
</div>

<!-- Filtros -->
<div class="card mb-3">
  <div class="card-header d-flex align-items-center justify-content-between" role="button"
       data-bs-toggle="collapse" data-bs-target="#filtros-panel" style="cursor:pointer;">
    <span>
      <i class="bi bi-funnel me-2"></i>Filtros
      <?php if ($hayFiltros): ?>
        <span class="filter-chip ms-2">Activos</span>
      <?php endif; ?>
    </span>
    <i class="bi bi-chevron-down"></i>
  </div>
  <div class="collapse <?= $hayFiltros ? 'show' : '' ?>" id="filtros-panel">
    <div class="card-body">
      <form method="GET" id="formFiltros">
        <div class="row g-2 align-items-end">
          <div class="col-6 col-md-2">
            <label class="form-label">TIPO</label>
            <select class="form-select form-select-sm" name="tipo" onchange="this.form.submit()">
              <option value="">Todos</option>
              <option value="ingreso" <?= $filtro_tipo === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
              <option value="egreso"  <?= $filtro_tipo === 'egreso'  ? 'selected' : '' ?>>Egreso</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">CATEGORÍA</label>
            <select class="form-select form-select-sm" name="categoria" onchange="this.form.submit()">
              <option value="">Todas</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filtro_cat === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= h($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">DESDE</label>
            <input type="date" class="form-control form-control-sm" name="fecha_ini"
                   value="<?= h($filtro_fecha_ini) ?>" onchange="this.form.submit()">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">HASTA</label>
            <input type="date" class="form-control form-control-sm" name="fecha_fin"
                   value="<?= h($filtro_fecha_fin) ?>" onchange="this.form.submit()">
          </div>
          <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="bi bi-search me-1"></i>Filtrar
            </button>
            <?php if ($hayFiltros): ?>
              <a href="transacciones.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg me-1"></i>Limpiar
              </a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Tabla -->
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <span>
      <i class="bi bi-table me-2"></i>
      <span id="result-count" data-total="<?= $total ?>">
        <?= number_format($total) ?> resultado<?= $total !== 1 ? 's' : '' ?>
      </span>
    </span>
    <?php if ($paginas > 1): ?>
      <span class="text-muted-sm">Página <?= $pagina ?> de <?= $paginas ?></span>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">

    <?php if (empty($transacciones)): ?>
      <div class="empty-state">
        <i class="bi bi-search"></i>
        <p>Sin resultados<?= $hayFiltros ? ' con los filtros aplicados' : '' ?>.</p>
        <?php if ($hayFiltros): ?>
          <a href="transacciones.php" class="btn btn-outline-secondary btn-sm">Quitar filtros</a>
        <?php else: ?>
          <a href="nueva-transaccion.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Registrar transacción
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="col-mobile-hide">Fecha</th>
              <th>Categoría</th>
              <th>Tipo</th>
              <th class="col-mobile-hide">Cant.</th>
              <th class="text-end">Monto</th>
              <th class="text-center" style="width:80px">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transacciones as $t): ?>
            <tr id="row-<?= $t['id'] ?>">
              <td class="text-muted-sm col-mobile-hide"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
              <td style="max-width:140px;">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($t['categoria']) ?></div>
                <?php if ($t['detalles']): ?>
                  <div class="text-muted-sm" style="font-size:.72rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:130px;">
                    <?= h($t['detalles']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($t['tipo'] === 'ingreso'): ?>
                  <span class="badge-income"><i class="bi bi-arrow-up"></i> Ingreso</span>
                <?php else: ?>
                  <span class="badge-expense"><i class="bi bi-arrow-down"></i> Egreso</span>
                <?php endif; ?>
              </td>
              <td class="text-muted-sm col-mobile-hide"><?= rtrim(rtrim(number_format($t['cantidad'],3),'0'),'.') ?></td>
              <td class="text-end <?= $t['tipo'] === 'ingreso' ? 'text-income' : 'text-expense' ?>">
                <?= moneda($t['monto'] * $t['cantidad']) ?>
                <?php if ($t['cantidad'] != 1): ?>
                  <div class="text-muted-sm" style="font-size:.7rem;font-weight:400">
                    <?= moneda($t['monto']) ?> × <?= rtrim(rtrim(number_format($t['cantidad'],3),'0'),'.') ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <button class="btn btn-icon btn-outline-primary me-1"
                        onclick="openEditModal(<?= $t['id'] ?>)"
                        title="Editar">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-icon btn-outline-danger"
                        onclick="confirmDelete(<?= $t['id'] ?>, '<?= h(addslashes($t['categoria'])) ?> — <?= moneda($t['monto'] * $t['cantidad']) ?>')"
                        title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <?php if ($paginas > 1): ?>
      <div class="d-flex justify-content-center py-3 border-top">
        <ul class="pagination mb-0">
          <?php if ($pagina > 1): ?>
            <li class="page-item">
              <a class="page-link" href="<?= urlPagina($pagina - 1) ?>">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>
          <?php endif; ?>

          <?php
          $ini = max(1, $pagina - 2);
          $fin = min($paginas, $pagina + 2);
          for ($i = $ini; $i <= $fin; $i++):
          ?>
            <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
              <a class="page-link" href="<?= urlPagina($i) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($pagina < $paginas): ?>
            <li class="page-item">
              <a class="page-link" href="<?= urlPagina($pagina + 1) ?>">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ── Modal: Confirmar eliminación ─────────────────────── -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header border-danger">
        <h5 class="modal-title text-danger">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Eliminar
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1" style="font-size:.875rem">¿Eliminar esta transacción?</p>
        <p class="fw-600 text-danger mb-0" id="delete-desc" style="font-size:.9rem"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-delete">
          <i class="bi bi-trash me-1"></i>Eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal: Editar transacción ─────────────────────────── -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-pencil-square me-2 text-primary"></i>Editar transacción
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="edit-modal-body">
        <!-- Cargado por JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btn-save-edit">
          <i class="bi bi-check-lg me-1"></i>Guardar cambios
        </button>
      </div>
    </div>
  </div>
</div>

<?php require '_layout_end.php'; ?>
