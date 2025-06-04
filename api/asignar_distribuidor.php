<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['id_usuario'], $input['id_distribuidor'])) {
  echo json_encode(["success" => false, "message" => "Faltan datos"]);
  exit;
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();
if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error de conexión"]);
  exit;
}

$id_usuario = intval($input['id_usuario']);
$id_distribuidor = intval($input['id_distribuidor']);

$actualizar = $conexion->query("UPDATE Sys_Ct_Usuarios SET id_distribuidor = $id_distribuidor WHERE id = $id_usuario");

if ($actualizar) {
  echo json_encode(["success" => true, "message" => "Distribuidor asignado"]);
} else {
  echo json_encode(["success" => false, "message" => "No se pudo asignar"]);
}
