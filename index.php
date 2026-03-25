<?php
require_once 'config.php';
requireLogin();

$db  = getDB();
$hoy = date('Y-m-d');

$stmt = $db->prepare('
    SELECT
        COALESCE(SUM(CASE WHEN tipo="ingreso" THEN monto * cantidad ELSE 0 END),0) AS ingresos,
        COALESCE(SUM(CASE WHEN tipo="egreso"  THEN monto * cantidad ELSE 0 END),0) AS egresos
    FROM transacciones WHERE fecha = ?
');
$stmt->execute([$hoy]);
$kpi     = $stmt->fetch();
$balance = $kpi['ingresos'] - $kpi['egresos'];

$stmt = $db->prepare('
    SELECT t.id, t.tipo, t.monto, t.cantidad, t.fecha, c.nombre AS categoria
    FROM transacciones t
    JOIN categorias c ON c.id = t.categoria_id
    ORDER BY t.creado_en DESC LIMIT 10
');
$stmt->execute();
$ultimas = $stmt->fetchAll();

$titulo = 'Inicio';
require '_layout.php';
?>

<!-- Page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Resumen del día — <?= date('l d \d\e F, Y', strtotime($hoy)) ?></p>
  </div>
  <a href="nueva-transaccion.php" class="btn btn-success">
    <i class="bi bi-plus-lg me-1"></i> Nueva transacción
  </a>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <div class="col-4">
    <div class="kpi-card kpi-income">
      <div class="kpi-label">Ingresos hoy</div>
      <div class="kpi-value"><?= moneda($kpi['ingresos']) ?></div>
      <div class="kpi-icon"><i class="bi bi-arrow-up-circle-fill"></i></div>
    </div>
  </div>
  <div class="col-4">
    <div class="kpi-card kpi-expense">
      <div class="kpi-label">Egresos hoy</div>
      <div class="kpi-value"><?= moneda($kpi['egresos']) ?></div>
      <div class="kpi-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
    </div>
  </div>
  <div class="col-4">
    <div class="kpi-card kpi-balance">
      <div class="kpi-label">Balance</div>
      <div class="kpi-value" style="color:<?= $balance >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>">
        <?= moneda($balance) ?>
      </div>
      <div class="kpi-icon"><i class="bi bi-wallet2"></i></div>
    </div>
  </div>
</div>

<!-- Últimas transacciones -->
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="bi bi-clock-history me-2"></i>Últimas 10 transacciones</span>
    <a href="transacciones.php" class="btn btn-sm btn-outline-secondary">
      Ver todas <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($ultimas)): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>Aún no hay transacciones registradas.</p>
        <a href="nueva-transaccion.php" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-lg me-1"></i> Registrar primera transacción
        </a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="col-mobile-hide">Fecha</th>
              <th>Categoría</th>
              <th>Tipo</th>
              <th class="text-end">Monto</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ultimas as $t): ?>
            <tr>
              <td class="text-muted-sm col-mobile-hide"><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
              <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;"><?= h($t['categoria']) ?></td>
              <td>
                <?php if ($t['tipo'] === 'ingreso'): ?>
                  <span class="badge-income"><i class="bi bi-arrow-up"></i> Ingreso</span>
                <?php else: ?>
                  <span class="badge-expense"><i class="bi bi-arrow-down"></i> Egreso</span>
                <?php endif; ?>
              </td>
              <td class="text-end <?= $t['tipo'] === 'ingreso' ? 'text-income' : 'text-expense' ?>">
                <?= moneda($t['monto'] * $t['cantidad']) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_layout_end.php'; ?>
