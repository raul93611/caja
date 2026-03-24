<?php
// _layout.php — Cabecera y navegación compartida
// Uso: definir $titulo antes de incluir este archivo.
if (!isset($titulo)) $titulo = 'Caja';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($titulo) ?> — Caja</title>
<style>
/* ===================== RESET & BASE ===================== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --azul:      #1a73e8;
  --azul-dark: #1558b0;
  --verde:     #188038;
  --rojo:      #c5221f;
  --gris-bg:   #f0f4f8;
  --gris-brd:  #dadce0;
  --txt:       #202124;
  --txt-2:     #5f6368;
  --blanco:    #ffffff;
  --nav-h:     56px;
  --sombra:    0 2px 8px rgba(0,0,0,.10);
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--gris-bg);
  color: var(--txt);
  min-height: 100vh;
  padding-bottom: calc(var(--nav-h) + 1rem);
}

/* ===================== TOPBAR ===================== */
.topbar {
  background: var(--azul);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1rem;
  height: 50px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--sombra);
}

.topbar .brand {
  font-weight: 700;
  font-size: 1.1rem;
  letter-spacing: -.5px;
}

.topbar .usuario {
  font-size: .8rem;
  opacity: .9;
}

/* ===================== NAV INFERIOR (mobile) ===================== */
.nav-bottom {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: var(--nav-h);
  background: var(--blanco);
  border-top: 1px solid var(--gris-brd);
  display: flex;
  align-items: stretch;
  z-index: 100;
  box-shadow: 0 -2px 8px rgba(0,0,0,.07);
}

.nav-bottom a {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: var(--txt-2);
  font-size: .68rem;
  font-weight: 500;
  gap: 2px;
  transition: color .15s, background .15s;
  border-radius: 0;
}

.nav-bottom a .icono { font-size: 1.3rem; line-height: 1; }
.nav-bottom a.activo { color: var(--azul); background: #e8f0fe; }
.nav-bottom a:hover  { color: var(--azul); }

/* ===================== CONTENIDO ===================== */
.contenido {
  padding: 1rem;
  max-width: 900px;
  margin: 0 auto;
}

/* ===================== TARJETAS ===================== */
.card {
  background: var(--blanco);
  border-radius: 12px;
  box-shadow: var(--sombra);
  padding: 1.2rem;
  margin-bottom: 1rem;
}

.card h2 {
  font-size: 1rem;
  color: var(--txt-2);
  font-weight: 600;
  margin-bottom: .8rem;
}

/* ===================== KPI CARDS ===================== */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .75rem;
  margin-bottom: 1rem;
}

.kpi {
  background: var(--blanco);
  border-radius: 12px;
  box-shadow: var(--sombra);
  padding: 1rem .75rem;
  text-align: center;
}

.kpi .etiqueta {
  font-size: .72rem;
  color: var(--txt-2);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .03em;
  margin-bottom: .3rem;
}

.kpi .valor {
  font-size: 1.15rem;
  font-weight: 700;
  word-break: break-all;
}

.kpi.ingreso .valor { color: var(--verde); }
.kpi.egreso  .valor { color: var(--rojo);  }
.kpi.balance .valor { color: var(--azul);  }

/* ===================== TABLA ===================== */
.tabla-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

table {
  width: 100%;
  border-collapse: collapse;
  font-size: .875rem;
}

thead th {
  background: #f8f9fa;
  padding: .6rem .75rem;
  text-align: left;
  font-weight: 600;
  color: var(--txt-2);
  border-bottom: 1.5px solid var(--gris-brd);
  white-space: nowrap;
}

tbody td {
  padding: .6rem .75rem;
  border-bottom: 1px solid #f1f3f4;
  vertical-align: middle;
}

tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #f8f9fa; }

.badge {
  display: inline-block;
  padding: .2rem .55rem;
  border-radius: 20px;
  font-size: .75rem;
  font-weight: 600;
}

