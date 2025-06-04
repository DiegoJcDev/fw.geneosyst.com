<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once("/home/adminsys/conexion.php");

try {
    $conexion = obtenerConexion();
    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    $sql = "SELECT Id ,RTRIM(Tipo) Tipo ,IFNULL(Sys,0) Sys FROM SysU_Ct_TiposProducto ORDER BY Tipo";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare(): " . $conexion->error);
    }

    $stmt->execute();
    $stmt->bind_result($id, $tipo, $sys);

    $resultados = [];
    while ($stmt->fetch()) {
        $resultados[] = [
            "Id" => $id,
            "Tipo" => $tipo
        ];
    }

    echo json_encode([
        "success" => true,
        "resultados" => $resultados
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error interno: " . $e->getMessage()
    ]);
}
