<?php
require_once __DIR__ . '/config.php';

// Estos parámetros los envía PayU en la URL cuando el comprador vuelve del pago.
// Sirven solo para mostrar mensaje en pantalla — el estado real y confiable
// llega por confirmation.php (server-to-server).
$referenceCode = $_GET['referenceCode'] ?? '';
$estadoTexto   = strtoupper($_GET['transactionState'] ?? '');
$polResponse   = $_GET['polResponseCode'] ?? '';

$mapa = [
    'APPROVED' => ['clase' => 'aprobada',  'icono' => '✓', 'titulo' => '¡Pago aprobado!', 'msg' => 'Tu pedido fue confirmado. Te enviaremos los detalles al correo registrado.'],
    'DECLINED' => ['clase' => 'rechazada', 'icono' => '✕', 'titulo' => 'Pago rechazado', 'msg' => 'La entidad bancaria rechazó la transacción. Intenta con otro medio de pago.'],
    'PENDING'  => ['clase' => 'pendiente', 'icono' => '…', 'titulo' => 'Pago pendiente', 'msg' => 'Estamos esperando la confirmación de tu banco. Te avisaremos por correo.'],
    'EXPIRED'  => ['clase' => 'rechazada', 'icono' => '✕', 'titulo' => 'Transacción expirada', 'msg' => 'El tiempo para completar el pago se agotó. Puedes intentarlo de nuevo.'],
];

$info = $mapa[$estadoTexto] ?? ['clase' => 'pendiente', 'icono' => '?', 'titulo' => 'Estado desconocido', 'msg' => 'No pudimos determinar el estado del pago. Escríbenos con tu número de referencia.'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($info['titulo']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="estado-wrap">
  <div class="estado-card estado-<?= $info['clase'] ?>">
    <div class="estado-icono"><?= $info['icono'] ?></div>
    <h2><?= htmlspecialchars($info['titulo']) ?></h2>
    <p><?= htmlspecialchars($info['msg']) ?></p>
    <?php if ($referenceCode): ?>
      <p class="mono" style="font-size:0.75rem; margin-top:16px;">Referencia: <?= htmlspecialchars($referenceCode) ?></p>
    <?php endif; ?>
    <a href="index.php">← Volver a la tienda</a>
  </div>
</div>
</body>
</html>
