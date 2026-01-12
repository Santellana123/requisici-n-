document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-poa');
    const inputArchivos = document.getElementById('archivos_csv');
    const inputAnio = document.getElementById('anio');
    const btn = document.getElementById('btn-subir');
    
    // UI Elements
    const progresoDiv = document.getElementById('progreso-contenedor');
    const barra = document.getElementById('barra-progreso');
    const textoProgreso = document.getElementById('texto-progreso');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (inputArchivos.files.length === 0) {
            alert("Seleccione los archivos CSV.");
            return;
        }

        btn.disabled = true;
        progresoDiv.style.display = 'block';
        textoProgreso.textContent = "Analizando archivos...";
        barra.style.width = '5%';

        let todosLosItems = [];
        let archivosProcesados = 0;
        const totalArchivos = inputArchivos.files.length;

        // Función para limpiar precios
        const limpiarPrecio = (valor) => {
            if (!valor) return 0.00;
            // Eliminar todo lo que no sea número o punto decimal
            let limpio = String(valor).replace(/[^0-9.]/g, ''); 
            return parseFloat(limpio) || 0.00;
        };

        const leerArchivo = (file) => {
            return new Promise((resolve) => {
                Papa.parse(file, {
                    header: false, // Leemos como array de arrays por posición
                    skipEmptyLines: true,
                    encoding: "UTF-8", // Importante para acentos
                    complete: (results) => {
                        const itemsValidos = [];
                        
                        // Variables para capturar los metadatos del encabezado
                        let programaActual = "";
                        let procesoActual = "";
                        let responsableActual = "";
                        
                        let encontramosTabla = false;

                        // Recorremos fila por fila
                        results.data.forEach((fila, index) => {
                            // Convertimos la fila a string para buscar palabras clave fácilmente
                            const filaStr = JSON.stringify(fila).toUpperCase();

                            // 1. CAPTURAR METADATOS (Antes de llegar a la tabla)
                            if (!encontramosTabla) {
                                // Buscar PROGRAMA (Usualmente en columna 2, índice 2)
                                if (fila[0] && fila[0].toUpperCase().includes('PROGRAMA')) {
                                    // A veces viene en col 2 (índice 2) según tu CSV
                                    programaActual = fila[2] || fila[1] || "SIN PROGRAMA";
                                }
                                // Buscar PROCESO
                                if (fila[0] && fila[0].toUpperCase().includes('PROCESO')) {
                                    procesoActual = fila[2] || fila[1] || "SIN PROCESO";
                                }
                                // Buscar RESPONSABLE
                                if (fila[0] && fila[0].toUpperCase().includes('RESPONSABLE')) {
                                    responsableActual = fila[2] || fila[1] || "SIN RESPONSABLE";
                                }

                                // 2. DETECTAR INICIO DE TABLA
                                if (fila[0] && String(fila[0]).toUpperCase().includes('PARTIDA')) {
                                    encontramosTabla = true;
                                    // Si no se encontró programa arriba, usamos el nombre del archivo como fallback
                                    if (!programaActual || programaActual === "SIN PROGRAMA") {
                                        programaActual = file.name.replace(/\.csv$/i, '').trim();
                                    }
                                }
                                return; // Saltamos esta fila, seguimos buscando
                            }

                            // 3. PROCESAR FILAS DE LA TABLA (Después de encontrar "PARTIDA")
                            /* Estructura esperada según tu CSV:
                               [0] PARTIDA (Ej: 21101)
                               [1] CANTIDAD
                               [2] CONCEPTO
                               [3] UNIDAD
                               [4] PRECIO UNITARIO
                            */

                            // Validamos que la columna 0 sea numérica (Partida)
                            if (fila.length >= 5 && fila[0] && /^\d+$/.test(fila[0].trim())) {
                                
                                const cantidad = parseInt(fila[1]); 
                                const precio = limpiarPrecio(fila[4]); 

                                itemsValidos.push({
                                    // Estos datos vienen del encabezado "flotante"
                                    programa: programaActual.trim(),
                                    proceso: procesoActual.trim(),
                                    responsable: responsableActual.trim(),
                                    
                                    // Estos datos vienen de la fila actual
                                    partida: String(fila[0]).trim(),
                                    cantidad: isNaN(cantidad) ? 0 : cantidad,
                                    concepto: fila[2] ? String(fila[2]).trim() : "Sin Concepto",
                                    unidad: fila[3] ? String(fila[3]).trim() : "Pza",
                                    precio: precio
                                });
                            }
                        });

                        resolve(itemsValidos);
                    },
                    error: (err) => {
                        console.error("Error en " + file.name, err);
                        resolve([]); 
                    }
                });
            });
        };

        // Procesar todos los archivos
        for (const file of inputArchivos.files) {
            const items = await leerArchivo(file);
            todosLosItems = todosLosItems.concat(items);
            archivosProcesados++;
            barra.style.width = Math.round((archivosProcesados / totalArchivos) * 50) + '%';
        }

        if (todosLosItems.length === 0) {
            alert("Error: No se encontraron partidas válidas. Verifique que el CSV tenga la estructura correcta (PROGRAMA, PARTIDA...).");
            btn.disabled = false;
            progresoDiv.style.display = 'none';
            return;
        }

        textoProgreso.textContent = `Guardando ${todosLosItems.length} registros en base de datos...`;
        barra.style.width = '70%';

        // Enviar a PHP
        try {
            const response = await fetch('api/importar_poa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    anio: inputAnio.value,
                    nombre_archivo: `Carga_Masiva_${totalArchivos}_Archivos`,
                    datos: todosLosItems
                })
            });

            // Verificar si la respuesta es JSON válido
            const textoRespuesta = await response.text();
            let resultado;
            try {
                resultado = JSON.parse(textoRespuesta);
            } catch (e) {
                throw new Error("El servidor devolvió una respuesta inválida: " + textoRespuesta);
            }

            barra.style.width = '100%';

            if (resultado.status === 'success') {
                if(typeof Toastify === 'function'){
                    Toastify({
                        text: `✅ ${resultado.message}`,
                        duration: 6000,
                        backgroundColor: "#28a745"
                    }).showToast();
                } else {
                    alert(resultado.message);
                }
                
                setTimeout(() => {
                    form.reset();
                    progresoDiv.style.display = 'none';
                    btn.disabled = false;
                    // Limpiar barra
                    barra.style.width = '0%';
                }, 2000);
            } else {
                throw new Error(resultado.message);
            }

        } catch (error) {
            console.error(error);
            if(typeof Toastify === 'function'){
                Toastify({
                    text: `❌ Error: ${error.message}`,
                    duration: 8000,
                    backgroundColor: "#dc3545"
                }).showToast();
            } else {
                alert("Error: " + error.message);
            }
            btn.disabled = false;
        }
    });
});