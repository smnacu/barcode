<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#000000">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Scanner Peirano</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="css/styles.css?v=10">
</head>

<body>
    <!-- Header -->
    <header class="app-header">
    <div class="header-left">
        <img src="img/logo_peirano.png" alt="Logo" class="header-logo">
        <span class="header-title">Scanner <span id="header-badge" style="font-size: 0.7em; opacity: 0.7; border: 1px solid #444; padding: 2px 4px; border-radius: 4px;">P1</span></span>
        <button class="theme-switch" onclick="toggleTheme()" title="Cambiar tema">🌓</button>
        <button class="header-btn" onclick="toggleSidebar()" title="Mostrar/Guardar Historial">📋</button>
        <button class="header-btn" onclick="openConfig()" title="Configuración">⚙️</button>
        <button class="header-btn" onclick="location.reload()" title="Recargar">🔄</button>
    </div>

    <div class="header-center" style="flex: 1; padding: 0 15px; display: flex; justify-content: center; align-items: center; overflow: hidden;">
        <span id="nav-scanned-product" style="color: var(--success); font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></span>
    </div>

    <div class="header-right">
        <button class="header-btn" onclick="toggleMenu()" title="Menú">⋮</button>
        <div id="dropdown-menu" class="dropdown-content">
            <button class="dropdown-item" onclick="toggleSync()" id="sync-btn">📡 Sincronizar</button>
            <button class="dropdown-item" onclick="toggleLog()">📋 Ver Log</button>
            <a href="manual.html" class="dropdown-item">❓ Manual</a>
            <a href="admin.php" class="dropdown-item">🔧 Admin</a>
        </div>
    </div>
