<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();
  if (!$conexion) {
    throw new Exception("No se pudo conectar a la base de datos.");
  }

  $input = json_decode(file_get_contents("php://input"), true);
  $id = intval($input["Id"] ?? 0);
  if ($id <= 0) throw new Exception("ID de grupo inválido.");

  // Verificar si algún ítem del grupo está vinculado a productos
  $check = $conexion->prepare("
    SELECT COUNT(*) 
    FROM Frm_Ct_Productos_CaractItems PC
    INNER JOIN Frm_Ct_Caract_Items CI ON CI.Id = PC.IdCaractItem
    WHERE CI.IdCaract = ?
  ");
  $check->bind_param("i", $id);
  $check->execute();
  $check->bind_result($enUso);
  $check->fetch();
  $check->close();

  if ($enUso > 0) {
    throw new Exception("No se puede eliminar este grupo porque tiene ítems vinculados a productos.");
  }

  // Eliminar ítems del grupo
  $conexion->query("DELETE FROM Frm_Ct_Caract_Items WHERE IdCaract = $id");

  // Eliminar el grupo
  $stmt = $conexion->prepare("DELETE FROM Frm_Ct_Caract WHERE Id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();

  echo json_encode(["success" => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
