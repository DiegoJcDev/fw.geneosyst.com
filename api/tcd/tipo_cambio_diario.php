<?php
// Configuración de la API de Banxico
$apiKey = '111e62632fd36bcae3f53227d7d6f8523a1de9a68549b88083d9df7fc6b04144';  // Sustituye 'TU_CLAVE_API' con tu clave API de Banxico

// Identificadores de las series
$serieUSD = 'SF43718';   // Identificador de la serie USD/MXN (FIX)
$serieEUR = 'SF46410';   // Identificador de la serie EUR/MXN

// Fecha actual para insertar
$fecha = date('Y-m-d');

// Función para obtener el tipo de cambio desde la API de Banxico
function obtenerTipoCambio($serie, $apiKey) {
    // URL de la API de Banxico para obtener el dato oportuno
    $url = "https://www.banxico.org.mx/SieAPIRest/service/v1/series/$serie/datos/oportuno?token=$apiKey";

    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Ejecutar la solicitud
    $response = curl_exec($ch);

    // Comprobar si hubo algún error con la solicitud cURL
    if (curl_errno($ch)) {
        echo 'Error en la solicitud cURL: ' . curl_error($ch);
        exit;
    }

    // Cerrar cURL
    curl_close($ch);

    // Decodificar la respuesta JSON
    $data = json_decode($response, true);

    // Verificar si la respuesta contiene datos
    if (isset($data['bmx']['series'][0]['datos'][0])) {
        // Obtener el valor del tipo de cambio
        $valor = $data['bmx']['series'][0]['datos'][0]['dato'];
        // Verificar que el valor no sea 'N/E' (No Disponible)
        if ($valor !== 'N/E') {
            return $valor;
        } else {
            echo "Dato no disponible para la serie $serie.\n";
            return null;
        }
    } else {
        echo "No se encontraron datos para la serie $serie.\n";
        return null;
    }
}

// Obtener los tipos de cambio de USD/MXN y EUR/MXN
$tipoCambioUSD = obtenerTipoCambio($serieUSD, $apiKey);
$tipoCambioEUR = obtenerTipoCambio($serieEUR, $apiKey);

// Verificar si se obtuvieron los tipos de cambio
if ($tipoCambioUSD && $tipoCambioEUR) {
    // Conectar a la base de datos MySQL (ajusta los parámetros)
    require_once("/home/adminsys/conexion.php");
    $conn = obtenerConexion();

    // Verificar la conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Insertar el tipo de cambio USD/MXN en la base de datos
    $stmtUSD = $conn->prepare("INSERT INTO TipoCambio (fecha, moneda_origen, moneda_destino, valor) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $monedaOrigenUSD = 'USD';
    $monedaDestinoUSD = 'MXN';
    $stmtUSD->bind_param("sssd", $fecha, $monedaOrigenUSD, $monedaDestinoUSD, $tipoCambioUSD);

    if ($stmtUSD->execute()) {
        echo "Tipo de cambio USD/MXN ($tipoCambioUSD) registrado correctamente.\n";
    } else {
        echo "Error al insertar el tipo de cambio USD/MXN: " . $stmtUSD->error . "\n";
    }

    // Insertar el tipo de cambio EUR/MXN en la base de datos
    $stmtEUR = $conn->prepare("INSERT INTO TipoCambio (fecha, moneda_origen, moneda_destino, valor) 
                                VALUES (?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $monedaOrigenEUR = 'EUR';
    $monedaDestinoEUR = 'MXN';
    $stmtEUR->bind_param("sssd", $fecha, $monedaOrigenEUR, $monedaDestinoEUR, $tipoCambioEUR);

    if ($stmtEUR->execute()) {
        echo "Tipo de cambio EUR/MXN ($tipoCambioEUR) registrado correctamente.\n";
    } else {
        echo "Error al insertar el tipo de cambio EUR/MXN: " . $stmtEUR->error . "\n";
    }

    // Cerrar la conexión
    $stmtUSD->close();
    $stmtEUR->close();
    $conn->close();
} else {
    echo "No se pudieron obtener los tipos de cambio.";
}
?>
