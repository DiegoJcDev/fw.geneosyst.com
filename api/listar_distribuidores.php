<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$result = $conexion->query("SELECT id, nombre FROM distribuidores WHERE activo = 1");

$distribuidores = [];
while ($row = $result->fetch_assoc()) {
  $distribuidores[] = $row;
}

echo json_encode(["distribuidores" => $distribuidores]);
