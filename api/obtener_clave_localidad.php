<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['ClaveEstado'])) {
  echo json_encode(["success" => false, "message" => "Falta ClaveEstado"]);
  exit;
}

$claveEstado = $input['ClaveEstado'];

// Conexión a la base de datos
require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error al conectar con la base de datos"]);
  exit;
}

// Consulta segura
$stmt = $conexion->prepare("SELECT Clave FROM Sys_Ct_LocalidadSAT WHERE ClaveEstado = ? LIMIT 1");
$stmt->bind_param("s", $claveEstado);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
  $fila = $resultado->fetch_assoc();
  echo json_encode([
    "success" => true,
    "clave" => $fila['Clave']
  ]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "No se encontró una localidad con esa clave de estado"
  ]);
}