</header>
    <!-- Config Modal -->
    <div id="config-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Configuración de Puesto</h3>
                <button onclick="closeConfig()" class="close-modal">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Línea de Producción</label>
                    <select id="config-line" class="form-control">
                        <option value="1">Línea 1</option>
                        <option value="2">Línea 2</option>
                        <option value="3">Línea 3</option>
                        <option value="4">Línea 4</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Puesto de Trabajo</label>
                    <select id="config-puesto" class="form-control">
                        <option value="1">Puesto 1 (Armado)</option>
                        <option value="2">Puesto 2 (Control)</option>
                        <option value="3">Puesto 3 (Empaque)</option>
                    </select>
                </div>
                <div class="modal-info">
                    <small>El puesto determina qué variante del PDF se mostrará (ej: _P2.pdf).</small>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="saveConfig()" class="btn-save">Guardar cambios</button>
            </div>
        </div>
    </div>

    <!-- Log Panel (Hidden by default) -->
    <div class="log-panel" id="log-panel" style="display: none;">
        <div class="log-header">
            <span>DEBUG LOG</span>
            <button class="log-close" onclick="toggleLog()">✕</button>
        </div>
        <div class="log-content" id="log-content"></div>
    </div>

    <!-- App Layout: Historial + Contenido Principal -->
    <div class="app-layout">
        <!-- History Panel -->
        <aside class="history-panel" id="history-panel">
            <div class="history-header">
                <div class="history-header-left" onclick="toggleSidebar()">
                    <button class="collapse-sidebar-btn" onclick="event.stopPropagation(); toggleSidebar(true)" title="Guardar/Ocultar historial">◀ Guardar</button>
                    <span class="history-title">📋 Historial</span>
                </div>
                <button class="clear-history-btn" onclick="event.stopPropagation(); clearHistory()" title="Borrar historial">BORRAR</button>
            </div>
            <div id="history-list">
                <div class="empty-history">Sin escaneos</div>
            </div>
            <div class="footer-credit">Daruma Consulting SRL <img src="img/logo_bw.png" alt="Daruma Logo" class="footer-logo"></div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" id="manual-search" class="search-input" placeholder="Buscar código o descripción..."
                        autocomplete="off">
                    <span class="search-icon">🔍</span>
                    <div id="search-results">
                        <div id="results-list"></div>
                        <button class="close-search-btn" onclick="closeSearch()">Cerrar</button>
                    </div>
                </div>
            </div>

            <!-- Scanner View -->
            <div class="scanner-wrapper">
                <div class="scanner-container">
                    <div id="reader"></div>
                    <div class="scan-region"></div>
                    <button class="switch-camera-btn" onclick="switchCamera()" title="Cambiar cámara (Frontal / Trasera)">📷⇄</button>
                </div>
            </div>

            <!-- Status & Action Bar -->
            <div class="status-action-container">
                <div class="status-bar">
                    <div class="status-message scanning" id="status-bar">
                        <span class="status-dot"></span>
                        <span class="status-text" id="status-text">Iniciando cámara...</span>
                    </div>
                </div>
                <!-- Botón de re-escanear dedicado para Puesto 1 -->
                <button id="btn-force-rescan" class="rescan-action-btn" onclick="forceRescan()" title="Volver a escanear código inmediatamente">
                    📷 Re-escanear
                </button>
            </div>
        </main>
    </div>

    <script src="js/libs/html5-qrcode.min.js"></script>
    <script src="js/app.js?v=modular6"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('./js/sw.js').catch(function () { });
        }

        function toggleSidebar(force) {
            var isPortrait = window.matchMedia('(orientation: portrait), (max-width: 767px)').matches;
            var panel = document.getElementById('history-panel');
            var btnOpen = document.getElementById('btn-open-sidebar');

            if (isPortrait) {
                if (panel) panel.classList.toggle('expanded');
                return;
            }

            var isHidden = document.body.classList.contains('sidebar-collapsed') || 
                           (document.body.classList.contains('hide-navs') && !document.body.classList.contains('show-sidebar'));

            var shouldCollapse = (typeof force === 'boolean') ? force : !isHidden;

            if (shouldCollapse) {
                document.body.classList.add('sidebar-collapsed');
                document.body.classList.remove('show-sidebar');
                if (btnOpen) btnOpen.style.display = 'inline-flex';
            } else {
                document.body.classList.remove('sidebar-collapsed');
                if (document.body.classList.contains('hide-navs')) {
                    document.body.classList.add('show-sidebar');
                }
                if (btnOpen) btnOpen.style.display = 'none';
            }
        }

        function toggleHistory() {
            toggleSidebar();
        }

        function toggleLog() {
            var panel = document.getElementById('log-panel');
            if (panel.style.display === 'none') {
                panel.style.display = 'flex';
                setTimeout(function () { panel.classList.add('visible'); }, 10);
            } else {
                panel.classList.remove('visible');
                setTimeout(function () { panel.style.display = 'none'; }, 300);
            }
        }

        function toggleTheme() {
            document.body.classList.toggle('light-mode');
            localStorage.setItem('theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
        }

        // Config UI Helpers
        function openConfig() {
            document.getElementById('config-modal').style.display = 'flex';
            if (window.AppConfig) {
                document.getElementById('config-line').value = window.AppConfig.line;
                document.getElementById('config-puesto').value = window.AppConfig.puesto;
            }
        }
        function closeConfig() {
            document.getElementById('config-modal').style.display = 'none';
        }
        function saveConfig() {
            var line = document.getElementById('config-line').value;
            var puesto = document.getElementById('config-puesto').value;
            if (window.AppConfig && window.AppConfig.save) {
                window.AppConfig.save(line, puesto);
            }
            closeConfig();
        }
        function toggleSync() {
            if (window.SyncManager) window.SyncManager.toggle();
        }

        // Cargar tema guardado
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
        }
function toggleMenu() {
    document.getElementById("dropdown-menu").classList.toggle("show");
}

window.onclick = function(event) {
    if (!event.target.matches('.header-btn')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}

    </script>
    <!-- Floating Controls -->
    <button id="btn-open-sidebar" class="floating-history-btn" onclick="toggleSidebar(false)" title="Ver Historial" style="display: none;">📋 Historial</button>
    <button id="btn-scan-again" class="floating-scan-btn" onclick="forceRescan()" title="Nuevo Escaneo" style="display: none;">📷 Re-escanear</button>
    <button class="floating-reload-btn" onclick="location.reload()" title="Recargar Página">🔄</button>
</body>

</html>