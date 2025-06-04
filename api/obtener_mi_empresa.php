<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

ob_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    $rfc = isset($_GET['rfc']) ? trim($_GET['rfc']) : '';
    $idUsuario = isset($_GET['idUsuario']) ? filter_var($_GET['idUsuario'], FILTER_VALIDATE_INT) : false;

    if (!$rfc) throw new Exception("El RFC es obligatorio.");
    if ($idUsuario === false) throw new Exception("El ID de usuario no es válido.");

    require_once("/home/adminsys/conexion.php");
    $conexion = obtenerConexion();
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }

    $stmt = $conexion->prepare("
        SELECT NombreOficial, IdPersonaJuridicaSAT, IdRegimenSAT, NombreVialidad, NumExt, NumInt,
               ColoniaLocalidad, CodigoPostal, CiudadEntidad, EstadoProvincia, CerTimb, KeyTimb, PswTimb,
               (Logo IS NOT NULL) AS LogoCargado
        FROM Frm_Ct_Empresas
        WHERE RfcNoIdFiscal = ? AND IdTipoEmpresa = 1 AND IdUsuarioGs = ?
    ");
    if (!$stmt) throw new Exception("Error al preparar la consulta: " . $conexion->error);

    $stmt->bind_param("si", $rfc, $idUsuario);
    if (!$stmt->execute()) throw new Exception("Error al ejecutar: " . $stmt->error);

    $stmt->bind_result(
        $NombreOficial, $IdPersonaJuridicaSAT, $IdRegimenSAT, $NombreVialidad, $NumExt, $NumInt,
        $ColoniaLocalidad, $CodigoPostal, $CiudadEntidad, $EstadoProvincia,
        $CerTimb, $KeyTimb, $PswTimb, $LogoCargado
    );

    if ($stmt->fetch()) {
        $data = [
            "NombreOficial" => $NombreOficial,
            "IdPersonaJuridicaSAT" => $IdPersonaJuridicaSAT,
            "IdRegimenSAT" => $IdRegimenSAT,
            "NombreVialidad" => $NombreVialidad,
            "NumExt" => $NumExt,
            "NumInt" => $NumInt,
            "ColoniaLocalidad" => $ColoniaLocalidad,
            "CodigoPostal" => $CodigoPostal,
            "CiudadEntidad" => $CiudadEntidad,
            "EstadoProvincia" => $EstadoProvincia,
            "PswTimb" => $PswTimb,
            "CerCargado" => $CerTimb && file_exists(__DIR__ . "/$CerTimb"),
            "KeyCargado" => $KeyTimb && file_exists(__DIR__ . "/$KeyTimb"),
            "LogoCargado" => (bool)$LogoCargado
        ];
        $response = ['success' => true, 'data' => $data];
    } else {
        $response = ['success' => false, 'message' => 'No se encontraron datos para este RFC y usuario.'];
    }

    $stmt->close();
    $conexion->close();
} catch (Exception $e) {
    error_log("Error en obtener_mi_empresa.php: " . $e->getMessage() . " | RFC: $rfc | idUsuario: $idUsuario");
    $response = ['success' => false, 'message' => $e->getMessage()];
}

if (ob_get_length()) ob_clean();
echo json_encode($response);
?>
