document.addEventListener('DOMContentLoaded', function() {
    cargarUsuarios();

    // Manejar el envío del formulario de cambio de contraseña
    document.getElementById('form-password').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerText;
        btn.innerText = "Guardando...";
        btn.disabled = true;

        const formData = new FormData(this);
        
        fetch('api/users_controller.php?op=cambiar_password', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('✅ Contraseña actualizada con éxito.');
                cerrarModal();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión.');
        })
        .finally(() => {
            btn.innerText = originalText;
            btn.disabled = false;
        });
    });
});

function cargarUsuarios() {
    fetch('api/users_controller.php?op=listar')
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('lista-usuarios');
        tbody.innerHTML = '';

        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No hay usuarios registrados. Cargue un POA primero.</td></tr>';
            return;
        }

        data.forEach(user => {
            // Etiqueta de Rol
            let rolLabel = user.rol === 'admin' || user.rol === 'director_planeacion' 
                ? '<span style="color:blue; font-weight:bold;">Administrador</span>' 
                : 'Usuario POA';

            // Fila de la tabla
            const row = `
                <tr>
                    <td>${user.id}</td>
                    <td style="font-weight:500;">${user.nombre_usuario}</td>
                    <td>${rolLabel}</td>
                    <td>${user.activo == 1 ? '🟢 Activo' : '🔴 Inactivo'}</td>
                    <td style="text-align: center;">
                        <button onclick="abrirModalPassword(${user.id}, '${user.nombre_usuario}')" class="btn-password">
                            🔑 Cambiar Contraseña
                        </button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    })
    .catch(err => {
        console.error(err);
        document.getElementById('lista-usuarios').innerHTML = '<tr><td colspan="5" style="color:red; text-align:center">Error al cargar datos.</td></tr>';
    });
}

// Abrir el modal y poner los datos del usuario seleccionado
function abrirModalPassword(id, nombre) {
    document.getElementById('user_id').value = id;
    document.getElementById('user_name_display').innerText = nombre;
    document.getElementById('new_password').value = ''; // Limpiar campo
    document.getElementById('modalPassword').style.display = "block";
    document.getElementById('new_password').focus();
}

function cerrarModal() {
    document.getElementById('modalPassword').style.display = "none";
}

// Cerrar si se da clic fuera del modal
window.onclick = function(event) {
    const modal = document.getElementById('modalPassword');
    if (event.target == modal) {
        cerrarModal();
    }
}