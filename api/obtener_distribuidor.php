<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (!isset($_GET['email'])) {
  echo json_encode(["success" => false, "message" => "Falta el correo."]);
  exit;
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();
if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error de conexión."]);
  exit;
}

$email = $conexion->real_escape_string($_GET['email']);

$result = $conexion->query("SELECT id_distribuidor FROM Sys_Ct_Usuarios WHERE Email = '$email' LIMIT 1");
if (!$result || $result->num_rows === 0) {
  echo json_encode(["success" => false, "message" => "Usuario no encontrado."]);
  exit;
}

$row = $result->fetch_assoc();
if (is_null($row['id_distribuidor'])) {
  echo json_encode(["success" => true, "distribuidor" => null]);
  exit;
}

$idDistribuidor = intval($row['id_distribuidor']);
$res = $conexion->query("SELECT nombre, email, telefono, direccion FROM distribuidores WHERE id = $idDistribuidor LIMIT 1");

if ($res && $res->num_rows > 0) {
  $distribuidor = $res->fetch_assoc();
  echo json_encode(["success" => true, "distribuidor" => $distribuidor]);
} else {
  echo json_encode(["success" => true, "distribuidor" => null]);
}
