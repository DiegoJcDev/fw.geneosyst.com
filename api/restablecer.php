<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['token'], $input['password'])) {
  echo json_encode(["success" => false, "message" => "Faltan datos."]);
  exit;
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();
if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error de conexión."]);
  exit;
}

$token = $conexion->real_escape_string($input['token']);
$password = $input['password'];

$result = $conexion->query("SELECT id, token_expiracion FROM Sys_Ct_Usuarios WHERE token_recuperacion = '$token' AND token_recuperacion IS NOT NULL");

if (!$result || $result->num_rows === 0) {
  echo json_encode(["success" => false, "message" => "Token inválido o vencido."]);
  exit;
}

$usuario = $result->fetch_assoc();
$expira = strtotime($usuario['token_expiracion']);
if (time() > $expira) {
  echo json_encode(["success" => false, "message" => "El enlace ha caducado."]);
  exit;
}

// Actualizar contraseña y limpiar token
$hash = password_hash($password, PASSWORD_DEFAULT);
$id = $usuario['id'];

$conexion->query("UPDATE Sys_Ct_Usuarios SET Password = '$hash', token_recuperacion = NULL, token_expiracion = NULL WHERE id = $id");

echo json_encode(["success" => true, "message" => "Contraseña actualizada correctamente. Redirigiendo al login..."]);
