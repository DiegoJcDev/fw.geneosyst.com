<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['Id']) || !isset($input['IdUsuarioGs'])) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros obligatorios"]);
    exit;
}

$Id = intval($input['Id']);
$IdUsuarioGs = intval($input['IdUsuarioGs']);
$IdTipoEmpresa = 4;

try {
    $stmt = $conexion->prepare("
        UPDATE Frm_Ct_Empresas
        SET Estatus = 'BAJA'
        WHERE Id = ? AND IdUsuarioGs = ? AND IdTipoEmpresa = ?
    ");
    $stmt->bind_param("iii", $Id, $IdUsuarioGs, $IdTipoEmpresa);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Cliente dado de baja correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontró el cliente o ya estaba dado de baja"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error al dar de baja cliente", "error" => $e->getMessage()]);
}
?>
