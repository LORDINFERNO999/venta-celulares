<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function responder(bool $ok, array $extra = []): never {
    echo json_encode(array_merge(['ok' => $ok], $extra));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    responder(false, ['mensaje' => 'Solicitud inválida.']);
}

$productoId   = (int) ($input['producto_id'] ?? 0);
$numero       = preg_replace('/\D/', '', $input['numero'] ?? '');
$vencimiento  = preg_replace('/[^\d]/', '', $input['vencimiento'] ?? ''); // MMAA
$cvv          = preg_replace('/\D/', '', $input['cvv'] ?? '');
$nombre       = trim($input['nombre'] ?? '');
$email        = trim($input['email'] ?? '');

// ---------- Validaciones mínimas ----------
if (strlen($numero) < 13 || strlen($numero) > 19) responder(false, ['mensaje' => 'Número de tarjeta inválido.']);
if (strlen($vencimiento) !== 4) responder(false, ['mensaje' => 'Vencimiento inválido (MM/AA).']);
if (strlen($cvv) < 3) responder(false, ['mensaje' => 'CVV inválido.']);
if (!$nombre) responder(false, ['mensaje' => 'Falta el nombre del titular.']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responder(false, ['mensaje' => 'Correo inválido.']);

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->execute([$productoId]);
$producto = $stmt->fetch();
if (!$producto) responder(false, ['mensaje' => 'Producto no encontrado.']);

// ---------- Detectar franquicia por el BIN ----------
$paymentMethod = match (true) {
    preg_match('/^4/', $numero) === 1 => 'VISA',
    preg_match('/^5[1-5]/', $numero) === 1 => 'MASTERCARD',
    preg_match('/^3[47]/', $numero) === 1 => 'AMEX',
    default => null,
};
if (!$paymentMethod) responder(false, ['mensaje' => 'No reconocemos la franquicia de esta tarjeta.']);

$mm = substr($vencimiento, 0, 2);
$aa = substr($vencimiento, 2, 2);
$expirationDate = '20' . $aa . '/' . $mm; // formato PayU: YYYY/MM

$referenceCode = 'CEL-' . $productoId . '-' . time() . '-' . bin2hex(random_bytes(3));
$valor = number_format((float) $producto['precio'], 2, '.', '');

$pdo->prepare('INSERT INTO ordenes (reference_code, producto_id, cantidad, valor, estado, comprador_email) VALUES (?, ?, 1, ?, "PENDIENTE", ?)')
    ->execute([$referenceCode, $productoId, $valor, $email]);

// Firma de orden (misma fórmula que en Web Checkout)
$signature = md5(PAYU_API_KEY . '~' . PAYU_MERCHANT_ID . '~' . $referenceCode . '~' . $valor . '~' . PAYU_CURRENCY);

$body = [
    'language' => 'es',
    'command'  => 'SUBMIT_TRANSACTION',
    'merchant' => [
        'apiLogin' => PAYU_API_LOGIN,
        'apiKey'   => PAYU_API_KEY,
    ],
    'transaction' => [
        'order' => [
            'accountId'     => PAYU_ACCOUNT_ID,
            'referenceCode' => $referenceCode,
            'description'   => $producto['marca'] . ' ' . $producto['nombre'],
            'language'      => 'es',
            'signature'     => $signature,
            'notifyUrl'     => PAYU_CONFIRMATION_URL,
            'additionalValues' => [
                'TX_VALUE' => ['value' => $valor, 'currency' => PAYU_CURRENCY],
            ],
            'buyer' => ['emailAddress' => $email],
        ],
        'creditCard' => [
            'number'         => $numero,
            'securityCode'   => $cvv,
            'expirationDate' => $expirationDate,
            'name'           => $nombre,
        ],
        'extraParameters' => ['INSTALLMENTS_NUMBER' => 1],
        'type'             => 'AUTHORIZATION_AND_CAPTURE',
        'paymentMethod'    => $paymentMethod,
        'paymentCountry'   => 'CO',
        'ipAddress'        => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'cookie'           => session_id() ?: bin2hex(random_bytes(8)),
        'userAgent'        => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    ],
    'test' => PAYU_TEST_MODE,
];

$ch = curl_init(PAYU_PAYMENTS_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_TIMEOUT        => 30,
]);
$respuesta = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// Log sin datos sensibles (nunca guardes número completo / CVV en logs)
error_log("PayU SUBMIT_TRANSACTION ref={$referenceCode} httpErr={$curlError}");

if (!$respuesta) {
    $pdo->prepare('UPDATE ordenes SET estado = "ERROR" WHERE reference_code = ?')->execute([$referenceCode]);
    responder(false, ['mensaje' => 'No pudimos conectar con la pasarela de pago.']);
}

$data = json_decode($respuesta, true);
$estadoPayu = $data['transactionResponse']['state'] ?? 'ERROR';

$mapaEstado = [
    'APPROVED' => 'APROBADA',
    'DECLINED' => 'RECHAZADA',
    'PENDING'  => 'PENDIENTE',
    'EXPIRED'  => 'EXPIRADA',
    'ERROR'    => 'ERROR',
];
$estadoLocal = $mapaEstado[$estadoPayu] ?? 'ERROR';
$transactionId = $data['transactionResponse']['transactionId'] ?? null;

$pdo->prepare('UPDATE ordenes SET estado = ?, payu_transaction_id = ? WHERE reference_code = ?')
    ->execute([$estadoLocal, $transactionId, $referenceCode]);

if ($estadoPayu === 'APPROVED') {
    responder(true, ['estado' => 'APPROVED', 'referenceCode' => $referenceCode]);
}

$mensajeError = $data['transactionResponse']['responseMessage']
    ?? $data['error']
    ?? 'El pago no pudo ser procesado.';

responder(true, ['estado' => $estadoPayu ?: 'DECLINED', 'referenceCode' => $referenceCode, 'mensaje' => $mensajeError]);
