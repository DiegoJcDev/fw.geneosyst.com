<?php
require_once __DIR__ . '/libs/stripe/init.php';

// Clave secreta de Stripe
\Stripe\Stripe::setApiKey('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'); // 🔁 REEMPLAZA esta clave por tu clave secreta real

header('Content-Type: application/json');

// Recibir los datos
$input = json_decode(file_get_contents('php://input'), true);
$priceId = $input['price_id'] ?? null;
$email = $input['email'] ?? null;

if (!$priceId || !$email) {
  echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
  exit;
}

try {
  $session = \Stripe\Checkout\Session::create([
    'mode' => 'subscription',
    'line_items' => [[
      'price' => $priceId,
      'quantity' => 1
    ]],
    'success_url' => 'https://fw.geneosyst.com/pago_exito.html',
    
    'cancel_url' => 'https://fw.geneosyst.com/pago_cancelado.html',

    'customer_email' => $email,
  ]);

  echo json_encode(['success' => true, 'url' => $session->url]);

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
