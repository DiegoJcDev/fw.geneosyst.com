<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once("/home/adminsys/conexion.php");

try {
    $conexion = obtenerConexion();
    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base de datos.");
    }

    $sql = "SELECT Id, RTRIM(NombreTipoImpuesto) AS Impuesto FROM Sys_Ct_ImpuestosTipos ORDER BY Id";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare(): " . $conexion->error);
    }

    $stmt->execute();
    $stmt->bind_result($id, $impuesto);

    $resultados = [];
    while ($stmt->fetch()) {
        $resultados[] = [
            "Id" => $id,
            "Impuesto" => $impuesto
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
