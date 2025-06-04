<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();
  $input = json_decode(file_get_contents("php://input"), true);
  $IdUsuarioGs = intval($input["IdUsuarioGs"] ?? 0);
  if ($IdUsuarioGs <= 0) throw new Exception("Usuario no válido");

  $stmt = $conexion->prepare("SELECT Id, Clasificacion FROM Frm_Ct_Clasificaciones WHERE IdUsuarioGs = ? ORDER BY Clasificacion");
  $stmt->bind_param("i", $IdUsuarioGs);
  $stmt->execute();
  $stmt->bind_result($id, $clasificacion);

  $resultados = [];
  while ($stmt->fetch()) {
    $resultados[] = [
      "Id" => $id,
      "Clasificacion" => $clasificacion
    ];
  }

  echo json_encode(["success" => true, "resultados" => $resultados]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
