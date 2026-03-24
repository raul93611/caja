<?php
session_start();
require_once 'config.php';

// Si ya está logueado, ir al dashboard
if (!empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Por favor ingresa tu correo y contraseña.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, nombre, password_hash FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Caja — Iniciar sesión</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f0f4f8;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }

  .card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,.10);
    padding: 2rem 1.5rem;
    width: 100%;
    max-width: 380px;
  }

  .logo {
    text-align: center;
    margin-bottom: 1.8rem;
  }

  .logo h1 {
    font-size: 2rem;
    color: #1a73e8;
    font-weight: 700;
    letter-spacing: -1px;
  }

  .logo p {
    color: #5f6368;
    font-size: .875rem;
    margin-top: .25rem;
  }

  label {
    display: block;
    font-size: .875rem;
    font-weight: 600;
    color: #3c4043;
    margin-bottom: .35rem;
  }

  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: .7rem .9rem;
    border: 1.5px solid #dadce0;
    border-radius: 8px;
    font-size: 1rem;
    outline: none;
    transition: border-color .2s;
  }

  input:focus {
    border-color: #1a73e8;
  }

  .field { margin-bottom: 1rem; }

  .btn {
    display: block;
    width: 100%;
    padding: .8rem;
    background: #1a73e8;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 1.2rem;
    transition: background .2s;
  }

  .btn:hover { background: #1558b0; }

  .alert {
    background: #fce8e6;
    color: #c5221f;
    border-radius: 8px;
    padding: .7rem .9rem;
    font-size: .875rem;
    margin-bottom: 1rem;
  }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>🧾 Caja</h1>
    <p>Gestión financiera de tu tienda</p>
  </div>

  <?php if ($error): ?>
    <div class="alert"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="" novalidate>
    <div class="field">
      <label for="email">Correo electrónico</label>
      <input type="email" id="email" name="email"
             value="<?= h($_POST['email'] ?? '') ?>"
             placeholder="admin@caja.com" required autocomplete="email">
    </div>
    <div class="field">
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password"
             placeholder="••••••••" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn">Entrar</button>
  </form>
</div>
</body>
</html>
