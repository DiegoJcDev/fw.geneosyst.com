document.getElementById("loginForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const errorDiv = document.getElementById("error");

  errorDiv.classList.add("hidden");
  errorDiv.textContent = "";

  if (!email || !password) {
    errorDiv.textContent = "Por favor, completa todos los campos.";
    errorDiv.classList.remove("hidden");
    return;
  }

  try {
    const response = await fetch("https://fw.geneosyst.com/api/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ email, password })
    });

    const result = await response.json();

    if (result.success) {
      localStorage.setItem("usuario_email", email);
      localStorage.setItem("usuario_id", result.id);
      localStorage.setItem("usuario_rfc", result.rfc);
      localStorage.setItem("usuario_msgbv", result.msgbv);
      window.location.href = "/panel";
    }
     else {
      errorDiv.textContent = result.message || "Error en el inicio de sesión";
      errorDiv.classList.remove("hidden");
    }
  } catch (error) {
    errorDiv.textContent = "Error de conexión con el servidor.";
    errorDiv.classList.remove("hidden");
  }
});
