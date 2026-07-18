<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['producto_id'])) {
    header('Location: index.php');
    exit;
}

$productoId = (int) $_POST['producto_id'];
$pdo = getDB();

$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->execute([$productoId]);
$producto = $stmt->fetch();

if (!$producto) {
    http_response_code(404);
    die('Producto no encontrado.');
}

// referenceCode único: debe ser distinto en cada intento de compra para PayU
$referenceCode = 'CEL-' . $productoId . '-' . time() . '-' . bin2hex(random_bytes(3));

// El monto SIEMPRE se formatea con punto decimal y sin separador de miles
$valor = number_format((float) $producto['precio'], 2, '.', '');

// Registrar la orden como PENDIENTE antes de mandar al usuario a PayU
$insert = $pdo->prepare(
    'INSERT INTO ordenes (reference_code, producto_id, cantidad, valor, estado) VALUES (?, ?, 1, ?, "PENDIENTE")'
);
$insert->execute([$referenceCode, $productoId, $valor]);

// Firma exigida por PayU Web Checkout:
// MD5(ApiKey~merchantId~referenceCode~amount~currency)
$firma = md5(
    PAYU_API_KEY . '~' . PAYU_MERCHANT_ID . '~' . $referenceCode . '~' . $valor . '~' . PAYU_CURRENCY
);

$descripcion = $producto['marca'] . ' ' . $producto['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Redirigiendo a PayU…</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="estado-wrap">
  <div class="estado-card estado-pendiente">
    <div class="estado-icono">→</div>
    <h2>Conectando con PayU</h2>
    <p>Estamos redirigiéndote a la pasarela de pago segura. No cierres esta ventana.</p>
  </div>
</div>

<form id="payuForm" action="<?= PAYU_CHECKOUT_URL ?>" method="POST">
  <input name="merchantId" type="hidden" value="<?= PAYU_MERCHANT_ID ?>">
  <input name="accountId" type="hidden" value="<?= PAYU_ACCOUNT_ID ?>">
  <input name="description" type="hidden" value="<?= htmlspecialchars($descripcion) ?>">
  <input name="referenceCode" type="hidden" value="<?= $referenceCode ?>">
  <input name="amount" type="hidden" value="<?= $valor ?>">
  <input name="tax" type="hidden" value="0">
  <input name="taxReturnBase" type="hidden" value="0">
  <input name="currency" type="hidden" value="<?= PAYU_CURRENCY ?>">
  <input name="signature" type="hidden" value="<?= $firma ?>">
  <input name="test" type="hidden" value="<?= PAYU_TEST_TRANSACTION ?>">
  <input name="buyerEmail" type="hidden" value="">
  <input name="responseUrl" type="hidden" value="<?= PAYU_RESPONSE_URL ?>">
  <input name="confirmationUrl" type="hidden" value="<?= PAYU_CONFIRMATION_URL ?>">
</form>

<script>document.getElementById('payuForm').submit();</script>
</body>
</html>
