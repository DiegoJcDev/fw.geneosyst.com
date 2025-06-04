<?php
header("Access-Control-Allow-Origin: *"); // En producción, cambia * por tu dominio
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Recibir los datos del frontend
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['email']) || !isset($input['password'])) {
  echo json_encode(["success" => false, "message" => "Faltan datos"]);
  exit;
}

// Conexión a MySQL
require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error al conectar con la base de datos"]);
  exit;
}

$email = $conexion->real_escape_string($input['email']);
$password = $input['password'];

// Buscar usuario por email
$sql = "SELECT * FROM Sys_Ct_Usuarios WHERE email = '$email'";
$resultado = $conexion->query($sql);

if ($resultado->num_rows > 0) {
  $usuario = $resultado->fetch_assoc();
  
  if (!$usuario['verificado']) {
  echo json_encode(["success" => false, "message" => "Cuenta no verificada. Revisa tu correo."]);
  exit;
  }

  // ✅ Verificación segura del password encriptado
  if (password_verify($password, $usuario['Password'])) {
        echo json_encode([
        "success" => true,
        "message" => "Login correcto",
        "id" => $usuario['Id'],
        "rfc" => $usuario['RFC'],
        "msgbv" => $usuario['MsgBienvenida']
      ]);
  } else {
    echo json_encode(["success" => false, "message" => "Contraseña incorrecta"]);
  }
} else {
  echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
}
