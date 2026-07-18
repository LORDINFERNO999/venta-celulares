<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Si ya está logueado, va directo al panel.
if (esta_autenticado()) {
    header('Location: pedidos.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $clave   = $_POST['clave'] ?? '';

    if (intentar_login($usuario, $clave)) {
        header('Location: pedidos.php');
        exit;
    }
    $error = 'Usuario o clave incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ingresar · Panel</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-body">

<div class="login-card">
  <div class="login-brand">signal<span>.</span>store</div>
  <p class="login-sub">Panel de pedidos · acceso del equipo</p>

  <?php if ($error): ?>
    <div class="login-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="adm-field">
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" placeholder="admin o vendedor" required autofocus>
    </div>
    <div class="adm-field">
      <label for="clave">Clave</label>
      <input type="password" id="clave" name="clave" placeholder="••••••••" required>
    </div>
    <button type="submit" class="adm-btn">Ingresar</button>
  </form>

  <a class="login-back" href="index.php">← Volver a la tienda</a>
</div>

</body>
</html>
