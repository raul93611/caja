<?php
require_once 'config.php';
requireLogin();

$db  = getDB();
$hoy = date('Y-m-d');

// KPIs del día
$stmt = $db->prepare('
    SELECT
        COALESCE(SUM(CASE WHEN tipo = "ingreso" THEN monto ELSE 0 END), 0) AS total_ingresos,
        COALESCE(SUM(CASE WHEN tipo = "egreso"  THEN monto ELSE 0 END), 0) AS total_egresos
    FROM transacciones
    WHERE fecha = ?
');
$stmt->execute([$hoy]);
$kpi = $stmt->fetch();
$balance = $kpi['total_ingresos'] - $kpi['total_egresos'];

// Últimas 10 transacciones
$stmt = $db->prepare('
    SELECT t.id, t.tipo, t.monto, t.cantidad, t.fecha,
           c.nombre AS categoria
    FROM transacciones t
    JOIN categorias c ON c.id = t.categoria_id
    ORDER BY t.creado_en DESC
    LIMIT 10
');
$stmt->execute();
$ultimas = $stmt->fetchAll();

$titulo = 'Inicio';
require '_layout.php';
?>

<div class="section-title">📅 Resumen de hoy <small style="font-size:.75rem;color:#5f6368;"><?= date('d/m/Y') ?></small></div>

<div class="kpi-grid">
  <div class="kpi ingreso">
    <div class="etiqueta">Ingresos</div>
    <div class="valor"><?= moneda($kpi['total_ingresos']) ?></div>
  </div>
  <div class="kpi egreso">
    <div class="etiqueta">Egresos</div>
    <div class="valor"><?= moneda($kpi['total_egresos']) ?></div>
  </div>
  <div class="kpi balance">
    <div class="etiqueta">Balance</div>
    <div class="valor" style="color:<?= $balance >= 0 ? '#188038' : '#c5221f' ?>">
      <?= moneda($balance) ?>
    </div>
  </div>
</div>

<div class="card">
  <h2>Últimas 10 transacciones</h2>

  <?php if (empty($ultimas)): ?>
    <p style="color:#5f6368;font-size:.9rem;text-align:center;padding:1rem 0;">
      Aún no hay transacciones registradas.
      <br><br>
      <a href="nueva-transaccion.php" class="btn btn-primary">+ Registrar primera transacción</a>
    </p>
  <?php else: ?>
    <div class="tabla-wrap">
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Categoría</th>
            <th>Tipo</th>
            <th>Monto</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ultimas as $t): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
            <td><?= h($t['categoria']) ?></td>
            <td>
              <span class="badge badge-<?= $t['tipo'] ?>">
                <?= $t['tipo'] === 'ingreso' ? 'Ingreso' : 'Egreso' ?>
              </span>
            </td>
            <td style="font-weight:600;color:<?= $t['tipo'] === 'ingreso' ? '#188038' : '#c5221f' ?>">
              <?= moneda($t['monto']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="text-align:right;margin-top:.75rem;">
      <a href="transacciones.php" class="btn btn-outline btn-sm">Ver todas →</a>
    </div>
  <?php endif; ?>
</div>

<div style="text-align:center;margin-top:.5rem;">
  <a href="nueva-transaccion.php" class="btn btn-success" style="min-width:220px;">
    ➕ Nueva transacción
  </a>
</div>

<?php require '_layout_end.php'; ?>
