<?php
require_once __DIR__ . '/config.php';

$productoId = (int) ($_GET['producto_id'] ?? 0);
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->execute([$productoId]);
$producto = $stmt->fetch();

if (!$producto) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pagar con tarjeta</title>
<link rel="stylesheet" href="assets/css/checkout-embed.css">
<!-- Fingerprint anti-fraude de PayU: requerido por la Payments API -->
<script src="https://sandbox.gwenter.gtwy.com/?merchant_id=<?= PAYU_MERCHANT_ID ?>" defer></script>
</head>
<body class="checkout-embed">

<div class="co-topbar">
  <a href="index.php" class="co-back">←</a>
  <span class="co-lang">ES</span>
</div>

<div class="co-resumen">
  <span class="label">Resumen</span>
  <span class="valor"><?= PAYU_CURRENCY ?> <?= number_format($producto['precio'], 2, '.', ',') ?></span>
</div>

<div class="co-form">
  <h3>Seleccione su método de pago preferido</h3>

  <div class="co-tabs">
    <button type="button" class="co-tab" disabled title="Próximamente: integración PSE">
      <span class="icon">🏦</span>Bancos
    </button>
    <button type="button" class="co-tab activo" id="tabTarjetas">
      <span class="icon">💳</span>Tarjetas
    </button>
    <button type="button" class="co-tab" disabled title="Próximamente: Nequi, DaviPlata, etc.">
      <span class="icon">👛</span>Billeteras
    </button>
  </div>

  <form id="formTarjeta">
    <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">

    <div class="co-field">
      <input type="text" id="numero" name="numero" placeholder="Número de tarjeta" inputmode="numeric" maxlength="19" required>
      <span class="brands" id="brandDetectado">VISA · MC</span>
    </div>
    <div class="co-error" id="errNumero">Verifica el número de tarjeta.</div>

    <div class="co-row">
      <div class="co-field">
        <input type="text" id="vencimiento" name="vencimiento" placeholder="MM / AA" maxlength="5" required>
      </div>
      <div class="co-field">
        <input type="text" id="cvv" name="cvv" placeholder="CVV" inputmode="numeric" maxlength="4" required>
      </div>
    </div>

    <div class="co-field">
      <input type="text" id="nombre" name="nombre" placeholder="Nombre como aparece en la tarjeta" required>
    </div>
    <div class="co-field">
      <input type="email" id="email" name="email" placeholder="Correo electrónico" required>
    </div>

    <div class="co-error visible" id="errGeneral" style="display:none;"></div>

    <button type="submit" class="co-submit" id="btnPagar">Haga su pedido</button>
    <p class="co-seguro">🔒 Procesado de forma segura por PayU Latam</p>
  </form>
</div>

<script>
const form = document.getElementById('formTarjeta');
const btn = document.getElementById('btnPagar');
const errGeneral = document.getElementById('errGeneral');

// Formateo simple del número de tarjeta y detección de marca
document.getElementById('numero').addEventListener('input', (e) => {
  let v = e.target.value.replace(/\D/g, '').slice(0, 16);
  e.target.value = v.replace(/(.{4})/g, '$1 ').trim();
  const brandEl = document.getElementById('brandDetectado');
  if (v.startsWith('4')) brandEl.textContent = 'VISA';
  else if (/^5[1-5]/.test(v)) brandEl.textContent = 'MASTERCARD';
  else brandEl.textContent = 'VISA · MC';
});

document.getElementById('vencimiento').addEventListener('input', (e) => {
  let v = e.target.value.replace(/\D/g, '').slice(0, 4);
  if (v.length >= 3) v = v.slice(0, 2) + ' / ' + v.slice(2);
  e.target.value = v;
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errGeneral.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'Procesando…';

  const payload = {
    producto_id: form.producto_id.value,
    numero: document.getElementById('numero').value.replace(/\s/g, ''),
    vencimiento: document.getElementById('vencimiento').value.replace(/\s/g, ''),
    cvv: document.getElementById('cvv').value,
    nombre: document.getElementById('nombre').value,
    email: document.getElementById('email').value,
  };

  try {
    const res = await fetch('procesar-pago.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.ok) {
      window.location.href = 'response.php?transactionState=' + encodeURIComponent(data.estado) + '&referenceCode=' + encodeURIComponent(data.referenceCode);
    } else {
      errGeneral.textContent = data.mensaje || 'No pudimos procesar el pago. Intenta de nuevo.';
      errGeneral.style.display = 'block';
      btn.disabled = false;
      btn.textContent = 'Haga su pedido';
    }
  } catch (err) {
    errGeneral.textContent = 'Error de conexión. Intenta de nuevo.';
    errGeneral.style.display = 'block';
    btn.disabled = false;
    btn.textContent = 'Haga su pedido';
  }
});
</script>

</body>
</html>
