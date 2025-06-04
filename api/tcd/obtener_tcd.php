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

$fecha = $input['fecha'] ?? null;
$moneda_origen = strtoupper($input['moneda_origen'] ?? null);
$moneda_destino = 'MXN'; // Fijo para este caso

if (!$fecha || !$moneda_origen) {
  echo json_encode(["success" => false, "message" => "Faltan parámetros: 'fecha' y 'moneda_origen' son requeridos"]);
  exit;
}

// Conexión a base de datos
require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error al conectar con la base de datos"]);
  exit;
}

// Consulta preparada
$stmt = $conexion->prepare("
  SELECT valor 
  FROM TipoCambio 
  WHERE fecha = ? AND moneda_origen = ? AND moneda_destino = ?
  LIMIT 1
");
$stmt->bind_param("sss", $fecha, $moneda_origen, $moneda_destino);
$stmt->execute();
$stmt->bind_result($valor);

if ($stmt->fetch()) {
  echo json_encode([
    "success" => true,
    "fecha" => $fecha,
    "moneda_origen" => $moneda_origen,
    "moneda_destino" => $moneda_destino,
    "valor" => $valor
  ]);
} else {
  echo json_encode([
    "success" => false,
    "message" => "No se encontró tipo de cambio para los parámetros proporcionados"
  ]);
}

$stmt->close();
$conexion->close();
