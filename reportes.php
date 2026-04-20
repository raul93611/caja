<?php
require_once 'config.php';
requireLogin();

$db = getDB();

$periodo    = $_GET['periodo']   ?? 'mensual';
$fecha_ini  = $_GET['fecha_ini'] ?? '';
$fecha_fin  = $_GET['fecha_fin'] ?? '';
$rangoLibre = ($fecha_ini !== '' && $fecha_fin !== '');

if (!$rangoLibre) {
    $hoy = new DateTime();
    switch ($periodo) {
        case 'diario':  $fecha_ini = $hoy->format('Y-m-d'); $fecha_fin = $hoy->format('Y-m-d'); break;
        case 'semanal': $lunes = (clone $hoy)->modify('monday this week');
                        $fecha_ini = $lunes->format('Y-m-d'); $fecha_fin = $hoy->format('Y-m-d'); break;
        case 'anual':   $fecha_ini = $hoy->format('Y-01-01'); $fecha_fin = $hoy->format('Y-12-31'); break;
        default:        $fecha_ini = $hoy->format('Y-m-01'); $fecha_fin = $hoy->format('Y-m-t');
    }
}

$stmtCat = $db->prepare('
    SELECT c.nombre, t.tipo, SUM(t.monto * t.cantidad) AS total, COUNT(*) AS num_trans
    FROM transacciones t JOIN categorias c ON c.id = t.categoria_id
    WHERE t.fecha BETWEEN ? AND ?
    GROUP BY c.id, t.tipo ORDER BY t.tipo, total DESC
');
$stmtCat->execute([$fecha_ini, $fecha_fin]);
$porCategoria = $stmtCat->fetchAll();

$stmtBal = $db->prepare('
    SELECT COALESCE(SUM(CASE WHEN tipo="ingreso" THEN monto * cantidad ELSE 0 END),0) AS ingresos,
           COALESCE(SUM(CASE WHEN tipo="egreso"  THEN monto * cantidad ELSE 0 END),0) AS egresos
    FROM transacciones WHERE fecha BETWEEN ? AND ?
');
$stmtBal->execute([$fecha_ini, $fecha_fin]);
$balance      = $stmtBal->fetch();
$balance_neto = $balance['ingresos'] - $balance['egresos'];

$stmtDia = $db->prepare('
    SELECT fecha,
           SUM(CASE WHEN tipo="ingreso" THEN monto * cantidad ELSE 0 END) AS ingresos,
           SUM(CASE WHEN tipo="egreso"  THEN monto * cantidad ELSE 0 END) AS egresos
    FROM transacciones WHERE fecha BETWEEN ? AND ?
    GROUP BY fecha ORDER BY fecha
');
$stmtDia->execute([$fecha_ini, $fecha_fin]);
$porDia = $stmtDia->fetchAll();

$labels_dias = []; $data_ing = []; $data_egr = [];
foreach ($porDia as $d) {
    $labels_dias[] = date('d/m', strtotime($d['fecha']));
    $data_ing[]    = (float)$d['ingresos'];
    $data_egr[]    = (float)$d['egresos'];
}

$labels_cat_egr = []; $data_cat_egr = [];
$labels_cat_ing = []; $data_cat_ing = [];
$colors_egr = ['#dc2626','#d97706','#7c3aed','#db2777','#0891b2','#65a30d','#6366f1','#f59e0b'];
$colors_ing = ['#059669','#10b981','#0d9488','#22c55e','#14b8a6','#84cc16','#06b6d4','#3b82f6'];
foreach ($porCategoria as $row) {
    if ($row['tipo'] === 'egreso') {
        $labels_cat_egr[] = $row['nombre'];
        $data_cat_egr[]   = (float)$row['total'];
    } else {
        $labels_cat_ing[] = $row['nombre'];
        $data_cat_ing[]   = (float)$row['total'];
    }
}

$periodos = ['diario'=>'Hoy','semanal'=>'Esta semana','mensual'=>'Este mes','anual'=>'Este año'];
$titulo = 'Reportes';
require '_layout.php';
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Reportes</h1>
    <p class="page-subtitle">
      <?= date('d/m/Y', strtotime($fecha_ini)) ?> — <?= date('d/m/Y', strtotime($fecha_fin)) ?>
    </p>
  </div>
</div>

<!-- Selector de período -->
<div class="card mb-3">
  <div class="card-header"><i class="bi bi-calendar3 me-2"></i>Período</div>
  <div class="card-body">
    <div class="mb-3">
      <div class="periodo-pills">
        <?php foreach ($periodos as $val => $lbl): ?>
          <a href="?periodo=<?= $val ?>"
             class="periodo-pill <?= ($periodo === $val && !$rangoLibre) ? 'active' : '' ?>">
            <?= $lbl ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <hr class="my-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-sm-auto">
        <label class="form-label">DESDE</label>
        <input type="date" class="form-control form-control-sm" name="fecha_ini"
               value="<?= h($_GET['fecha_ini'] ?? '') ?>">
      </div>
      <div class="col-12 col-sm-auto">
        <label class="form-label">HASTA</label>
        <input type="date" class="form-control form-control-sm" name="fecha_fin"
               value="<?= h($_GET['fecha_fin'] ?? '') ?>">
      </div>
      <div class="col-12 col-sm-auto">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-search me-1"></i>Aplicar rango
        </button>
      </div>
    </form>
  </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <div class="col-4">
    <div class="kpi-card kpi-income">
      <div class="kpi-label">Ingresos</div>
      <div class="kpi-value"><?= moneda($balance['ingresos']) ?></div>
      <div class="kpi-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
    </div>
  </div>
  <div class="col-4">
    <div class="kpi-card kpi-expense">
      <div class="kpi-label">Egresos</div>
      <div class="kpi-value"><?= moneda($balance['egresos']) ?></div>
      <div class="kpi-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
    </div>
  </div>
  <div class="col-4">
    <div class="kpi-card kpi-balance">
      <div class="kpi-label">Balance</div>
      <div class="kpi-value" style="color:<?= $balance_neto >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>">
        <?= moneda($balance_neto) ?>
      </div>
      <div class="kpi-icon"><i class="bi bi-wallet2"></i></div>
    </div>
  </div>
</div>

<!-- Gráficas -->
<?php if (!empty($porDia) || !empty($data_cat_ing) || !empty($data_cat_egr)): ?>
<div class="row g-3 mb-3">

  <?php if (!empty($porDia)): ?>
  <div class="col-12">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Ingresos vs Egresos por día</div>
      <div class="card-body">
        <div style="position:relative;height:240px;">
          <canvas id="graficaBarras"></canvas>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data_cat_ing)): ?>
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Distribución de ingresos</div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <div style="position:relative;height:240px;width:100%;max-width:320px;">
          <canvas id="graficaPastelIng"></canvas>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($data_cat_egr)): ?>
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Distribución de egresos</div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <div style="position:relative;height:240px;width:100%;max-width:320px;">
          <canvas id="graficaPastelEgr"></canvas>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<style>
