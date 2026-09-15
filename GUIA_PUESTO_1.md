# Guía Operativa de Planta - Puesto 1 (Armado y Escaneo)

Esta guía establece el procedimiento estándar para el operador del **Puesto 1** al inicio y durante el turno de producción. El Puesto 1 es el puesto máster: al escanear una pieza, actualiza automáticamente las pantallas de Control (Puesto 2) y Empaque (Puesto 3).

---

## 1. Inicio de Turno y Acceso
1. Encienda la tablet / terminal del Puesto 1.
2. Verifique que el dispositivo esté conectado a la red Wi-Fi de planta (**Peirano**).
3. Abra Google Chrome utilizando el acceso directo de su línea:
   - Línea 1: `.../accesos/linea1_puesto1.php`
   - Línea 2: `.../accesos/linea2_puesto1.php`
   - Línea 3: `.../accesos/linea3_puesto1.php`
   - Línea 4: `.../accesos/linea4_puesto1.php`
   *(Si ingresa a la raíz del sistema, confirme la configuración en el paso 2).*

---

## 2. Verificación de Identidad de Puesto
Mire el identificador ubicado en la barra superior junto al título "Scanner":
- Debe indicar **L[Número de Línea]-P1** (Ejemplo: `L1-P1`).
- Si indica `(LP)`, significa modo de bajo consumo de memoria activo (operación normal en tablets).

**Si la línea es incorrecta:**
1. Toque el ícono de engranaje (⚙️).
2. Seleccione su **Línea de Producción** (1 a 4).
3. Seleccione **Puesto 1 (Armado)**.
4. Toque **Guardar cambios**. La página se recargará automáticamente.

---

## 3. Dinámica de Trabajo y Escaneo
El sistema sincroniza automáticamente cada pieza escaneada con los puestos siguientes.

1. **Alinear Código**: Apunte el lector o la cámara hacia el código de barras / QR de la pieza o ficha de armado.
2. **Confirmación**:
   - Sonará un **BIP agudo** de éxito y el marco parpadeará en verde.
   - En el centro de la barra superior verá: `NUEVA ORDEN: [Descripción de la pieza]`.
   - Se abrirá la hoja de proceso (imagen en pantalla o plano PDF en pestaña nueva).
   - Simultáneamente, los Puestos 2 y 3 de su línea recibirán la misma orden y abrirán su correspondiente plano de control o empaque.
3. **Siguiente Pieza**:
   - Si se mostró una imagen en pantalla, toque el botón rojo de cámara (**📷**) para rehabilitar el escáner.

---

## 4. Búsqueda Manual (Etiquetas dañadas)
Si una etiqueta no puede ser leída por la cámara:
1. Toque el buscador superior (🔍).
2. Ingrese el código de artículo o parte de la descripción del producto.
3. Toque sobre el producto en la lista desplegable. El sistema procesará el ítem exactamente igual que si hubiera sido escaneado.

---

## 5. Resolución Rápida de Problemas

| Problema | Causa probable | Solución |
| :--- | :--- | :--- |
| **Bip grave y cartel rojo: "NO EN CSV"** | El código no está en la base activa del mes. | Verifique que el código físico coincida con la serie. Si es correcto, notifique al Supervisor para el alta en sistema. |
| **Los Puestos 2 y 3 no cambian de plano** | Diferencia de línea o pérdida de conexión. | 1. Confirme que los otros puestos tengan configurada la misma línea (ej: L1).<br>2. Verifique que los otros puestos tengan conexión Wi-Fi.<br>3. Pida al Puesto 2/3 que toque el botón 🔄. |
| **Cámara congelada o en negro** | Bloqueo de recurso en Android. | Toque el botón de recargar (**🔄**) en la barra superior o el botón flotante inferior. |
| **Desaparecieron los botones del sistema** | Modo pantalla completa involuntario. | Toque con un dedo el centro de la pantalla para restaurar los controles. |
