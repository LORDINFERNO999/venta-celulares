<?php
/**
 * Genera la imagen de un celular para el catálogo y el checkout.
 *
 * - Si el producto trae el campo "imagen" (URL o ruta a un archivo), se usa esa foto real.
 * - Si no, se dibuja una ilustración SVG del celular con el color de la marca.
 *
 * Para usar fotos reales: agrega una columna "imagen" a la tabla productos con la
 * ruta (ej: assets/images/galaxy.png) o una URL (https://...).
 *
 * @param array  $producto  Fila del producto (nombre, marca, imagen?)
 * @param string $contexto  "card" (catálogo) o "thumb" (checkout)
 */
function imagenCelular(array $producto, string $contexto = 'card'): string
{
    $nombre = htmlspecialchars($producto['nombre'] ?? 'Celular', ENT_QUOTES);

    // 1) Si hay una foto definida, se usa.
    //    - URL (http/https): se usa siempre.
    //    - Ruta local (ej: assets/images/galaxy.jpg): solo si el archivo existe;
    //      si aún no lo has subido, se muestra la ilustración (evita el icono roto).
    $imagen = trim($producto['imagen'] ?? '');
    if ($imagen !== '') {
        $esUrl  = preg_match('#^https?://#i', $imagen) === 1;
        $existe = $esUrl || is_file(dirname(__DIR__, 2) . '/' . ltrim($imagen, '/'));
        if ($existe) {
            $src = htmlspecialchars($imagen, ENT_QUOTES);
            return '<img src="' . $src . '" alt="' . $nombre . '" loading="lazy">';
        }
    }

    // 2) Ilustración SVG con degradado según la marca.
    $marca = strtolower(trim($producto['marca'] ?? ''));
    $paleta = [
        'samsung'  => ['#3b82f6', '#0a2a8a'],
        'apple'    => ['#c7cbd4', '#5b6270'],
        'xiaomi'   => ['#ff7a2b', '#e2470a'],
        'motorola' => ['#22d3c5', '#0d7d74'],
        'huawei'   => ['#ff5b6a', '#b3122a'],
        'google'   => ['#8ab4f8', '#1a73e8'],
        'oppo'     => ['#34d399', '#0f9d6b'],
    ];
    [$c1, $c2] = $paleta[$marca] ?? ['#3b6bff', '#16309c'];

    // id único para que los degradados no choquen si hay varias tarjetas
    $uid = 'g' . substr(md5($nombre . $marca . $contexto), 0, 6);

    return <<<SVG
<svg viewBox="0 0 220 300" width="220" height="300" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="$nombre">
  <defs>
    <linearGradient id="{$uid}s" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="$c1"/>
      <stop offset="1" stop-color="$c2"/>
    </linearGradient>
    <linearGradient id="{$uid}g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#ffffff" stop-opacity="0.35"/>
      <stop offset="0.5" stop-color="#ffffff" stop-opacity="0.05"/>
      <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
    </linearGradient>
  </defs>
  <!-- cuerpo -->
  <rect x="64" y="16" width="92" height="268" rx="22" fill="#0f1117"/>
  <rect x="64" y="16" width="92" height="268" rx="22" fill="none" stroke="#ffffff" stroke-opacity="0.10" stroke-width="1.5"/>
  <!-- pantalla -->
  <rect x="71" y="23" width="78" height="254" rx="16" fill="url(#{$uid}s)"/>
  <!-- reflejo -->
  <rect x="71" y="23" width="78" height="254" rx="16" fill="url(#{$uid}g)"/>
  <!-- notch / cámara frontal -->
  <rect x="98" y="30" width="24" height="7" rx="3.5" fill="#0f1117"/>
  <!-- botón lateral -->
  <rect x="156" y="70" width="3" height="34" rx="1.5" fill="#0f1117"/>
</svg>
SVG;
}
