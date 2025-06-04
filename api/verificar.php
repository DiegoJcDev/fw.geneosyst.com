<?php
$mensaje = "";
$exito = false;

if (!isset($_GET['token'])) {
  $mensaje = "Token no proporcionado.";
} else {
    require_once("/home/adminsys/conexion.php");
    $conexion = obtenerConexion();

  if ($conexion->connect_error) {
    $mensaje = "Error al conectar a la base de datos.";
  } else {
    $token = $conexion->real_escape_string($_GET['token']);
    $result = $conexion->query("SELECT id FROM Sys_Ct_Usuarios WHERE token_verificacion = '$token' AND verificado = 0");

    if ($result && $result->num_rows > 0) {
      $usuario = $result->fetch_assoc();
      $id = $usuario['id'];
      $conexion->query("UPDATE Sys_Ct_Usuarios SET verificado = 1, token_verificacion = NULL WHERE id = $id");
      $mensaje = "✅ Tu cuenta ha sido verificada correctamente.";
      $exito = true;
    } else {
      $mensaje = "El enlace ya expiró, o la cuenta ya fue verificada.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Verificación de cuenta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    <?php if ($exito): ?>
      setTimeout(() => {
        window.location.href = "/index.html"; // ajustá la ruta si es distinta
      }, 5000);
    <?php endif; ?>
  </script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">
  <div class="bg-white p-8 rounded-xl shadow-lg text-center max-w-md w-full">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Verificación de cuenta</h1>
    <p class="text-gray-600 text-lg mb-4"><?= $mensaje ?></p>

    <?php if ($exito): ?>
      <p class="text-sm text-gray-500">Redirigiendo al inicio de sesión en 5 segundos...</p>
    <?php else: ?>
      <a href="/index.html" class="inline-block mt-4 text-blue-600 hover:underline">Volver al inicio de sesión</a>
    <?php endif; ?>
  </div>
</body>
</html>
