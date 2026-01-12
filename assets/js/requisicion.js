document.addEventListener('DOMContentLoaded', () => {

    // Referencias DOM
    const buscarInput = document.getElementById('buscar-material');
    const resultadosList = document.getElementById('resultados-busqueda');
    const materialSpan = document.getElementById('material-seleccionado');
    const stockSpan = document.getElementById('stock-disponible');
    const cantidadInput = document.getElementById('cantidad-pedir');
    const agregarBtn = document.getElementById('btn-agregar');
    const tablaCuerpo = document.getElementById('cuerpo-tabla');
    const formReq = document.getElementById('form-requisicion');
    const btnGenerar = document.getElementById('btn-generar-req');

    let itemsCarrito = [];
    let materialActual = null;

    // 1. BUSCADOR INTELIGENTE
    buscarInput.addEventListener('input', async (e) => {
        const term = e.target.value;
        resultadosList.innerHTML = '';
        
        if (term.length < 2) return;

        try {
            const res = await fetch(`api/buscar_material.php?term=${term}`);
            const materiales = await res.json();

            materiales.forEach(mat => {
                const li = document.createElement('li');
                li.style.cursor = "pointer";
                li.style.padding = "5px";
                li.style.borderBottom = "1px solid #eee";
                
                li.innerHTML = `<strong>${mat.partida_presupuestal}</strong> - ${mat.concepto} <br>
                                <small style="color:#28a745">Disponible: ${mat.cantidad_disponible} ${mat.unidad_medida}</small>`;
                
                li.addEventListener('click', () => seleccionarMaterial(mat));
                resultadosList.appendChild(li);
            });
        } catch (err) {
            console.error(err);
        }
    });

    // 2. SELECCIONAR MATERIAL
    function seleccionarMaterial(mat) {
        materialActual = mat;
        materialSpan.textContent = mat.concepto;
        stockSpan.textContent = `${mat.cantidad_disponible} ${mat.unidad_medida}`;
        stockSpan.style.color = "#28a745";
        stockSpan.style.fontWeight = "bold";
        
        cantidadInput.disabled = false;
        cantidadInput.max = mat.cantidad_disponible; 
        cantidadInput.value = 1;
        agregarBtn.disabled = false;
        
        buscarInput.value = '';
        resultadosList.innerHTML = '';
    }

    // 3. AGREGAR AL CARRITO (Visualmente)
    agregarBtn.addEventListener('click', () => {
        const cant = parseInt(cantidadInput.value);
        if (!materialActual || cant <= 0) return;
        
        if (cant > materialActual.cantidad_disponible) {
            alert(`Solo tienes ${materialActual.cantidad_disponible} unidades disponibles.`);
            return;
        }

        itemsCarrito.push({
            id: materialActual.id,
            concepto: materialActual.concepto,
            unidad: materialActual.unidad_medida,
            cantidad: cant,
            partida: materialActual.partida_presupuestal
        });

        renderTabla();
        
        // Reset inputs de selección
        materialActual = null;
        materialSpan.textContent = '-- Seleccione un material --';
        stockSpan.textContent = '--';
        cantidadInput.value = "";
        cantidadInput.disabled = true;
        agregarBtn.disabled = true;
        buscarInput.focus();
    });

    // 4. RENDERIZAR TABLA
    function renderTabla() {
        tablaCuerpo.innerHTML = '';
        if (itemsCarrito.length === 0) {
            tablaCuerpo.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#999;">No hay materiales agregados</td></tr>';
            btnGenerar.disabled = true;
            return;
        }

        btnGenerar.disabled = false;
        itemsCarrito.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.cantidad}</td>
                <td>${item.unidad}</td>
                <td>${item.concepto}</td>
                <td style="text-align:center;"><button type="button" class="btn-danger-sm" onclick="quitarItem(${idx})" style="color:red; border:none; background:none; cursor:pointer;">🗑 Eliminar</button></td>
            `;
            tablaCuerpo.appendChild(tr);
        });
    }

    window.quitarItem = (idx) => {
        itemsCarrito.splice(idx, 1);
        renderTabla();
    };

    // 5. ENVIAR REQUISICIÓN (LÓGICA CORREGIDA)
    
    // A. Prevenir que "Enter" envíe el formulario
    formReq.addEventListener('submit', (e) => {
        e.preventDefault();
    });

    // B. Manejar el click del botón "Generar"
    btnGenerar.addEventListener('click', async (e) => {
        e.preventDefault(); // Por seguridad
        
        // Validación extra: Si el botón ya dice "Generando...", no hacer nada
        if (btnGenerar.disabled && btnGenerar.textContent !== "✅ Generar Requisición y Guardar") return;

        if(itemsCarrito.length === 0) {
            alert("Debes agregar al menos un material.");
            return;
        }

        // DESHABILITAR INMEDIATAMENTE PARA EVITAR DOBLE CLICK
        btnGenerar.disabled = true;
        btnGenerar.textContent = "Generando PDF...";

        const datos = {
            motivo: document.getElementById('motivo').value,
            programa: document.getElementById('programa').value,
            proyecto: document.getElementById('proyecto').value,
            observaciones: document.getElementById('observaciones').value,
            items: itemsCarrito
        };

        try {
            const res = await fetch('api/crear_requisicion.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(datos)
            });
            
            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);
            
            const data = await res.json();

            if (data.status === 'success') {
                
                // Abrir PDF (Asegúrate que la ruta sea api/imprimir.php)
                window.open(`api/imprimir.php?id=${data.req_id}`, '_blank');
                
                if(typeof Toastify === 'function'){
                    Toastify({
                        text: `✅ Requisición creada. ID: ${data.req_id}`,
                        duration: 5000,
                        backgroundColor: "#28a745"
                    }).showToast();
                } else {
                    alert("¡Requisición guardada correctamente!");
                }
                
                // Limpiar todo
                formReq.reset();
                itemsCarrito = [];
                renderTabla();
                materialActual = null;
                materialSpan.textContent = '-- Seleccione un material --';
                stockSpan.textContent = '--';

            } else {
                alert("Error: " + (data.message || "Error desconocido"));
            }
        } catch (err) {
            console.error(err);
            alert("Error de conexión. Revisa la consola.");
        } finally {
            // Habilitar botón de nuevo (por si quiere hacer otra req)
            btnGenerar.disabled = false;
            btnGenerar.textContent = "✅ Generar Requisición y Guardar";
            renderTabla(); // Verifica si debe estar deshabilitado por estar vacío
        }
    });
});