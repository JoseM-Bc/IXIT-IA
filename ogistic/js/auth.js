document.addEventListener("DOMContentLoaded", function () {
    // Registro
    const registerForm = document.querySelector("#registerForm form");
    if (registerForm) {
      registerForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const correo = registerForm.querySelector('input[type="email"]').value;
        const contrasena = registerForm.querySelector('input[type="password"]').value;
  
        fetch("registro.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: `correo=${encodeURIComponent(correo)}&contrasena=${encodeURIComponent(contrasena)}`
        })
        .then(response => response.text())
        .then(data => {
          if (data === "registro_exitoso") {
            alert("Datos registrados correctamente");
          } else {
            alert("Error en el registro: " + data);
          }
        });
      });
    }
  
    // Login
    const loginForm = document.querySelector("#loginForm form");
    if (loginForm) {
      loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const correo = loginForm.querySelector('input[type="text"], input[type="email"]').value;
        const contrasena = loginForm.querySelector('input[type="password"]').value;
  
        fetch("login.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: `correo=${encodeURIComponent(correo)}&contrasena=${encodeURIComponent(contrasena)}`
        })
        .then(response => response.text())
        .then(data => {
          if (data === "login_exitoso") {
            alert("Inicio de sesión exitoso");
          } else {
            alert("Error en el login: " + data);
          }
        });
      });
    }
  });
  

  