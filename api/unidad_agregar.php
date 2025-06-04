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
  if (!isset($input["Unidad"]) || trim($input["Unidad"]) === '') {
    throw new Exception("El nombre de la Unidad es obligatorio.");
  }

  $nombre = trim($input["Unidad"]);

  // Verificar si ya existe
  $verificar = $conexion->prepare("SELECT Id FROM Frm_Ct_Unidades WHERE Unidad = ?");
  $verificar->bind_param("s", $nombre);
  $verificar->execute();
  $verificar->store_result();
  if ($verificar->num_rows > 0) {
    throw new Exception("La unidad ya existe.");
  }
  $verificar->close();

  // Insertar
  $stmt = $conexion->prepare("INSERT INTO Frm_Ct_Unidades (Unidad) VALUES (?)");
  $stmt->bind_param("s", $nombre);
  $stmt->execute();
  $nuevoId = $stmt->insert_id;
  $stmt->close();

  echo json_encode([
    "success" => true,
    "nuevoId" => $nuevoId
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Error: " . $e->getMessage()
  ]);
}