@media (max-width: 767px) {
  .table-categorias thead { display: none; }
  .table-categorias tbody tr {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    padding: .55rem .85rem;
    border-bottom: 1px solid #f1f5f9;
    gap: 0;
  }
  .table-categorias tbody tr:last-child { border-bottom: none; }
  .table-categorias tbody tr td { display: block; border: none !important; padding: 0; }
  .table-categorias td.col-cat  { flex: 1; min-width: 0; font-weight: 600; padding-right: .5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .table-categorias td.col-tipo { display: flex; align-items: center; }
  .table-categorias td.col-num  { display: none !important; }
  .table-categorias td.col-total {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: .3rem;
    margin-top: .3rem;
    border-top: 1px dashed #f1f5f9 !important;
    font-size: .82rem;
  }
  .table-categorias td.col-total::before {
    content: attr(data-num);
    color: #94a3b8;
    font-size: .75rem;
    font-weight: 400;
  }
}
</style>

<!-- Tabla por categoría -->
<div class="card">
  <div class="card-header"><i class="bi bi-table me-2"></i>Totales por categoría</div>
  <div class="card-body p-0">
    <?php if (empty($porCategoria)): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>Sin transacciones en este período.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0 table-categorias">
          <thead>
            <tr>
              <th>Categoría</th>
              <th>Tipo</th>
              <th class="text-center">Movimientos</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($porCategoria as $row): ?>
            <tr>
              <td class="col-cat fw-500"><?= h($row['nombre']) ?></td>
              <td class="col-tipo">
                <?php if ($row['tipo'] === 'ingreso'): ?>
                  <span class="badge-income"><i class="bi bi-arrow-up"></i> Ingreso</span>
                <?php else: ?>
                  <span class="badge-expense"><i class="bi bi-arrow-down"></i> Egreso</span>
                <?php endif; ?>
              </td>
              <td class="col-num text-center text-muted-sm"><?= (int)$row['num_trans'] ?></td>
              <td class="col-total text-end <?= $row['tipo'] === 'ingreso' ? 'text-income' : 'text-expense' ?>"
                  data-num="<?= (int)$row['num_trans'] ?> movimiento<?= $row['num_trans'] != 1 ? 's' : '' ?>">
                <?= moneda($row['total']) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const fmtBs = v => 'Bs ' + v.toLocaleString('es-BO', {minimumFractionDigits:2, maximumFractionDigits:2});

<?php if (!empty($porDia)): ?>
new Chart(document.getElementById('graficaBarras').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels_dias, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [
      {
        label: 'Ingresos', data: <?= json_encode($data_ing) ?>,
        backgroundColor: 'rgba(5,150,105,.8)', borderWidth: 0, borderRadius: 5, borderSkipped: false,
      },
      {
        label: 'Egresos',  data: <?= json_encode($data_egr) ?>,
        backgroundColor: 'rgba(220,38,38,.75)', borderWidth: 0, borderRadius: 5, borderSkipped: false,
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { position:'top', labels:{ usePointStyle:true, pointStyle:'circle', padding:16, font:{size:12,family:'Inter'} } },
      tooltip: { callbacks:{ label: c => ' ' + fmtBs(c.parsed.y) } }
    },
    scales: {
      x: { grid:{ display:false }, ticks:{ font:{size:11,family:'Inter'} } },
      y: { beginAtZero:true, grid:{ color:'rgba(0,0,0,.05)' },
           ticks:{ callback: v => 'Bs '+v.toLocaleString('es-BO'), font:{size:11,family:'Inter'} } }
    }
  }
});
<?php endif; ?>

