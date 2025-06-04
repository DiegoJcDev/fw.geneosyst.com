// Regex de validación de RFC (personas físicas y morales, con homoclave opcional)
function esRFCValido(rfc) {
  const rfcRegex = /^([A-ZÑ&]{3,4})\d{6}([A-Z\d]{3})?$/i;
  return rfcRegex.test(rfc);
}

document.getElementById("registerForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const rfc = document.getElementById("rfc").value.trim().toUpperCase();
  const password = document.getElementById("password").value.trim();
  const confirmPassword = document.getElementById("confirmPassword").value.trim();
  const errorDiv = document.getElementById("registerError");
  const successDiv = document.getElementById("registerSuccess");

  errorDiv.classList.add("hidden");
  errorDiv.textContent = "";
  successDiv.classList.add("hidden");
  successDiv.textContent = "";

  // Validaciones
  if (!email.includes("@") || email.length < 5) {
    errorDiv.textContent = "Por favor, ingresa un correo válido.";
    errorDiv.classList.remove("hidden");
    return;
  }

  if (!esRFCValido(rfc)) {
    errorDiv.textContent = "El RFC no es válido. Verifica el formato.";
    errorDiv.classList.remove("hidden");
    return;
  }

  if (password.length < 6) {
    errorDiv.textContent = "La contraseña debe tener al menos 6 caracteres.";
    errorDiv.classList.remove("hidden");
    return;
  }

  if (password !== confirmPassword) {
    errorDiv.textContent = "Las contraseñas no coinciden.";
    errorDiv.classList.remove("hidden");
    return;
  }

  try {
    const response = await fetch("https://fw.geneosyst.com/api/register.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password, rfc })
    });

    const result = await response.json();

    if (result.success) {
      successDiv.textContent = "✅ Registro exitoso. Revisa tu correo para activar tu cuenta.";
      successDiv.classList.remove("hidden");

      setTimeout(() => {
        window.location.href = "/login";
      }, 4000);
    } else {
      errorDiv.textContent = result.message || "Ocurrió un error al registrarse.";
      errorDiv.classList.remove("hidden");
    }
  } catch (error) {
    errorDiv.textContent = "Error de conexión con el servidor.";
    errorDiv.classList.remove("hidden");
  }
});
