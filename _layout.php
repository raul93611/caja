<?php
// _layout.php — Layout compartido con Bootstrap 5
if (!isset($titulo)) $titulo = 'Caja';
$pag = basename($_SERVER['PHP_SELF']);
$nav = [
    'index.php'             => ['icon' => 'bi-house-door-fill',  'label' => 'Inicio'],
    'nueva-transaccion.php' => ['icon' => 'bi-plus-circle-fill', 'label' => 'Nueva'],
    'transacciones.php'     => ['icon' => 'bi-list-ul',          'label' => 'Movimientos'],
    'reportes.php'          => ['icon' => 'bi-bar-chart-fill',   'label' => 'Reportes'],
];
$inicial = strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($titulo) ?> — Caja</title>

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Manrope font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
<!-- App CSS -->
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<!-- ── Sidebar overlay (móvil) ────────────────────────────── -->
<div id="sidebar-overlay"></div>

<!-- ── Sidebar ────────────────────────────────────────────── -->
<aside id="sidebar">
  <a href="index.php" class="sidebar-brand">
    <div class="brand-icon">🧾</div>
    <div>
      <span class="brand-name">Caja</span>
      <span class="brand-sub">Gestión financiera</span>
    </div>
  </a>

  <div class="nav-section-label">Menú principal</div>

  <nav>
    <?php foreach ($nav as $href => $item): ?>
      <a href="<?= $href ?>" class="nav-link <?= $pag === $href ? 'active' : '' ?>">
        <i class="bi <?= $item['icon'] ?>"></i>
        <?= $item['label'] ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar"><?= $inicial ?></div>
      <div class="overflow-hidden flex-grow-1">
        <span class="user-name"><?= h($_SESSION['usuario_nombre'] ?? '') ?></span>
        <a href="logout.php" class="user-logout d-block">
          <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
      </div>
    </div>
  </div>
</aside>

<!-- ── Topbar (móvil) ──────────────────────────────────────── -->
<header id="topbar">
  <a href="index.php" class="topbar-brand">
    <div class="brand-icon">🧾</div>
    Caja
  </a>
  <button id="btn-sidebar-toggle" aria-label="Menú">
    <i class="bi bi-list"></i>
  </button>
</header>

<!-- ── Bottom nav (móvil) ─────────────────────────────────── -->
<nav id="bottom-nav">
  <?php foreach ($nav as $href => $item): ?>
    <a href="<?= $href ?>" class="<?= $pag === $href ? 'active' : '' ?>">
      <i class="bi <?= $item['icon'] ?>"></i>
      <?= $item['label'] ?>
    </a>
  <?php endforeach; ?>
</nav>

<!-- ── Toast container ────────────────────────────────────── -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div>

<!-- ── Contenido principal ────────────────────────────────── -->
<main id="main-content">
