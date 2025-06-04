<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$result = $conexion->query("SELECT id, Email AS email, fecha_registro FROM Sys_Ct_Usuarios WHERE verificado = 1 AND id_distribuidor IS NULL");

$usuarios = [];
while ($row = $result->fetch_assoc()) {
  $usuarios[] = $row;
}

echo json_encode(["usuarios" => $usuarios]);
