<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Conexión a la base de datos
require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();
if ($conexion->connect_error) {
  echo json_encode(["success" => false, "message" => "Error de conexión"]);
  exit;
}

// Consulta
$query = "SELECT Id, PersonaJuridica FROM Sys_Ct_PersonaJuridicaSAT";
$result = $conexion->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode(["success" => true, "data" => $data]);

$conexion->close();
?>
