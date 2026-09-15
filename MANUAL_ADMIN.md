# Manual de Administración y Soporte Técnico (`admin.php`)

El panel de administración permite gestionar la base de datos de productos (CSV), configurar los parámetros de red y modificar las rutas de almacenamiento de planos técnicos.

- **Acceso:** `https://[IP_O_DOMINIO]/barcode/admin.php`
- **Contraseña:** Configurada en `api/config.json` (`admin_password`).

---

## 1. Actualización de Base de Datos de Productos (CSV)

### Paso 1: Cargar un nuevo archivo mensual
1. Ingrese a `admin.php` e inicie sesión.
2. Desplácese a la sección **"📤 Subir Nuevo CSV"**.
3. Haga clic en *Seleccionar archivo* y elija el CSV provisto por Planeamiento (ej: `dic26.csv`).
4. Haga clic en el botón **Subir**.

> **FORMATO OBLIGATORIO DEL CSV:**
> - Separador de campos: Punto y coma (`;`).
> - Codificación: UTF-8 (sin BOM).
> - Columnas requeridas (mínimo 3):
>   `Columna 1: Código Artículo` | `Columna 2: Descripción` | `Columna 3: Código EAN / Barras`

### Paso 2: Activar la nueva base de datos
1. En la sección superior **"Configuración"**, localice el selector **"📄 Base de Datos Activa (CSV)"**.
2. Seleccione el archivo recién subido (ej: `dic26.csv`).
3. **CRÍTICO:** Haga clic en **"💾 Guardar Config"**. Si no pulsa este botón, el scanner seguirá consultando la base de datos anterior.

---

## 2. Configuración de Rutas de Planos e Intranet Industrial

En el campo **"📂 Ruta PDFs (Servidor)"** se define dónde residen las imágenes y planos técnicos:
- **Ruta de Producción en Planta:**  
  `http://192.168.170.160/PDF-EXPGRIFERIA/Hojas-de-procesos-nuevo`
- **Botón "🔗 Probar":**  
  Envía una solicitud HTTP `HEAD` para validar si el servidor de archivos responde con código 200.

> [!NOTE]
> La verificación desde el panel prueba la visibilidad desde el servidor web hacia el servidor de archivos. Asegúrese de que las tablets también tengan enrutamiento hacia la IP `192.168.170.160`.

---

## 3. Gestión de Sufijos por Puesto de Trabajo

Permite que un mismo código de producto abra planos diferenciados según la estación:
- **Puesto 1 (Armado):** Suele utilizarse sin sufijo o con `_P1`.
- **Puesto 2 (Control):** `_P2` (Hojas de verificación dimensional y estética).
- **Puesto 3 (Empaque):** `_P3` (Diagramas de estibado y embalaje).

**Activación:**
1. Marque la casilla **"Habilitar sufijos por puesto"**.
2. Configure el sufijo para cada puesto (`P1`, `P2`, `P3` o `Ninguno`).
3. Haga clic en **"💾 Guardar Config"**.

---

## 4. Edición de Artículos en Caliente (ABM Rápido)
Si una orden de fabricación debe procesarse inmediatamente y un código no existe en el CSV:
1. En **"✏️ Editar Archivo CSV"**, elija la base activa y pulse **🔄 Recargar**.
2. Use el botón **"➕ Agregar Fila"**.
3. Ingrese el Código de Pieza, Descripción y Código de Barras EAN.
4. Haga doble clic en las celdas para modificar datos existentes.
5. El guardado en la tabla es atómico y aplica bloqueos de archivo para evitar corrupción de datos.
