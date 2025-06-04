<?php
// Mostrar errores para depuración (solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeceras necesarias para CORS y JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Leer y validar JSON de entrada
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
  echo json_encode(["success" => false, "message" => "JSON inválido"]);
  exit;
}

if (!isset($input['Clave'])) {
  echo json_encode(["success" => false, "message" => "Falta parámetro 'Clave'"]);
  exit;
}

$clave = $input['Clave'];

// Conexión a base de datos
require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error al conectar con la base de datos"]);
  exit;
}

// Consulta preparada
$stmt = $conexion->prepare("SELECT Descripcion FROM Sys_Ct_UnidadAduanaSAT WHERE Clave = ? LIMIT 1");
$stmt->bind_param("s", $clave);
$stmt->execute();

// Vincular resultados
$stmt->bind_result($descripcion);

if ($stmt->fetch()) {
  echo json_encode([
    "success" => true,
    "descripcion" => $descripcion
  ]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "No se encontró un Incoterm con esa clave"
  ]);
}

// Cerrar conexiones
$stmt->close();
$conexion->close();
