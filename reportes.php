<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// ── Determinar período ────────────────────────────────────────
$periodo    = $_GET['periodo']   ?? 'mensual';
$fecha_ini  = $_GET['fecha_ini'] ?? '';
$fecha_fin  = $_GET['fecha_fin'] ?? '';

// Si hay rango libre, lo usamos; si no, calculamos por período
if ($fecha_ini === '' || $fecha_fin === '') {
    $hoy = new DateTime();
    switch ($periodo) {
        case 'diario':
            $fecha_ini = $hoy->format('Y-m-d');
            $fecha_fin = $hoy->format('Y-m-d');
            break;
        case 'semanal':
            $lunes = (clone $hoy)->modify('monday this week');
            $fecha_ini = $lunes->format('Y-m-d');
            $fecha_fin = $hoy->format('Y-m-d');
            break;
        case 'anual':
            $fecha_ini = $hoy->format('Y-01-01');
            $fecha_fin = $hoy->format('Y-12-31');
            break;
        default: // mensual
            $fecha_ini = $hoy->format('Y-m-01');
            $fecha_fin = $hoy->format('Y-m-t');
    }
}

// ── Totales por categoría ─────────────────────────────────────
$stmt = $db->prepare('
    SELECT c.nombre, t.tipo,
           SUM(t.monto) AS total,
           COUNT(*)     AS num_trans
    FROM transacciones t
    JOIN categorias c ON c.id = t.categoria_id
    WHERE t.fecha BETWEEN ? AND ?
    GROUP BY c.id, t.tipo
    ORDER BY t.tipo, total DESC
');
$stmt->execute([$fecha_ini, $fecha_fin]);
$porCategoria = $stmt->fetchAll();

// ── Balance del período ───────────────────────────────────────
$stmtBal = $db->prepare('
    SELECT
        COALESCE(SUM(CASE WHEN tipo="ingreso" THEN monto ELSE 0 END), 0) AS ingresos,
        COALESCE(SUM(CASE WHEN tipo="egreso"  THEN monto ELSE 0 END), 0) AS egresos
    FROM transacciones
    WHERE fecha BETWEEN ? AND ?
');
$stmtBal->execute([$fecha_ini, $fecha_fin]);
$balance = $stmtBal->fetch();
$balance_neto = $balance['ingresos'] - $balance['egresos'];

// ── Ingresos vs Egresos por día (para gráfica de barras) ──────
$stmtDia = $db->prepare('
    SELECT fecha,
           SUM(CASE WHEN tipo="ingreso" THEN monto ELSE 0 END) AS ingresos,
           SUM(CASE WHEN tipo="egreso"  THEN monto ELSE 0 END) AS egresos
    FROM transacciones
    WHERE fecha BETWEEN ? AND ?
    GROUP BY fecha
    ORDER BY fecha
');
$stmtDia->execute([$fecha_ini, $fecha_fin]);
$porDia = $stmtDia->fetchAll();

// ── Separar datos para gráficas ───────────────────────────────
$labels_dias  = [];
$data_ing     = [];
$data_egr     = [];
foreach ($porDia as $d) {
    $labels_dias[] = date('d/m', strtotime($d['fecha']));
    $data_ing[]    = (float)$d['ingresos'];
    $data_egr[]    = (float)$d['egresos'];
}

// Pie chart: distribución por categoría (egresos)
$labels_cat = [];
$data_cat   = [];
$colors_cat = ['#c5221f','#e37400','#0b8043','#1a73e8','#8430ce','#c2185b','#00838f'];
$i = 0;
foreach ($porCategoria as $row) {
    if ($row['tipo'] === 'egreso') {
        $labels_cat[] = $row['nombre'];
        $data_cat[]   = (float)$row['total'];
    }
    $i++;
}

$titulo = 'Reportes';
require '_layout.php';
?>

<div class="section-title">📊 Reportes</div>

<!-- Filtros de período -->
<div class="card">
  <form method="GET" action="" id="formReporte">
    <div style="margin-bottom:.75rem;">
      <label style="font-size:.875rem;font-weight:600;display:block;margin-bottom:.4rem;">Período predefinido</label>
      <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
        <?php foreach (['diario' => 'Hoy', 'semanal' => 'Semana', 'mensual' => 'Mes', 'anual' => 'Año'] as $val => $lbl): ?>
          <a href="?periodo=<?= $val ?>"
             class="btn btn-sm <?= ($periodo === $val && $_GET['fecha_ini'] ?? '') === '' ? 'btn-primary' : 'btn-outline' ?>">
            <?= $lbl ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group">
        <label for="fecha_ini">Desde</label>
        <input type="date" name="fecha_ini" id="fecha_ini"
               value="<?= h($_GET['fecha_ini'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="fecha_fin">Hasta</label>
        <input type="date" name="fecha_fin" id="fecha_fin"
               value="<?= h($_GET['fecha_fin'] ?? '') ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Aplicar rango</button>
  </form>
</div>

<!-- KPIs del período -->
<div class="kpi-grid">
  <div class="kpi ingreso">
    <div class="etiqueta">Ingresos</div>
    <div class="valor"><?= moneda($balance['ingresos']) ?></div>
  </div>
  <div class="kpi egreso">
    <div class="etiqueta">Egresos</div>
    <div class="valor"><?= moneda($balance['egresos']) ?></div>
  </div>
  <div class="kpi balance">
    <div class="etiqueta">Balance</div>
    <div class="valor" style="color:<?= $balance_neto >= 0 ? '#188038' : '#c5221f' ?>">
      <?= moneda($balance_neto) ?>
    </div>
  </div>
</div>

<p style="text-align:center;font-size:.8rem;color:#5f6368;margin-bottom:.75rem;">
  Período: <?= date('d/m/Y', strtotime($fecha_ini)) ?>
  — <?= date('d/m/Y', strtotime($fecha_fin)) ?>
</p>

<!-- Gráfica de barras: Ingresos vs Egresos por día -->
<?php if (!empty($porDia)): ?>
<div class="card">
  <h2>Ingresos vs Egresos por día</h2>
  <div style="position:relative;height:260px;">
    <canvas id="graficaBarras"></canvas>
  </div>
</div>
<?php endif; ?>

<!-- Gráfica de pastel: distribución de egresos por categoría -->
<?php if (!empty($data_cat)): ?>
<div class="card">
  <h2>Distribución de egresos por categoría</h2>
  <div style="position:relative;height:280px;max-width:320px;margin:0 auto;">
    <canvas id="graficaPastel"></canvas>
  </div>
</div>
<?php endif; ?>

<!-- Tabla de totales por categoría -->
<div class="card">
  <h2>Totales por categoría</h2>
  <?php if (empty($porCategoria)): ?>
    <p style="color:#5f6368;text-align:center;padding:1rem;">Sin transacciones en este período.</p>
  <?php else: ?>
    <div class="tabla-wrap">
      <table>
        <thead>
          <tr>
            <th>Categoría</th>
            <th>Tipo</th>
            <th>Transacciones</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($porCategoria as $row): ?>
          <tr>
            <td><?= h($row['nombre']) ?></td>
            <td>
              <span class="badge badge-<?= $row['tipo'] ?>">
                <?= $row['tipo'] === 'ingreso' ? 'Ingreso' : 'Egreso' ?>
              </span>
            </td>
            <td><?= (int)$row['num_trans'] ?></td>
            <td style="font-weight:600;color:<?= $row['tipo'] === 'ingreso' ? '#188038' : '#c5221f' ?>;">
              <?= moneda($row['total']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
<?php if (!empty($porDia)): ?>
// Gráfica de barras
(function() {
  const ctx = document.getElementById('graficaBarras').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($labels_dias, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [
        {
          label: 'Ingresos',
          data: <?= json_encode($data_ing) ?>,
          backgroundColor: 'rgba(24,128,56,.75)',
          borderColor:     '#188038',
          borderWidth: 1.5,
          borderRadius: 4,
        },
        {
          label: 'Egresos',
          data: <?= json_encode($data_egr) ?>,
          backgroundColor: 'rgba(197,34,31,.65)',
          borderColor:     '#c5221f',
          borderWidth: 1.5,
          borderRadius: 4,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: ctx => ' $' + ctx.parsed.y.toLocaleString('es-MX', {minimumFractionDigits:2})
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: v => '$' + v.toLocaleString('es-MX')
          }
        }
      }
    }
  });
})();
<?php endif; ?>

<?php if (!empty($data_cat)): ?>
// Gráfica de pastel
(function() {
  const ctx = document.getElementById('graficaPastel').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($labels_cat, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{
        data: <?= json_encode($data_cat) ?>,
        backgroundColor: <?= json_encode(array_slice($colors_cat, 0, count($data_cat))) ?>,
        hoverOffset: 8,
        borderWidth: 2,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 12 } } },
        tooltip: {
          callbacks: {
            label: ctx => ' $' + ctx.parsed.toLocaleString('es-MX', {minimumFractionDigits:2})
          }
        }
      }
    }
  });
})();
<?php endif; ?>
</script>

<?php require '_layout_end.php'; ?>
