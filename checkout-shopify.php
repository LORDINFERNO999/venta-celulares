<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/assets/inc/imagen-celular.php';

$productoId = (int) ($_GET['producto_id'] ?? 0);
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->execute([$productoId]);
$producto = $stmt->fetch();

if (!$producto) {
    header('Location: index.php');
    exit;
}

$envio = 0; // ajusta si cobras envío
$total = (float) $producto['precio'] + $envio;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pagar pedido</title>
<link rel="stylesheet" href="assets/css/checkout-shopify.css">
<script src="https://sandbox.gwenter.gtwy.com/?merchant_id=<?= PAYU_MERCHANT_ID ?>" defer></script>
</head>
<body class="checkout-shopify">

<div class="sc-wrap">

  <!-- Resumen del pedido -->
  <aside class="sc-resumen">
    <div class="sc-producto">
      <div class="thumb"><?= imagenCelular($producto, 'thumb') ?><span class="qty">1</span></div>
      <div>
        <div class="marca"><?= htmlspecialchars($producto['marca']) ?></div>
        <div class="nombre"><?= htmlspecialchars($producto['nombre']) ?></div>
      </div>
      <div class="precio">$<?= number_format($producto['precio'], 0, ',', '.') ?></div>
    </div>

    <div class="sc-totales">
      <div class="fila"><span>Subtotal</span><span>$<?= number_format($producto['precio'], 0, ',', '.') ?></span></div>
      <div class="fila"><span>Envío</span><span><?= $envio > 0 ? '$'.number_format($envio, 0, ',', '.') : 'Gratis' ?></span></div>
      <div class="total">
        <span>Total<small>Impuestos incluidos</small></span>
        <span><?= PAYU_CURRENCY ?> $<?= number_format($total, 0, ',', '.') ?></span>
      </div>
    </div>
  </aside>

  <!-- Formulario -->
  <div class="sc-form-col">
    <div class="sc-brand">signal<span style="color:#6B6B6B">.</span>store</div>

    <form id="formPago">
      <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">

      <div class="sc-section">
        <h4>Información de contacto</h4>
        <div class="sc-field">
          <label for="email">Correo electrónico</label>
          <input type="email" id="email" name="email" placeholder="tucorreo@ejemplo.com" required>
        </div>
      </div>

      <div class="sc-section">
        <h4>Envío</h4>
        <div class="sc-field">
          <label for="direccion">Dirección</label>
          <input type="text" id="direccion" placeholder="Calle, número, apto" required>
        </div>
        <div class="sc-row2">
          <div class="sc-field">
            <label for="ciudad">Ciudad</label>
            <input type="text" id="ciudad" placeholder="Medellín" required>
          </div>
          <div class="sc-field">
            <label for="telefono">Teléfono</label>
            <input type="tel" id="telefono" placeholder="300 000 0000" required>
          </div>
        </div>
      </div>

      <div class="sc-section">
        <h4>Pago</h4>
        <div class="sc-pago-box">
          <div class="sc-pago-tabs">
            <button type="button" class="activo">Tarjeta</button>
            <button type="button" disabled title="Próximamente">PSE</button>
          </div>

          <div class="sc-field">
            <label for="numero">Número de tarjeta</label>
            <input type="text" id="numero" name="numero" inputmode="numeric" maxlength="19" placeholder="1234 1234 1234 1234" required>
          </div>
          <div class="sc-row2">
            <div class="sc-field">
              <label for="vencimiento">Fecha de vencimiento</label>
              <input type="text" id="vencimiento" name="vencimiento" maxlength="5" placeholder="MM / AA" required>
            </div>
            <div class="sc-field">
              <label for="cvv">Código de seguridad</label>
              <input type="text" id="cvv" name="cvv" inputmode="numeric" maxlength="4" placeholder="CVV" required>
            </div>
          </div>
          <div class="sc-field">
            <label for="nombre">Nombre en la tarjeta</label>
            <input type="text" id="nombre" name="nombre" placeholder="Como aparece en la tarjeta" required>
          </div>
        </div>
      </div>

      <div class="sc-error" id="errGeneral"></div>

      <button type="submit" class="sc-submit" id="btnPagar">Pagar ahora</button>
      <p class="sc-seguro">🔒 Pago procesado de forma segura por PayU Latam</p>
    </form>
  </div>

</div>

<script>
const form = document.getElementById('formPago');
const btn = document.getElementById('btnPagar');
const errGeneral = document.getElementById('errGeneral');

document.getElementById('numero').addEventListener('input', (e) => {
  let v = e.target.value.replace(/\D/g, '').slice(0, 16);
  e.target.value = v.replace(/(.{4})/g, '$1 ').trim();
});

document.getElementById('vencimiento').addEventListener('input', (e) => {
  let v = e.target.value.replace(/\D/g, '').slice(0, 4);
  if (v.length >= 3) v = v.slice(0, 2) + ' / ' + v.slice(2);
  e.target.value = v;
});

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errGeneral.classList.remove('visible');
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
      errGeneral.classList.add('visible');
      btn.disabled = false;
      btn.textContent = 'Pagar ahora';
    }
  } catch (err) {
    errGeneral.textContent = 'Error de conexión. Intenta de nuevo.';
    errGeneral.classList.add('visible');
    btn.disabled = false;
    btn.textContent = 'Pagar ahora';
  }
});
</script>

</body>
</html>
