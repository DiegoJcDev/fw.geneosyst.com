<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function responder($success, $mensaje) {
  echo json_encode(["success" => $success, "message" => $mensaje]);
  exit;
}

try {
  $conexion = obtenerConexion();
  if ($conexion->connect_error) {
    responder(false, "Error de conexión a la base de datos.");
  }

  $input = json_decode(file_get_contents("php://input"), true);
  $Id = intval($input["Id"] ?? 0);

  if ($Id <= 0) {
    responder(false, "ID inválido.");
  }

  $sql = "UPDATE Frm_Ct_Productos SET Estatus = 'INACTIVO' WHERE Id = ?";
  $stmt = $conexion->prepare($sql);
  if (!$stmt) {
    responder(false, "Error preparando la consulta: " . $conexion->error);
  }

  $stmt->bind_param("i", $Id);
  $stmt->execute();

  if ($stmt->affected_rows > 0) {
    responder(true, "Producto marcado como INACTIVO.");
  } else {
    responder(false, "No se encontró el producto o ya estaba inactivo.");
  }

} catch (Exception $e) {
  responder(false, "Error: " . $e->getMessage());
}
