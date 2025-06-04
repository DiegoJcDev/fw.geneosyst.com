<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once("/home/adminsys/conexion.php");

$input = json_decode(file_get_contents("php://input"), true);
$tipo = $input['tipo'] ?? '';

if (!in_array($tipo, ['mensual', 'anual'])) {
  echo json_encode(["success" => false, "message" => "Tipo de suscripción inválido."]);
  exit;
}

$conexion = obtenerConexion();

$sql = "
SELECT Precio, EsPromocion, StripePriceId
FROM Sys_Ct_PreciosSuscripcion
WHERE TipoSuscripcion = ?
  AND Activo = 1
  AND CURDATE() BETWEEN FechaInicio AND IFNULL(FechaFin, CURDATE())
ORDER BY EsPromocion DESC, FechaInicio DESC
LIMIT 1;
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $tipo);
$stmt->execute();
$stmt->bind_result($precio, $esPromo, $stripePriceId);

if ($stmt->fetch()) {
  echo json_encode([
    "success" => true,
    "precio" => $precio,
    "esPromocion" => $esPromo,
    "stripePriceId" => $stripePriceId
  ]);
} else {
  echo json_encode(["success" => false, "message" => "No hay precio vigente configurado."]);
}

$stmt->close();
$conexion->close();
?>