<?php
$doughnutOpts = "{
    responsive: true, maintainAspectRatio: false, cutout: '62%',
    plugins: {
      legend: { position:'bottom', labels:{ usePointStyle:true, pointStyle:'circle', padding:12, font:{size:11,family:'Inter'} } },
      tooltip: { callbacks:{ label: c => ' ' + fmtBs(c.parsed) } }
    }
  }";
?>

<?php if (!empty($data_cat_ing)): ?>
new Chart(document.getElementById('graficaPastelIng').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($labels_cat_ing, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{
      data: <?= json_encode($data_cat_ing) ?>,
      backgroundColor: <?= json_encode(array_slice($colors_ing, 0, count($data_cat_ing))) ?>,
      hoverOffset: 10, borderWidth: 3, borderColor: '#fff',
    }]
  },
  options: <?= $doughnutOpts ?>
});
<?php endif; ?>

<?php if (!empty($data_cat_egr)): ?>
new Chart(document.getElementById('graficaPastelEgr').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($labels_cat_egr, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{
      data: <?= json_encode($data_cat_egr) ?>,
      backgroundColor: <?= json_encode(array_slice($colors_egr, 0, count($data_cat_egr))) ?>,
      hoverOffset: 10, borderWidth: 3, borderColor: '#fff',
    }]
  },
  options: <?= $doughnutOpts ?>
});
<?php endif; ?>
</script>

<?php require '_layout_end.php'; ?>
