document.addEventListener('DOMContentLoaded', () => {

    const loginForm = document.getElementById('login-form');
    const usuarioInput = document.getElementById('usuario');
    const passwordInput = document.getElementById('password');
    const errorContainer = document.getElementById('mensaje-error');
    const loginButton = document.getElementById('login-button');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // 1. Limpiar errores previos y bloquear botón (UX)
        errorContainer.style.display = 'none';
        errorContainer.textContent = '';
        loginButton.disabled = true;
        loginButton.textContent = 'Verificando...';

        const usuario = usuarioInput.value.trim(); // .trim() quita espacios accidentales
        const password = passwordInput.value;

        try {
            // 2. Enviar petición al servidor
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ usuario: usuario, password: password })
            });

            const data = await response.json();

            // 3. Procesar respuesta
            if (data.status === 'success') {
                // Éxito: Redirigir según el rol
                if (data.rol === 'director_planeacion') {
                    window.location.href = 'admin.php';
                } else {
                    window.location.href = 'index.php';
                }
            } else {
                // Error: Mostrar mensaje en la cajita roja
                mostrarError(data.message || 'Error desconocido.');
                // Limpiar contraseña para que intente de nuevo
                passwordInput.value = '';
                passwordInput.focus();
            }

        } catch (error) {
            console.error('Error:', error);
            mostrarError('No se pudo conectar con el servidor.');
        } finally {
            // 4. Reactivar botón
            loginButton.disabled = false;
            loginButton.textContent = 'Entrar';
        }
    });

    // Función auxiliar para mostrar el error
    function mostrarError(mensaje) {
        errorContainer.textContent = mensaje;
        errorContainer.style.display = 'block';
        
        // Efecto visual: sacudir la caja ligeramente (opcional pero se ve bien)
        errorContainer.animate([
            { transform: 'translateX(0)' },
            { transform: 'translateX(-5px)' },
            { transform: 'translateX(5px)' },
            { transform: 'translateX(0)' }
        ], {
            duration: 300,
            iterations: 1
        });
    }

});