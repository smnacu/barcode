📦 ITDelivery Scanner - Sistema de Control de Producción
Desarrollado por Daruma Consulting SRL > Solución integral para trazabilidad y gestión documental en línea de montaje.
🚀 Descripción GeneralITDelivery Scanner es una Web App progresiva (PWA) diseñada para operar en tablets y dispositivos móviles dentro de entornos industriales. 
Permite a los operarios escanear códigos de barras o QR para acceder instantáneamente a la documentación técnica (Planos, PDFs) y verificar datos de producción desde una base de datos centralizada.
A diferencia de soluciones genéricas, este sistema está optimizado para gestionar eficientemente la memoria en Chrome/Android, evitando los bloqueos comunes por saturación de RAM al alternar entre cámara y visualización de documentos pesados.
✨ Funcionalidades Clave
🏭 Para el Operario (Frontend)
Escaneo de Alto Rendimiento: Lectura rápida de códigos 1D y 2D.
Gestión de Memoria Inteligente: El sistema "apaga" y libera los recursos de la cámara antes de abrir documentos pesados, garantizando estabilidad en tablets de gama media/baja.
Visualización Híbrida: * Detecta automáticamente si el PDF está en el servidor local o intranet.Visualizador integrado (sin descargas innecesarias).
Interfaz Touch-Friendly: Botones grandes y navegación fluida pensada para guantes o pantallas táctiles.
🛠 Para el Administrador (Backend)
Panel de Control Centralizado: Interfaz visual (admin.php) protegida para gestión del sistema.
ABM de Datos (CSV): 
* Edición de celdas tipo Excel directamente en el navegador.
Alta, Baja y Modificación de registros sin tocar el servidor por FTP.
Subida de nuevos archivos CSV con un clic.
Configuración Dinámica: Cambio de rutas de carpetas de PDF y selección de base de datos activa en tiempo real.
🔧 Stack TecnológicoEl proyecto prioriza la ligereza y la facilidad de despliegue:
Frontend: HTML5, CSS3 (Grid/Flexbox), JavaScript (Vanilla ES6).
Scanning Engine: Implementación optimizada de html5-qrcode.
Backend: PHP 7.4/8.0 (API REST ligera).
Base de Datos: Flat-file (CSV) para máxima portabilidad y facilidad de integración con sistemas legacy.
📥 Instalación y DespliegueRequisitos: Servidor Web (Apache/Nginx) con soporte PHP.
Estructura de Carpetas:Copiar los archivos manteniendo la estructura:
/ (root)
├── api/       (Lógica backend)
├── css/       (Estilos)
├── csv/       (Base de datos)
├── js/        (Lógica frontend y librerías)
├── Pdf/       (Repositorio de documentos)
├── admin.php (Panel de control)
└── index.php (App Operario)
Permisos (CRÍTICO):Para que el panel de administración funcione, el usuario del servidor web (www-data, apache, etc.) debe tener permisos de escritura en:Carpeta csv/ (Para guardar/subir bases de datos).Carpeta api/ (Para generar el config.json).Comando rápido: chmod -R 775 csv/ api/🛡️ Seguridad y MantenimientoSanitización: Todas las entradas vía PHP están sanitizadas para prevenir inyecciones básicas en los archivos CSV.Modo Kiosco: Se recomienda configurar el navegador de la tablet en modo "Pantalla Completa" para evitar salidas accidentales de la app.

👥 Autoría Desarrollado y mantenido por Santiago M. Nacucchio para Daruma Consulting SRL.Innovación tecnológica aplicada a procesos productivos.© 2025 Daruma Consulting SRL. Todos los derechos reservados.