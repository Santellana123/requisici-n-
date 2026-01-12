document.addEventListener('DOMContentLoaded', () => {

    // =========================================================
    // 1. REFERENCIAS DEL DOM (REQUISICIONES)
    // =========================================================
    const tabla = document.getElementById('cuerpo-tabla-admin');
    const buscador = document.getElementById('buscador');
    const filtroAnio = document.getElementById('filtro-anio'); // Select para filtrar la tabla

    // =========================================================
    // 2. REFERENCIAS DEL DOM (CONFIGURACIÓN GLOBAL)
    // =========================================================
    // Estos IDs deben existir en tu admin.php
    const inputConfigAnio = document.getElementById('config-anio-sistema'); 
    const btnGuardarConfig = document.getElementById('btn-guardar-config');

    // =========================================================
    // 3. LÓGICA DE CONFIGURACIÓN GLOBAL (LO NUEVO)
    // =========================================================
    
    // A. Cargar el año activo actual al iniciar la página
    if (inputConfigAnio) {
        fetch('api/configurar_sistema.php')
            .then(res => res.json())
            .then(data => {
                if (data.anio) {
                    inputConfigAnio.value = data.anio;
                    console.log(`Sistema configurado en el año fiscal: ${data.anio}`);
                }
            })
            .catch(err => console.error("Error cargando configuración:", err));
    }

    // B. Guardar el nuevo año al hacer clic en el botón
    if (btnGuardarConfig) {
        btnGuardarConfig.addEventListener('click', async (e) => {
            e.preventDefault();

            const nuevoAnio = inputConfigAnio.value;
            
            // Validación simple
            if (!nuevoAnio || nuevoAnio.length !== 4 || isNaN(nuevoAnio)) {
                alert("⚠️ Por favor ingresa un año válido de 4 dígitos (Ej: 2025).");
                return;
            }

            const textoOriginal = btnGuardarConfig.textContent;
            btnGuardarConfig.textContent = "Guardando...";
            btnGuardarConfig.disabled = true;

            try {
                const res = await fetch('api/configurar_sistema.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ anio: nuevoAnio })
                });

                const data = await res.json();

                if (data.success) {
                    alert(`✅ ${data.message}`);
                    // Opcional: Recargar la página si quieres refrescar todo el sistema visualmente
                    // location.reload(); 
                } else {
                    alert(`❌ Error: ${data.message}`);
                }

            } catch (error) {
                console.error(error);
                alert("❌ Error de conexión con el servidor.");
            } finally {
                btnGuardarConfig.textContent = textoOriginal;
                btnGuardarConfig.disabled = false;
            }
        });
    }

    // =========================================================
    // 4. LÓGICA DE GESTIÓN DE REQUISICIONES (TABLA)
    // =========================================================

    // Función Principal de Carga
    window.cargarDatos = async function() {
        try {
            // Obtenemos el año seleccionado para el FILTRO DE VISTA (no confundir con el global)
            const anioFiltro = filtroAnio ? filtroAnio.value : new Date().getFullYear();

            // Llamamos a la API enviando el año
            const res = await fetch(`api/ver_requisiciones.php?anio=${anioFiltro}`);
            
            if (!res.ok) throw new Error("Error en la respuesta del servidor");
            
            const datos = await res.json();

            tabla.innerHTML = '';
            
            if (datos.length === 0) {
                tabla.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px; color: #666;">No hay requisiciones registradas en ${anioFiltro}.</td></tr>`;
                return;
            }

            datos.forEach(req => {
                let color = '#6c757d'; // Gris por defecto
                let textoEstado = req.estado;
                let btnAccion = ''; 

                // --- LÓGICA DE ESTADOS Y BOTONES ---
                if(req.estado === 'pendiente') {
                    color = '#ffc107'; // Amarillo
                    textoEstado = 'Pendiente';
                    
                    // BOTÓN DE ACCIÓN RÁPIDA (Solo si está pendiente)
                    btnAccion = `
                        <button onclick="marcarRealizada(${req.id})" 
                                class="btn-accion-realizada"
                                style="cursor:pointer; background:#28a745; color:white; border:none; padding:6px 10px; border-radius:4px; font-size:13px; margin-right:5px; font-weight:bold;">
                            ✅ Realizada
                        </button>
                    `;
                } 
                else if(req.estado === 'aprobado_direccion') {
                    color = '#28a745'; // Verde
                    textoEstado = 'Realizada';
                    btnAccion = `<span style="color:green; font-weight:bold; font-size:1.2em; margin-right:10px;">✓</span>`;
                }
                else if(req.estado === 'rechazado') {
                    color = '#dc3545'; // Rojo
                    textoEstado = 'Rechazada';
                }

                // Creamos la fila
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #eee';

                tr.innerHTML = `
                    <td style="padding: 10px;"><strong>${req.folio || 'S/N'}</strong></td>
                    <td style="padding: 10px;">${req.fecha_solicitud}</td>
                    <td style="padding: 10px;">${req.solicitante}</td>
                    <td style="padding: 10px;">${req.nombre_area}</td>
                    <td style="padding: 10px;">
                        <span style="background-color: ${color}; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; text-transform: uppercase; font-weight: bold;">
                            ${textoEstado}
                        </span>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        ${btnAccion}
                        <a href="api/imprimir.php?id=${req.id}" target="_blank" style="text-decoration: none; background: #0055a4; color: white; padding: 6px 10px; border-radius: 4px; font-size: 13px;">
                            👁️ Ver PDF
                        </a>
                    </td>
                `;
                tabla.appendChild(tr);
            });

        } catch (error) {
            console.error(error);
            tabla.innerHTML = '<tr><td colspan="6" style="color:red; text-align:center;">Error al cargar datos. Ver consola.</td></tr>';
        }
    };

    // Listener para recargar tabla si cambia el filtro de año
    if (filtroAnio) {
        filtroAnio.addEventListener('change', window.cargarDatos);
    }

    // --- LÓGICA DEL BUSCADOR ---
   if (buscador) {
        buscador.addEventListener('keyup', function() {
            // 1. Limpiamos el texto buscado: minúsculas y sin acentos
            const texto = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            
            const filas = tabla.getElementsByTagName('tr');
            let hayResultados = false;

            // Eliminamos mensajes previos de "No se encontraron resultados" si existen
            const msgFila = document.getElementById('fila-no-resultados');
            if(msgFila) msgFila.remove();

            for (let fila of filas) {
                // Si es una fila de mensaje (ej: "Cargando..." o "No hay datos"), la ignoramos
                if (fila.cells.length < 2) continue;

                // 2. Construimos el texto SOLO de las columnas que nos interesan
                // Índices: 0=Folio, 1=Fecha, 2=Solicitante, 3=Área, 4=Estado
                // NO incluimos la columna 5 (Acciones) para que no busque el texto de los botones
                const contenidoFila = (
                    fila.cells[0].textContent + " " + // Folio
                    fila.cells[2].textContent + " " + // Solicitante
                    fila.cells[3].textContent         // Área
                ).toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

                // 3. Comparamos
                if (contenidoFila.includes(texto)) {
                    fila.style.display = '';
                    hayResultados = true;
                } else {
                    fila.style.display = 'none';
                }
            }

            // 4. Si no hay resultados visibles, mostramos un mensaje temporal
            if (!hayResultados && filas.length > 0) {
                const tr = document.createElement('tr');
                tr.id = 'fila-no-resultados';
                tr.innerHTML = `<td colspan="6" style="text-align:center; padding:15px; color:#666;">
                                    No se encontraron coincidencias para "<strong>${this.value}</strong>"
                                </td>`;
                tabla.appendChild(tr);
            }
        });
    }

    // Iniciar carga de la tabla
    cargarDatos();
});

// =========================================================
// 5. FUNCIONES GLOBALES (Fuera del DOMContentLoaded)
// =========================================================

function marcarRealizada(id) {
    if (!confirm('¿Confirmas que esta requisición ya fue revisada y está REALIZADA?')) {
        return;
    }

    // Usamos la API de cambio de estado
    fetch('api/cambiar_estado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Recargamos la tabla para ver el cambio a verde
            if (window.cargarDatos) window.cargarDatos(); 
            else location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión');
    });
}