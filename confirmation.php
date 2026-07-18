<?php
require_once __DIR__ . '/config.php';

/**
 * PayU llama a esta URL por SERVER-SIDE (no la ve el usuario) para confirmar
 * el estado final de la transacción. Aquí es donde debes actualizar el stock,
 * activar la entrega, etc. NUNCA confíes solo en response.php para eso: ese
 * archivo lo controla el navegador del comprador y se puede manipular.
 */

$referenceCode  = $_POST['reference_sale'] ?? '';
$transactionId  = $_POST['transaction_id'] ?? '';
$newState       = $_POST['state_pol'] ?? ''; // 4 = APROBADA, 6 = RECHAZADA, 5 = EXPIRADA, 7 = PENDIENTE
$value           = $_POST['value'] ?? '';
$currency        = $_POST['currency'] ?? '';
$sign            = $_POST['sign'] ?? '';

if (!$referenceCode || !$sign) {
    http_response_code(400);
    exit('Datos incompletos');
}

// El valor debe tener un decimal para esta firma (según especificación PayU)
$valorFirma = number_format((float) $value, 1, '.', '');

$firmaEsperada = md5(
    PAYU_API_KEY . '~' . PAYU_MERCHANT_ID . '~' . $referenceCode . '~' . $valorFirma . '~' . $currency . '~' . $newState
);

if (!hash_equals($firmaEsperada, $sign)) {
    http_response_code(400);
    error_log("PayU confirmation: firma inválida para {$referenceCode}");
    exit('Firma inválida');
}

$estados = [
    '4' => 'APROBADA',
    '5' => 'EXPIRADA',
    '6' => 'RECHAZADA',
    '7' => 'PENDIENTE',
];
$estado = $estados[$newState] ?? 'ERROR';

$pdo = getDB();
$stmt = $pdo->prepare(
    'UPDATE ordenes SET estado = ?, payu_transaction_id = ? WHERE reference_code = ?'
);
$stmt->execute([$estado, $transactionId, $referenceCode]);

// Aquí puedes disparar lo que necesites cuando el pago quede APROBADA:
// enviar el correo con las credenciales, descontar stock, avisar por Telegram, etc.
// if ($estado === 'APROBADA') { ... }

http_response_code(200);
echo 'OK';
