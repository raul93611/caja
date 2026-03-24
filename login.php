<?php
session_start();
require_once 'config.php';

if (!empty($_SESSION['usuario_id'])) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Por favor ingresa tu correo y contraseña.';
    } else {
        $stmt = getDB()->prepare('SELECT id, nombre, password_hash FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']     = $u['id'];
            $_SESSION['usuario_nombre'] = $u['nombre'];
            header('Location: index.php'); exit;
        }
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión — Caja</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Manrope', -apple-system, sans-serif;
    min-height: 100vh;
    display: flex;
    background: #0f172a;
    -webkit-font-smoothing: antialiased;
  }

  /* Left panel (hidden on mobile) */
  .login-left {
    display: none;
    flex: 1;
    background: linear-gradient(145deg, #1e1b4b 0%, #0f172a 50%, #0c4a6e 100%);
    padding: 3rem;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }

  .login-left::before {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(99,102,241,.25) 0%, transparent 70%);
    top: -100px; right: -100px;
  }

  .login-left::after {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(5,150,105,.2) 0%, transparent 70%);
    bottom: 50px; left: -50px;
  }

  .login-left .feature-list { list-style: none; padding: 0; margin: 0; position: relative; z-index: 1; }
  .login-left .feature-list li {
    display: flex;
    align-items: center;
    gap: .75rem;
    color: #94a3b8;
    font-size: .9rem;
    margin-bottom: .9rem;
  }
  .login-left .feature-list li i { color: #6366f1; font-size: 1.1rem; width: 24px; text-align: center; }

  .login-left .left-brand {
    display: flex; align-items: center; gap: .7rem;
    color: #f1f5f9; font-size: 1.2rem; font-weight: 700;
    position: relative; z-index: 1;
  }
  .login-left .left-brand .brand-icon {
    width: 40px; height: 40px; background: #6366f1;
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; box-shadow: 0 4px 16px rgba(99,102,241,.5);
  }

  /* Right panel */
  .login-right {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.25rem;
    background: #f8fafc;
  }

  .login-box {
    width: 100%;
    max-width: 400px;
  }

  .login-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.1), 0 4px 16px rgba(0,0,0,.06);
    border: 1px solid #e2e8f0;
    overflow: hidden;
  }

  .login-card-header {
    padding: 2rem 2rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
  }

  .login-logo {
    width: 52px; height: 52px;
    background: #6366f1;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 6px 20px rgba(99,102,241,.4);
  }

  .login-card-header h1 {
    font-size: 1.4rem; font-weight: 800;
    color: #0f172a; letter-spacing: -.03em; margin-bottom: .2rem;
  }

  .login-card-header p { font-size: .82rem; color: #64748b; }

  .login-card-body { padding: 1.6rem 2rem 2rem; }

  .form-label { font-size: .78rem; font-weight: 600; color: #64748b; letter-spacing: .02em; }

  .input-group-text {
    background: #f8fafc; border: 1.5px solid #cbd5e1;
    border-right: none; color: #94a3b8; font-size: .9rem;
  }

  .form-control {
    font-family: 'Inter', sans-serif;
    font-size: .9rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 0 8px 8px 0 !important;
    padding: .68rem .9rem;
    color: #0f172a;
    transition: border-color .2s, box-shadow .2s;
  }

  .form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.15);
    outline: none;
    z-index: 2;
  }

  .input-group .input-group-text { border-radius: 8px 0 0 8px !important; }

  .btn-login {
    width: 100%;
    padding: .8rem;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: .9rem;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: .01em;
    box-shadow: 0 4px 14px rgba(99,102,241,.4);
    transition: all .18s;
    margin-top: 1.4rem;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
  }

  .btn-login:hover {
    background: #4f46e5;
    box-shadow: 0 6px 20px rgba(99,102,241,.5);
    transform: translateY(-1px);
  }

  .btn-login:active { transform: scale(.98); }

  .alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-left: 4px solid #dc2626;
    border-radius: 10px;
    padding: .75rem 1rem;
    font-size: .84rem;
    margin-bottom: 1.2rem;
    display: flex; align-items: center; gap: .5rem;
  }

  .login-footer {
    text-align: center;
    font-size: .73rem;
    color: #94a3b8;
    padding-top: 1.2rem;
  }

  @media (min-width: 900px) {
    .login-left  { display: flex; }
    .login-right { width: 480px; flex-shrink: 0; }
  }
</style>
</head>
<body>

<!-- Left decorative panel -->
<div class="login-left">
  <div class="left-brand">
    <div class="brand-icon">🧾</div>
    Caja
  </div>
  <div>
    <h2 style="color:#f1f5f9;font-size:1.5rem;font-weight:800;letter-spacing:-.03em;margin-bottom:.75rem;position:relative;z-index:1;">
      Controla tus finanzas<br>desde el mostrador
    </h2>
    <ul class="feature-list">
      <li><i class="bi bi-lightning-charge-fill"></i> Registra ventas y gastos al instante</li>
      <li><i class="bi bi-graph-up-arrow"></i> Reportes y gráficas en tiempo real</li>
      <li><i class="bi bi-phone-fill"></i> Optimizado para móvil</li>
      <li><i class="bi bi-shield-lock-fill"></i> Acceso seguro con contraseña</li>
    </ul>
  </div>
  <p style="color:#334155;font-size:.75rem;position:relative;z-index:1;">Caja v1.0 · Sistema de gestión financiera</p>
</div>

<!-- Right login panel -->
<div class="login-right">
  <div class="login-box">
    <div class="login-card">
      <div class="login-card-header">
        <div class="login-logo">🧾</div>
        <h1>Bienvenido</h1>
        <p>Inicia sesión para continuar</p>
      </div>

      <div class="login-card-body">
        <?php if ($error): ?>
          <div class="alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?= h($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label d-block mb-1">CORREO ELECTRÓNICO</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" class="form-control"
                     value="<?= h($_POST['email'] ?? '') ?>"
                     placeholder="admin@caja.com"
                     autocomplete="email" required>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label d-block mb-1">CONTRASEÑA</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" name="password" class="form-control"
                     placeholder="••••••••"
                     autocomplete="current-password" required>
            </div>
          </div>

          <button type="submit" class="btn-login">
            <i class="bi bi-arrow-right-circle"></i>
            Entrar
          </button>
        </form>

        <p class="login-footer">Solo personal autorizado · Caja v1.0</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
