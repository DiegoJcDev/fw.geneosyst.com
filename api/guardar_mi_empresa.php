<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    require_once("/home/adminsys/conexion.php");
    $conexion = obtenerConexion();
    if ($conexion->connect_error) {
        throw new Exception("❌ Error de conexión a base de datos.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("❌ Solicitud inválida.");
    }

    // Capturar datos
    $razonSocial = $_POST['razonSocial'] ?? '';
    $rfc = strtoupper(trim($_POST['rfc'] ?? ''));
    $tipoPersona = $_POST['tipoPersona'] ?? '';
    $regimenFiscal = $_POST['regimenFiscal'] ?? '';
    $calle = $_POST['calle'] ?? '';
    $numExt = $_POST['numExt'] ?? '';
    $numInt = $_POST['numInt'] ?? '';
    $colonia = $_POST['colonia'] ?? '';
    $codigoPostal = $_POST['codigoPostal'] ?? '';
    $municipio = $_POST['municipio'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $contrasenaCsd = $_POST['contrasenaCsd'] ?? '';
    $idUsuario = $_POST['idUsuario'] ?? '';

    if (!$rfc) throw new Exception("❌ El RFC es obligatorio.");
    if (!$idUsuario) throw new Exception("❌ El ID de usuario es obligatorio.");

    $carpetaEmpresa = __DIR__ . "/archivos_empresas/" . $rfc;
    if (!file_exists($carpetaEmpresa)) {
        mkdir($carpetaEmpresa, 0775, true);
    }

    // Archivos
    $archivoCer = $_FILES['certificadoCer'] ?? null;
    $archivoKey = $_FILES['certificadoKey'] ?? null;
    $archivoLogo = $_FILES['logoEmpresa'] ?? null;

    $rutaCer = $rutaKey = null;
    $logoBinario = null;

    // Solo mover si se cargó un nuevo .cer
    if ($archivoCer && $archivoCer['error'] === UPLOAD_ERR_OK) {
        $rutaCer = "archivos_empresas/{$rfc}/certificado.cer";
        move_uploaded_file($archivoCer['tmp_name'], __DIR__ . "/{$rutaCer}");
    }

    // Solo mover si se cargó un nuevo .key
    if ($archivoKey && $archivoKey['error'] === UPLOAD_ERR_OK) {
        $rutaKey = "archivos_empresas/{$rfc}/certificado.key";
        move_uploaded_file($archivoKey['tmp_name'], __DIR__ . "/{$rutaKey}");
    }

    // Leer logo solo si se subió uno nuevo
    if ($archivoLogo && $archivoLogo['error'] === UPLOAD_ERR_OK) {
        $logoBinario = file_get_contents($archivoLogo['tmp_name']);
        if ($logoBinario === false) {
            throw new Exception("❌ Error al leer el archivo de logo.");
        }
    }

    // Verificar si ya existe la empresa
    $stmtVerif = $conexion->prepare("SELECT Id FROM Frm_Ct_Empresas WHERE RfcNoIdFiscal = ? AND IdTipoEmpresa = 1");
    $stmtVerif->bind_param("s", $rfc);
    $stmtVerif->execute();
    $stmtVerif->store_result();
    $existe = $stmtVerif->num_rows > 0;
    $stmtVerif->close();

    if ($existe) {
        // Armado dinámico del UPDATE
        $campos = "
            NombreOficial = ?, IdPersonaJuridicaSAT = ?, IdRegimenSAT = ?, NombreVialidad = ?,
            NumExt = ?, NumInt = ?, ColoniaLocalidad = ?, CodigoPostal = ?, CiudadEntidad = ?, EstadoProvincia = ?,
            PswTimb = ?, IdUsuarioGs = ?
        ";
        $params = [$razonSocial, $tipoPersona, $regimenFiscal, $calle, $numExt, $numInt, $colonia,
                   $codigoPostal, $municipio, $estado, $contrasenaCsd, $idUsuario];
        $types = "siissssssssi";

        if ($rutaCer !== null) {
            $campos .= ", CerTimb = ?";
            $params[] = $rutaCer;
            $types .= "s";
        }
        if ($rutaKey !== null) {
            $campos .= ", KeyTimb = ?";
            $params[] = $rutaKey;
            $types .= "s";
        }
        if ($logoBinario !== null) {
            $campos .= ", Logo = ?";
            $params[] = $logoBinario;
            $types .= "b";
        }

        $sql = "UPDATE Frm_Ct_Empresas SET $campos WHERE RfcNoIdFiscal = ? AND IdTipoEmpresa = 1";
        $params[] = $rfc;
        $types .= "s";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);

        // Enviar logo binario si aplica
        if ($logoBinario !== null) {
            $stmt->send_long_data(count($params) - 2, $logoBinario); // antes del RFC
        }
    } else {
        // INSERT completo
        if (!$rutaCer || !$rutaKey) {
            throw new Exception("❌ Debes subir el .CER y .KEY para registrar por primera vez.");
        }

        $stmt = $conexion->prepare("
            INSERT INTO Frm_Ct_Empresas 
            (NombreOficial, RfcNoIdFiscal, IdPersonaJuridicaSAT, IdRegimenSAT, NombreVialidad, NumExt, NumInt,
             ColoniaLocalidad, CodigoPostal, CiudadEntidad, EstadoProvincia, CerTimb, KeyTimb, PswTimb, Logo,
             CfdiActive, IdTipoEmpresa, IdUsuarioGs)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?)
        ");

        $stmt->bind_param("ssiissssssssssbi",
            $razonSocial, $rfc, $tipoPersona, $regimenFiscal, $calle, $numExt, $numInt, $colonia,
            $codigoPostal, $municipio, $estado, $rutaCer, $rutaKey, $contrasenaCsd, $logoBinario, $idUsuario
        );

        if ($logoBinario !== null) {
            $stmt->send_long_data(14, $logoBinario);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("❌ Error al guardar los datos: " . $stmt->error);
    }

    $stmt->close();
    $conexion->close();

    $response = ['success' => true, 'message' => 'Datos guardados correctamente'];

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>
