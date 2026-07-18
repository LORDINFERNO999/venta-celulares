<?php
/**
 * config.php
 * Credenciales de PayU Latam (Colombia) y conexión a base de datos.
 *
 * IMPORTANTE:
 * - Estos son los valores de PRUEBA (sandbox) publicados oficialmente por PayU
 *   para que cualquier desarrollador integre y pruebe sin tener cuenta real.
 * - Antes de pasar a producción, reemplaza TODO el bloque PAYU_* con las
 *   credenciales reales que te entrega PayU al aprobar tu cuenta de comercio
 *   (Panel PayU > Configuración > Llaves API).
 */

// ---------- MODO ----------
// true = sandbox (pruebas, no cobra de verdad) | false = producción
define('PAYU_TEST_MODE', true);

// ---------- CREDENCIALES PAYU ----------
define('PAYU_MERCHANT_ID', 'TU_MERCHANT_ID');
define('PAYU_ACCOUNT_ID',  'TU_ACCOUNT_ID');
define('PAYU_API_KEY',     'TU_API_KEY');
define('PAYU_API_LOGIN',   'TU_API_LOGIN');

// URLs del gateway (cambian entre sandbox y producción)
define('PAYU_CHECKOUT_URL', PAYU_TEST_MODE
    ? 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/'
    : 'https://checkout.payulatam.com/ppp-web-gateway-payu/'
);

// API de Pagos (integración directa/embebida, sin redirigir a PayU)
define('PAYU_PAYMENTS_API_URL', PAYU_TEST_MODE
    ? 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi'
    : 'https://api.payulatam.com/payments-api/4.0/service.cgi'
);

// ---------- DATOS DEL COMERCIO ----------
define('PAYU_CURRENCY', 'COP');
define('PAYU_TEST_TRANSACTION', PAYU_TEST_MODE ? 'TRUE' : 'FALSE');

// ---------- URL DEL SITIO ----------
// LOCAL: debe coincidir EXACTO con el nombre de tu carpeta dentro de htdocs.
//   Tu carpeta se llama "venta_celulares" (con guión bajo), por eso la URL es:
//   http://localhost/venta_celulares
// PRODUCCIÓN: cuando subas a Hostinger, cambia esta línea por tu dominio real,
//   por ejemplo 'https://tudominio.com'
define('SITE_URL', 'http://localhost/venta_celulares');

define('PAYU_RESPONSE_URL', SITE_URL . '/response.php');
define('PAYU_CONFIRMATION_URL', SITE_URL . '/confirmation.php');
// Nota: confirmation.php necesita ser accesible públicamente por internet para
// que PayU pueda notificarte (no funciona apuntando a localhost). En local vas
// a ver bien response.php, pero confirmation.php solo se puede probar ya en Hostinger.

// ---------- BASE DE DATOS (LOCAL) ----------
// Valores típicos de XAMPP/WAMP. Cuando subas a Hostinger, reemplaza estos 4
// por los datos de tu base de datos MySQL de Hostinger (Panel > Bases de datos).
define('DB_HOST', 'localhost');
define('DB_NAME', 'venta_celulares');
define('DB_USER', 'TU_USUARIO_BD');
define('DB_PASS', 'TU_CLAVE_BD');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}