.badge-ingreso { background: #e6f4ea; color: var(--verde); }
.badge-egreso  { background: #fce8e6; color: var(--rojo);  }

/* ===================== FORMULARIOS ===================== */
.form-group { margin-bottom: 1rem; }

.form-group label {
  display: block;
  font-size: .875rem;
  font-weight: 600;
  color: #3c4043;
  margin-bottom: .3rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: .7rem .9rem;
  border: 1.5px solid var(--gris-brd);
  border-radius: 8px;
  font-size: 1rem;
  outline: none;
  background: var(--blanco);
  color: var(--txt);
  transition: border-color .2s;
  appearance: auto;
}

.form-group input:focus,
.form-group select:focus { border-color: var(--azul); }

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem;
}

/* ===================== BOTONES ===================== */
.btn {
  display: inline-block;
  padding: .72rem 1.2rem;
  border: none;
  border-radius: 8px;
  font-size: .95rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: background .2s, opacity .2s;
  text-align: center;
}

.btn-primary  { background: var(--azul);  color: #fff; }
.btn-primary:hover  { background: var(--azul-dark); }
.btn-success  { background: #188038; color: #fff; }
.btn-success:hover  { background: #0f5a27; }
.btn-outline  { background: transparent; color: var(--azul); border: 1.5px solid var(--azul); }
.btn-outline:hover  { background: #e8f0fe; }
.btn-block    { display: block; width: 100%; }
.btn-sm       { padding: .4rem .8rem; font-size: .82rem; }

/* ===================== ALERTAS ===================== */
.alert {
  padding: .75rem 1rem;
  border-radius: 8px;
  font-size: .9rem;
  margin-bottom: 1rem;
}

.alert-error   { background: #fce8e6; color: #c5221f; }
.alert-success { background: #e6f4ea; color: #188038; }

/* ===================== PAGINACIÓN ===================== */
.paginacion {
  display: flex;
  gap: .4rem;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 1rem;
}

.paginacion a,
.paginacion span {
  display: inline-block;
  padding: .4rem .75rem;
  border-radius: 6px;
  font-size: .875rem;
  text-decoration: none;
  border: 1.5px solid var(--gris-brd);
  color: var(--txt);
  background: var(--blanco);
}

.paginacion .activo {
  background: var(--azul);
  color: #fff;
  border-color: var(--azul);
  font-weight: 700;
}

.paginacion a:hover { background: #e8f0fe; }

/* ===================== TÍTULO DE SECCIÓN ===================== */
.section-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--txt);
  margin-bottom: .75rem;
  display: flex;
  align-items: center;
  gap: .4rem;
}

/* ===================== FILTROS ===================== */
.filtros-form {
  display: flex;
  flex-wrap: wrap;
  gap: .6rem;
  align-items: flex-end;
}

.filtros-form .form-group { margin-bottom: 0; flex: 1; min-width: 120px; }

/* ===================== RESPONSIVE DESKTOP ===================== */
@media (min-width: 640px) {
  body { padding-bottom: 1rem; }
  .nav-bottom { display: none; }

  /* Nav lateral en desktop */
  .nav-lateral {
    position: fixed;
    left: 0; top: 50px;
    width: 200px;
    height: calc(100vh - 50px);
    background: var(--blanco);
    border-right: 1px solid var(--gris-brd);
    padding: 1rem 0;
    display: flex;
    flex-direction: column;
    gap: .25rem;
    z-index: 90;
  }

  .nav-lateral a {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .65rem 1.2rem;
    text-decoration: none;
    color: var(--txt);
    font-size: .9rem;
    font-weight: 500;
    border-radius: 0 24px 24px 0;
    margin-right: .75rem;
    transition: background .15s, color .15s;
  }

  .nav-lateral a .icono { font-size: 1.1rem; }
  .nav-lateral a.activo { background: #e8f0fe; color: var(--azul); font-weight: 700; }
  .nav-lateral a:hover  { background: #f0f4f8; color: var(--azul); }

  .contenido {
    margin-left: 200px;
    padding: 1.5rem;
  }

  .kpi .valor { font-size: 1.3rem; }
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="brand">🧾 Caja</div>
  <div class="usuario">
    <?= h($_SESSION['usuario_nombre'] ?? '') ?> &nbsp;·&nbsp;
    <a href="logout.php" style="color:#fff;font-size:.8rem;">Salir</a>
  </div>
</div>

<?php
// Determinar página activa
$pag = basename($_SERVER['PHP_SELF']);
$nav = [
  'index.php'             => ['icono' => '🏠', 'label' => 'Inicio'],
  'nueva-transaccion.php' => ['icono' => '➕', 'label' => 'Nueva'],
  'transacciones.php'     => ['icono' => '📋', 'label' => 'Movimientos'],
  'reportes.php'          => ['icono' => '📊', 'label' => 'Reportes'],
];
?>

<!-- Nav inferior (móvil) -->
<nav class="nav-bottom">
  <?php foreach ($nav as $href => $item): ?>
    <a href="<?= $href ?>" class="<?= $pag === $href ? 'activo' : '' ?>">
      <span class="icono"><?= $item['icono'] ?></span>
      <?= $item['label'] ?>
    </a>
  <?php endforeach; ?>
</nav>

<!-- Nav lateral (desktop) -->
<nav class="nav-lateral">
  <?php foreach ($nav as $href => $item): ?>
    <a href="<?= $href ?>" class="<?= $pag === $href ? 'activo' : '' ?>">
      <span class="icono"><?= $item['icono'] ?></span>
      <?= $item['label'] ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="contenido">
