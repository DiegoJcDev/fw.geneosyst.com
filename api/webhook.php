<?php
require_once __DIR__ . '/libs/stripe/init.php';

\Stripe\Stripe::setApiKey('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');
$endpoint_secret = 'whsec_gyIXdzsLOhAr92RtVM6rVmcOYe31ylVf';

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\Exception $e) {
    http_response_code(400);
    exit('❌ Webhook inválido: ' . $e->getMessage());
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();
if ($conexion->connect_error) {
    http_response_code(500);
    exit('❌ Error de conexión a la base de datos');
}

// Manejamos eventos específicos
switch ($event->type) {

    case 'checkout.session.completed':
        $session = $event->data->object;

        $email = $session->customer_email ?? null;
        $subscriptionId = $session->subscription ?? null;
        $planComprado = $session->display_items[0]->price->id ?? '';

        if (!$email || !$subscriptionId) {
            http_response_code(400);
            exit('❌ Faltan datos esenciales (email o subscription ID)');
        }

        // Detectar el tipo de plan
        $precioMensual = 'price_1REa1wGRa50ySd0nxZn8OIg2';
        $precioAnual = 'price_1REa2vGRa50ySd0nxlUmwfAs';
        $tipo_suscripcion = ($planComprado === $precioAnual) ? 'Anual' : 'Mensual';

        // Obtener fecha actual y calcular nueva vigencia
        $hoy = new DateTime();
        $diasRestantes = 0;

        $stmt = $conexion->prepare("SELECT FechaTermino FROM Sys_Ct_Usuarios WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($fechaFinActual);
        if ($stmt->fetch() && $fechaFinActual) {
            $fechaFin = new DateTime($fechaFinActual);
            if ($fechaFin > $hoy) {
                $diasRestantes = $hoy->diff($fechaFin)->days;
            }
        }
        $stmt->close();

        $nuevaFechaFin = ($tipo_suscripcion === 'Anual') ? (clone $hoy)->modify('+1 year') : (clone $hoy)->modify('+1 month');
        if ($diasRestantes > 0) {
            $nuevaFechaFin->modify("+{$diasRestantes} days");
        }

        $fechaInicio = $hoy->format('Y-m-d');
        $fechaTermino = $nuevaFechaFin->format('Y-m-d');

        $stmt = $conexion->prepare("UPDATE Sys_Ct_Usuarios SET EstaEnPrueba = 0, FechaInicio = ?, FechaTermino = ?, SubscriptionId = ?, TipoSuscripcion = ? WHERE Email = ?");
        $stmt->bind_param('sssss', $fechaInicio, $fechaTermino, $subscriptionId, $tipo_suscripcion, $email);
        $stmt->execute();
        $stmt->close();

        http_response_code(200);
        echo "✅ Suscripción inicial registrada para $email hasta $fechaTermino";
        break;

    case 'invoice.payment_succeeded':
        $invoice = $event->data->object;
        $subscriptionId = $invoice->subscription;
        $customerEmail = $invoice->customer_email ?? null;

        if (!$subscriptionId || !$customerEmail) {
            http_response_code(400);
            exit('❌ Faltan datos esenciales en invoice.payment_succeeded');
        }

        $subscription = \Stripe\Subscription::retrieve($subscriptionId);
        $periodStart = date('Y-m-d', $subscription->current_period_start);
        $periodEnd = date('Y-m-d', $subscription->current_period_end);

        // Detectar plan
        $tipo_suscripcion = 'Mensual';
        foreach ($subscription->items->data as $item) {
            if ($item->price->id === 'price_1REa2vGRa50ySd0nxlUmwfAs') {
                $tipo_suscripcion = 'Anual';
            }
        }

        $stmt = $conexion->prepare("UPDATE Sys_Ct_Usuarios SET EstaEnPrueba = 0, FechaInicio = ?, FechaTermino = ?, SubscriptionId = ?, TipoSuscripcion = ? WHERE Email = ?");
        $stmt->bind_param('sssss', $periodStart, $periodEnd, $subscriptionId, $tipo_suscripcion, $customerEmail);
        $stmt->execute();
        $stmt->close();

        http_response_code(200);
        echo "✅ Renovación automática registrada para $customerEmail hasta $periodEnd";
        break;

    default:
        http_response_code(200);
        echo "🔔 Evento no procesado: {$event->type}";
        break;
}

$conexion->close();
?>
