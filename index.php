<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/assets/inc/imagen-celular.php';

try {
    $pdo = getDB();
    $productos = $pdo->query('SELECT * FROM productos ORDER BY id')->fetchAll();
} catch (Throwable $e) {
    // Si aún no has creado la base de datos, se muestra el catálogo de ejemplo.
    // El campo "imagen" es opcional: si lo dejas vacío se dibuja una ilustración
    // del celular con el color de la marca. Ponle una ruta o URL para usar foto real.
    $productos = [
        ['id' => 1, 'nombre' => 'Galaxy Signal X12', 'marca' => 'Samsung', 'descripcion' => 'Pantalla AMOLED 120Hz, cámara triple 108MP', 'ram' => '8GB', 'almacenamiento' => '256GB', 'bateria' => '5000mAh', 'pantalla' => '6.7" AMOLED', 'precio' => 2190000, 'stock' => 14, 'imagen' => ''],
        ['id' => 2, 'nombre' => 'Pulse P40 Lite', 'marca' => 'Xiaomi', 'descripcion' => 'Carga rápida 67W, cuerpo ultraliviano', 'ram' => '6GB', 'almacenamiento' => '128GB', 'bateria' => '4500mAh', 'pantalla' => '6.5" IPS', 'precio' => 1090000, 'stock' => 22, 'imagen' => ''],
        ['id' => 3, 'nombre' => 'Orbit One 5G', 'marca' => 'Motorola', 'descripcion' => 'Conectividad 5G, resistente a salpicaduras', 'ram' => '8GB', 'almacenamiento' => '256GB', 'bateria' => '5000mAh', 'pantalla' => '6.6" LCD', 'precio' => 1650000, 'stock' => 9, 'imagen' => ''],
        ['id' => 4, 'nombre' => 'Aria S Pro', 'marca' => 'Apple', 'descripcion' => 'Chip A-series, sistema de cámaras Pro', 'ram' => '6GB', 'almacenamiento' => '256GB', 'bateria' => '4325mAh', 'pantalla' => '6.1" Super Retina', 'precio' => 4890000, 'stock' => 5, 'imagen' => ''],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tienda de Celulares — Pago seguro con PayU</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="topbar">
  <div class="brand">signal<span>.</span>store</div>
  <div class="tag">Pago protegido · PayU Latam</div>
</header>

<section class="hero">
  <h1>Celulares nuevos,<br><em>pago en un solo paso</em>.</h1>
  <p>Elige tu equipo y paga directamente con PSE, tarjeta de crédito o efectivo a través de la pasarela PayU. Sin registros largos, sin vueltas.</p>
  <div class="badges">
    <span>ENVÍO A TODA COLOMBIA</span>
    <span>PAGO 100% ENCRIPTADO</span>
    <span>GARANTÍA DE FÁBRICA</span>
  </div>
</section>

<section class="catalogo">
  <?php foreach ($productos as $p): ?>
    <article class="card">
      <div class="media"><?= imagenCelular($p, 'card') ?></div>
      <div class="body">
        <span class="marca"><?= htmlspecialchars($p['marca']) ?></span>
        <h3><?= htmlspecialchars($p['nombre']) ?></h3>
        <p class="desc"><?= htmlspecialchars($p['descripcion']) ?></p>

        <div class="specs">
          <div>RAM <b><?= htmlspecialchars($p['ram']) ?></b></div>
          <div>ALM <b><?= htmlspecialchars($p['almacenamiento']) ?></b></div>
          <div>BAT <b><?= htmlspecialchars($p['bateria']) ?></b></div>
          <div>PANT <b><?= htmlspecialchars($p['pantalla']) ?></b></div>
        </div>

        <?php if ($p['stock'] <= 6): ?>
          <span class="stock-baja">quedan <?= (int)$p['stock'] ?> unidades</span>
        <?php endif; ?>

        <div class="footer">
          <div class="precio">
            $<?= number_format($p['precio'], 0, ',', '.') ?>
            <small>COP · IVA incluido</small>
          </div>
          <a href="checkout-shopify.php?producto_id=<?= (int)$p['id'] ?>" class="btn-comprar" style="text-decoration:none; display:inline-block;">Comprar</a>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<div class="seguridad">
  <span>🔒 Transacciones procesadas por PayU Latam, PCI-DSS nivel 1.</span>
  <span>Aceptamos PSE, Visa, Mastercard, Efecty y Baloto.</span>
</div>

</body>
</html>
