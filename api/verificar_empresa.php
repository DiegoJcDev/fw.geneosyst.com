<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$response = ["success" => false, "mostrar_modal" => false];

try {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['idUsuario'])) {
        throw new Exception("Falta parámetro idUsuario.");
    }

    $idUsuario = intval($input['idUsuario']);

    // Conexión segura a la base de datos (ajusta los datos reales)
    require_once("/home/adminsys/conexion.php");
    $mysqli = obtenerConexion();
    if ($mysqli->connect_errno) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }

    $sql = "SELECT COUNT(*) AS total FROM Frm_Ct_Empresas WHERE IdUsuarioGs = ? AND IdTipoEmpresa = 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta.");
    }

    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    $mysqli->close();

    // Mostrar el modal si no hay empresa registrada con IdTipoEmpresa = 1
    if ($total == 0) {
        $response["success"] = true;
        $response["mostrar_modal"] = true;
    } else {
        $response["success"] = true;
    }

} catch (Exception $e) {
    // Opcional: log del error
    $response["error"] = $e->getMessage();
}

echo json_encode($response);
