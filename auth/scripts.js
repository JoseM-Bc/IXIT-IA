document.addEventListener('DOMContentLoaded', function() {
    // Validación para el formulario de registro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const email = document.getElementById('email').value;
            
            // Validar que las contraseñas coincidan
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return;
            }
            
            // Validar formato de contraseña
            if (!isPasswordValid(password)) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres, una mayúscula y un número');
                return;
            }
            
            // Validar formato de email
            if (!email.includes('@')) {
                e.preventDefault();
                alert('Por favor ingresa un correo electrónico válido');
                return;
            }
        });
    }
    
    // Función para validar la contraseña
    function isPasswordValid(password) {
        // Al menos 8 caracteres
        if (password.length < 8) return false;
        
        // Al menos una mayúscula
        if (!/[A-Z]/.test(password)) return false;
        
        // Al menos un número
        if (!/[0-9]/.test(password)) return false;
        
        return true;
    }
});