<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();
  $input = json_decode(file_get_contents("php://input"), true);
  $IdUsuarioGs = intval($input["IdUsuarioGs"] ?? 0);

  if ($IdUsuarioGs <= 0) {
    throw new Exception("Usuario no válido");
  }

  $stmt = $conexion->prepare("
    SELECT Id, Unidad 
    FROM Frm_Ct_Unidades 
    WHERE IdUsuarioGs = ?
    ORDER BY Unidad
  ");
  $stmt->bind_param("i", $IdUsuarioGs);
  $stmt->execute();
  $stmt->bind_result($id, $unidad);

  $resultados = [];
  while ($stmt->fetch()) {
    $resultados[] = [
      "Id" => $id,
      "Unidad" => $unidad
    ];
  }

  echo json_encode(["success" => true, "resultados" => $resultados]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
