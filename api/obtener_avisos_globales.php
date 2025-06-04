<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("/home/adminsys/conexion.php");

$conexion = obtenerConexion();
$hoy = date('Y-m-d');

$input = json_decode(file_get_contents("php://input"), true);
$idUsuario = intval($input["idUsuario"] ?? 0);

$sql = "SELECT Id, Titulo, Mensaje, Tipo, EsUrgente
        FROM Sys_Ct_AvisosGlobales
        WHERE Activo = 1
          AND FechaInicio <= ?
          AND (FechaFin IS NULL OR FechaFin >= ?)
          AND (ParaTodos = 1 OR IdUsuarioGs = ?)
        ORDER BY EsUrgente DESC, FechaInicio DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssi", $hoy, $hoy, $idUsuario);
$stmt->execute();

$stmt->store_result();
$stmt->bind_result($id, $titulo, $mensaje, $tipo, $esUrgente);

$avisos = [];
while ($stmt->fetch()) {
  $avisos[] = [
    "Id" => $id,
    "Titulo" => $titulo,
    "Mensaje" => $mensaje,
    "Tipo" => $tipo,
    "EsUrgente" => $esUrgente
  ];
}

echo json_encode([
  "success" => true,
  "avisos" => $avisos
]);

$stmt->close();
$conexion->close();
