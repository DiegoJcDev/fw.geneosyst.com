<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // en producción, mantener en 0

header("Access-Control-Allow-Origin: *");

$rfc = isset($_GET['rfc']) ? trim($_GET['rfc']) : '';
$idUsuario = isset($_GET['idUsuario']) ? intval($_GET['idUsuario']) : 0;

if (!$rfc || !$idUsuario) {
    http_response_code(400);
    exit("Parámetros inválidos.");
}

require_once("/home/adminsys/conexion.php");
$conexion = obtenerConexion();

$stmt = $conexion->prepare("SELECT Logo FROM Frm_Ct_Empresas WHERE RfcNoIdFiscal = ? AND IdUsuarioGs = ? AND IdTipoEmpresa = 1");
$stmt->bind_param("si", $rfc, $idUsuario);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($logoBin);
    $stmt->fetch();

    if ($logoBin) {
        header("Content-Type: image/png"); // o image/jpeg si usas JPG
        echo $logoBin;
        exit;
    }
}

http_response_code(404);
echo "Logo no encontrado.";
?>
