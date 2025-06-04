<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();
  if (!$conexion) throw new Exception("No se pudo conectar a la base de datos.");

  $id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
  if ($id <= 0) throw new Exception("ID inválido.");

  $stmt = $conexion->prepare("SELECT Descripcion FROM Frm_Ct_Caract WHERE Id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($descripcion);
  if (!$stmt->fetch()) throw new Exception("Grupo no encontrado.");
  $stmt->close();

  $items = [];
  $stmt = $conexion->prepare("SELECT Id, Nombre FROM Frm_Ct_Caract_Items WHERE IdCaract = ? ORDER BY Nombre");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($idItem, $nombre);
  while ($stmt->fetch()) {
    $items[] = ["Id" => $idItem, "Nombre" => $nombre];
  }

  echo json_encode([
    "success" => true,
    "grupo" => [
      "Id" => $id,
      "Descripcion" => $descripcion,
      "Items" => $items
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
