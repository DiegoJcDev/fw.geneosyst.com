<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once("/home/adminsys/conexion.php");

try {
  $conexion = obtenerConexion();

  if (!$conexion) {
    throw new Exception("No se pudo conectar a la base de datos");
  }

  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  if ($q === '') throw new Exception("Parámetro de búsqueda vacío");

  $sql = "
    SELECT Clave, Nombre
    FROM Sys_Ct_ClaveUnidadSAT
    WHERE Clave LIKE CONCAT('%', ?, '%') OR Nombre LIKE CONCAT('%', ?, '%')
    LIMIT 20
  ";

  $stmt = $conexion->prepare($sql);
  if (!$stmt) throw new Exception("Error en prepare(): " . $conexion->error);

  $stmt->bind_param("ss", $q, $q);
  $stmt->execute();

  $stmt->bind_result($clave, $nombre);
  $respuestas = [];
  while ($stmt->fetch()) {
    $respuestas[] = [
      "Clave" => $clave,
      "Descripcion" => $nombre
    ];
  }

  echo json_encode([
    "success" => true,
    "resultados" => $respuestas
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Error interno: " . $e->getMessage()
  ]);
}
