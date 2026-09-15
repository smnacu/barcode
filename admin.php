<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);
$isAuth = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Admin - Scanner ITDelivery</title>
    <style>
        :root {
            --bg-body: #050507;
            --bg-card: #0b0b0f;
            --bg-input: #111217;
            --text-main: #e6eef6;
            --text-muted: #9aa4b2;
            --brand-red: #dc2626;
            --error: #f87171;
            --warning: #f59e0b;
            --success: #22c55e;
        }

        body.light-mode {
            --bg-body: #f5f5f5;
            --bg-card: #ffffff;
            --bg-input: #f0f0f0;
            --text-main: #1a1a1a;
            --text-muted: #666666;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
        }

        /* HEADER */
        .app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--bg-card);
            border-bottom: 1px solid rgba(128, 128, 128, 0.2);
            flex-wrap: wrap;
            gap: 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-logo {
            height: 36px;
        }

        body:not(.light-mode) .header-logo {
            filter: invert(1);
        }

        .theme-switch {
            width: 32px;
            height: 32px;
            background: rgba(128, 128, 128, 0.2);
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .header-info {
            display: flex;
            flex-direction: column;
        }

        .header-title {
            font-size: 16px;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: 11px;
            color: var(--text-muted);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* BUTTONS */
        .btn {
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            color: white;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--brand-red);
        }

        .btn-primary:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: #374151;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: var(--success);
        }

        .btn-warning {
            background: var(--warning);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 12px;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ADMIN LOG */
        .admin-log {
            background: rgba(128, 128, 128, 0.1);
            color: var(--text-muted);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* TOAST */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--bg-card);
            color: var(--text-main);
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            opacity: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(128, 128, 128, 0.2);
        }

        .toast.visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .toast.success {
            border-left: 3px solid var(--success);
        }

        .toast.error {
            border-left: 3px solid var(--error);
        }

        .toast.warning {
            border-left: 3px solid var(--warning);
        }

        /* CONTAINER */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px;
        }

        /* TOOLBAR */
        .toolbar {
            background: var(--bg-card);
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
            border: 1px solid rgba(128, 128, 128, 0.2);
        }

        .input-group {
            flex: 1;
            min-width: 200px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(128, 128, 128, 0.3);
            border-radius: 6px;
            background: var(--bg-input);
            color: var(--text-main);
            font-size: 14px;
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: var(--brand-red);
        }

        .input-with-btn {
            display: flex;
            gap: 8px;
        }

        .input-with-btn input {
            flex: 1;
        }

        /* TABLE */
        .table-container {
            background: var(--bg-card);
            border-radius: 10px;
            border: 1px solid rgba(128, 128, 128, 0.2);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(128, 128, 128, 0.2);
            flex-wrap: wrap;
            gap: 10px;
        }

        .table-header h3 {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .table-scroll {
            overflow-x: auto;
            max-height: 55vh;
            overflow-y: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid rgba(128, 128, 128, 0.2);
            padding: 10px 12px;
            text-align: left;
        }

        .data-table th {
            background: rgba(0, 0, 0, 0.3);
            color: var(--text-muted);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .data-table tr:nth-child(even) {
            background: rgba(128, 128, 128, 0.05);
        }

        .data-table tr:hover {
            background: rgba(128, 128, 128, 0.1);
        }

        .data-table td[contenteditable="true"] {
            cursor: text;
        }

        .data-table td[contenteditable="true"]:focus {
            outline: 2px solid var(--brand-red);
            background: var(--bg-input);
        }

        /* LOGIN */
        #login-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.98);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #login-overlay.hidden {
            display: none;
        }

        .login-box {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            width: 90%;
            max-width: 360px;
            border: 1px solid rgba(128, 128, 128, 0.2);
        }

        .login-box h2 {
            margin-bottom: 8px;
        }

        .login-box p {
            color: var(--text-muted);
            margin-bottom: 16px;
            font-size: 14px;
        }

        .login-box input {
            width: 100%;
            padding: 14px;
            margin-bottom: 16px;
            font-size: 16px;
            background: var(--bg-input);
            color: var(--text-main);
            border: 1px solid rgba(128, 128, 128, 0.3);
            border-radius: 8px;
        }

        .login-box input:focus {
            outline: none;
            border-color: var(--brand-red);
        }

        .login-error {
            color: var(--error);
            margin-top: 10px;
            display: none;
            font-size: 13px;
        }

        .login-footer {
            margin-top: 24px;
        }

        .login-footer a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* ADMIN CONTENT */
        #admin-content {
            display: none;
        }

        #admin-content.visible {
            display: block;
        }

        /* LOADER */
        .loader {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* CONNECTION STATUS */
        .connection-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .connection-status.ok {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
        }

        .connection-status.error {
            background: rgba(248, 113, 113, 0.2);
            color: var(--error);
        }

        .connection-status.testing {
            background: rgba(128, 128, 128, 0.2);
            color: var(--text-muted);
        }

        /* FOOTER */
        .footer-credit {
            text-align: center;
            padding: 20px;
            font-size: 11px;
            color: var(--text-muted);
            opacity: 0.6;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
            }

            .input-group {
                min-width: 100%;
            }

            .admin-log {
                display: none;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-right {
                width: 100%;
                justify-content: flex-end;
            }
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(128, 128, 128, 0.3);
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <!-- TOAST -->
    <div class="toast" id="toast"></div>

<?php if (!$isAuth): ?>
    <!-- LOGIN OVERLAY -->
    <div id="login-overlay">
        <div class="login-box">
            <img src="img/logo_bw.png" alt="Logo" class="header-logo" style="height: 50px; margin-bottom: 16px;">
            <h2>Acceso Admin</h2>
            <p>Ingrese la clave de administrador</p>
            <input type="password" id="admin-pass" placeholder="Contrasena" autocomplete="current-password">
            <button onclick="doLogin()" class="btn btn-primary" style="width:100%" id="login-btn">Entrar</button>
            <p id="login-error" class="login-error">Clave incorrecta</p>
            <div class="login-footer">
                <a href="index.php">← Volver al Scanner</a>
                <p style="margin-top: 24px; font-size: 10px; opacity: 0.5;">Daruma Consulting SRL</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- ADMIN CONTENT -->
    <div id="admin-content" class="visible">
        <header class="app-header">
            <div class="header-left">
                <img src="img/logo_bw.png" alt="Logo" class="header-logo">
                <button class="theme-switch" onclick="toggleTheme()" title="Cambiar tema">🌓</button>
                <div class="header-info">
                    <span class="header-title">Panel Admin</span>
                    <span class="header-subtitle">Gestion de CSV y Configuracion</span>
                </div>
            </div>
            <div class="admin-log" id="admin-log">Sin eventos</div>
            <div class="header-right">
                <button onclick="doLogout()" class="btn btn-secondary btn-sm">Salir</button>
                <a href="index.php" class="btn btn-secondary btn-sm">Scanner</a>
            </div>
        </header>

        <div class="admin-container">
            <!-- CONFIGURACION -->
            <section class="toolbar">
                <div class="input-group">
                    <label>📂 Ruta PDFs (Servidor)</label>
                    <div class="input-with-btn">
                        <input type="text" id="config-pdf-path" placeholder="http://192.168.170.160/PDF-EXPGRIFERIA/">
                        <button onclick="testConnection()" class="btn btn-secondary" id="test-conn-btn">🔗 Probar</button>
                    </div>
                    <div id="connection-status" style="margin-top: 6px;"></div>
                </div>
                <div class="input-group">
                    <label>📄 Base de Datos Activa (CSV)</label>
                    <select id="config-csv-active"></select>
                </div>
                <div class="input-group">
                    <label>🔗 Sufijo Puesto 1 (Armado)</label>
                    <select id="config-sufijo-puesto-1">
                        <option value="">Ninguno (Normal)</option>
                        <option value="P1">Puesto 1 (_P1)</option>
                        <option value="P2">Puesto 2 (_P2)</option>
                        <option value="P3">Puesto 3 (_P3)</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>🔗 Sufijo Puesto 2 (Control)</label>
                    <select id="config-sufijo-puesto-2">
                        <option value="">Ninguno (Normal)</option>
                        <option value="P1">Puesto 1 (_P1)</option>
                        <option value="P2">Puesto 2 (_P2)</option>
                        <option value="P3">Puesto 3 (_P3)</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>🔗 Sufijo Puesto 3 (Empaque)</label>
                    <select id="config-sufijo-puesto-3">
                        <option value="">Ninguno (Normal)</option>
                        <option value="P1">Puesto 1 (_P1)</option>
                        <option value="P2">Puesto 2 (_P2)</option>
                        <option value="P3">Puesto 3 (_P3)</option>
                    </select>
                </div>
                <div class="input-group" style="flex: 0 0 auto; display: flex; align-items: center; gap: 8px; min-width: 220px; height: 38px; margin-top: 24px; border: none;">
                    <input type="checkbox" id="config-usar-sufijos-puesto" style="width: 18px !important; height: 18px !important; margin: 0 !important; padding: 0 !important; cursor: pointer;">
                    <label for="config-usar-sufijos-puesto" style="margin: 0; cursor: pointer; font-weight: 600; font-size: 12px; color: var(--text-muted);">Habilitar sufijos por puesto</label>
                </div>
                <div style="flex: 0 0 auto;">
                    <button onclick="saveConfiguration()" class="btn btn-primary" id="save-config-btn">
                        💾 Guardar Config
                    </button>
                </div>
            </section>

            <!-- SUBIR CSV -->
            <section class="toolbar">
                <div class="input-group">
                    <label>📤 Subir Nuevo CSV</label>
                    <input type="file" id="csv-upload" accept=".csv">
                </div>
                <div style="flex: 0 0 auto;">
                    <button onclick="uploadCSV()" class="btn btn-secondary" id="upload-btn">Subir</button>
                </div>
            </section>

            <!-- EDITOR CSV -->
            <section class="toolbar">
                <div class="input-group">
                    <label>✏️ Editar Archivo CSV</label>
                    <select id="editor-csv-select" onchange="loadData()"></select>
                </div>
                <div style="flex: 0 0 auto;">
                    <button onclick="loadData()" class="btn btn-secondary" id="reload-btn">🔄 Recargar</button>
                </div>
            </section>

            <!-- EDITOR MANUAL -->
            <section class="toolbar" style="align-items: flex-start;">
                <div style="flex: 1; min-width: 300px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <h3 style="margin:0; color: var(--text-muted); font-size:14px;">📘 Editor de Manual</h3>
                        <small style="color: var(--text-muted);">Edite y guarde para aplicar cambios</small>
                    </div>

                    <div style="display: flex; gap: 10px; height: 250px;">
                        <!-- List -->
                        <div
                            style="flex: 0 0 200px; border: 1px solid rgba(128,128,128,0.2); border-radius: 6px; overflow-y: auto; background: var(--bg-input);">
                            <div id="faq-list-admin" style="display: flex; flex-direction: column;">
                                <!-- Items will be here -->
                            </div>
                        </div>
                        <!-- Form -->
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                            <input type="hidden" id="faq-id">
                            <input type="text" id="faq-title" placeholder="Título"
                                style="width: 100%; padding: 8px; background: var(--bg-input); color: var(--text-main); border: 1px solid rgba(128,128,128,0.3); border-radius: 4px;">
                            <textarea id="faq-content" placeholder="Contenido (acepta saltos de línea)"
                                style="flex: 1; resize: none; padding: 8px; background: var(--bg-input); color: var(--text-main); border: 1px solid rgba(128,128,128,0.3); border-radius: 4px; font-family: inherit;"></textarea>
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <button onclick="newFaq()" class="btn btn-secondary btn-sm">Limpiar / Nuevo</button>
                                <button onclick="deleteFaq()" class="btn btn-warning btn-sm">Eliminar</button>
                                <button onclick="saveFaqLocal()" class="btn btn-primary btn-sm">Aplicar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="flex: 0 0 auto; display: flex; flex-direction: column; gap: 10px; margin-left: 10px;">
                    <button onclick="commitFaq()" class="btn btn-success" id="save-faq-server-btn"
                        style="height: 100%;">
                        💾 Guardar<br>Todo
                    </button>
                </div>
            </section>

            <!-- TABLA -->
            <div class="table-container">
                <div class="table-header">
                    <h3 id="table-title">Datos del archivo</h3>
                    <button onclick="addRow()" class="btn btn-success btn-sm">➕ Agregar Fila</button>
                </div>
                <div class="table-scroll">
                    <table class="data-table" id="csv-table">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="footer-credit">Daruma Consulting SRL</div>
        </div>
    </div>
<?php endif; ?>

    <script>
        var API = 'api/admin.php';
        var currentEditingFile = '';
        var faqData = [];

        // =====================================================================
        // TEMA
        // =====================================================================
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
        }

        function toggleTheme() {
            document.body.classList.toggle('light-mode');
            localStorage.setItem('theme', document.body.classList.contains('light-mode') ? 'light' : 'dark');
        }

        // =====================================================================
        // TOAST NOTIFICATIONS
        // =====================================================================
        function showToast(message, type) {
            type = type || 'success';
            var toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' visible';

            setTimeout(function () {
                toast.classList.remove('visible');
            }, 3000);
        }

        // =====================================================================
        // INICIALIZACION
        // =====================================================================
        document.addEventListener('DOMContentLoaded', function () {
            var passInput = document.getElementById('admin-pass');
            if (passInput) {
                passInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') doLogin();
                });
            }
            <?php if ($isAuth): ?>
            loadConfig();
            loadFaqFromServer();
            logEvent('Sesión de administración activa');
            <?php endif; ?>
        });

        // =====================================================================
        // AUTENTICACION
        // =====================================================================
        function doLogin() {
            var btn = document.getElementById('login-btn');
            var pass = document.getElementById('admin-pass').value;

            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Verificando...';

            var fd = new FormData();
            fd.append('action', 'login');
            fd.append('password', pass);

            fetch(API, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status === 'ok') {
                        location.reload();
                    } else {
                        document.getElementById('login-error').style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = 'Entrar';
                    }
                })
                .catch(function () {
                    showToast('Error de conexion', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Entrar';
                });
        }

        function doLogout() {
            var fd = new FormData();
            fd.append('action', 'logout');
            fetch(API, { method: 'POST', body: fd })
                .then(function () { location.reload(); });
        }

        function showAdmin() {
            document.getElementById('login-overlay').classList.add('hidden');
            document.getElementById('admin-content').classList.add('visible');
            loadConfig();
            loadFaqFromServer();
            logEvent('Usuario autenticado');
        }

        function logEvent(msg) {
            var el = document.getElementById('admin-log');
            if (el) {
                var now = new Date().toLocaleTimeString();
                el.textContent = now + ' - ' + msg;
            }
        }

        // =====================================================================
        // CONFIGURACION
        // =====================================================================
        function loadConfig() {
            var fd = new FormData();
            fd.append('action', 'get_config');
            fetch(API, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.getElementById('config-pdf-path').value = data.config.pdf_path || '';
                    
                    var suf1 = data.config.sufijo_puesto_1 !== undefined ? data.config.sufijo_puesto_1 : 'P1';
                    var suf2 = data.config.sufijo_puesto_2 !== undefined ? data.config.sufijo_puesto_2 : 'P2';
                    var suf3 = data.config.sufijo_puesto_3 !== undefined ? data.config.sufijo_puesto_3 : 'P3';

                    // Si hay una config global forzada en la config vieja
                    var forzarGlobal = data.config.forzar_sufijo || '';
                    if (forzarGlobal === '' && data.config.forzar_p1) {
                        forzarGlobal = 'P1';
                    }
                    if (forzarGlobal !== '') {
                        suf1 = forzarGlobal;
                        suf2 = forzarGlobal;
                        suf3 = forzarGlobal;
                    }

                    document.getElementById('config-sufijo-puesto-1').value = suf1;
                    document.getElementById('config-sufijo-puesto-2').value = suf2;
                    document.getElementById('config-sufijo-puesto-3').value = suf3;

                    var usarSufijos = data.config.usar_sufijos_puesto !== undefined 
                        ? !!data.config.usar_sufijos_puesto 
                        : (data.config.forzar_p1 || (data.config.forzar_sufijo && data.config.forzar_sufijo !== ''));
                    document.getElementById('config-usar-sufijos-puesto').checked = usarSufijos;

                    var selActive = document.getElementById('config-csv-active');
                    var selEditor = document.getElementById('editor-csv-select');

                    selActive.innerHTML = '';
                    selEditor.innerHTML = '';

                    data.csv_files.forEach(function (f) {
                        var opt1 = document.createElement('option');
                        opt1.value = f;
                        opt1.textContent = f;
                        if (f === data.config.active_csv) opt1.selected = true;
                        selActive.appendChild(opt1);

                        var opt2 = document.createElement('option');
                        opt2.value = f;
                        opt2.textContent = f;
                        if (f === data.config.active_csv && currentEditingFile === '') opt2.selected = true;
                        selEditor.appendChild(opt2);
                    });

                    if (currentEditingFile === '') {
                        currentEditingFile = data.config.active_csv || data.csv_files[0];
                    }
                    loadData();
                });
        }

        function testConnection() {
            var path = document.getElementById('config-pdf-path').value;
            var btn = document.getElementById('test-conn-btn');
            var status = document.getElementById('connection-status');

            if (!path) {
                showToast('Ingrese una ruta primero', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span>';
            status.innerHTML = '<span class="connection-status testing">Probando conexion...</span>';

            var fd = new FormData();
            fd.append('action', 'test_path');
            fd.append('path', path);
            fd.append('type', 'dir');

            fetch(API, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        status.innerHTML = '<span class="connection-status ok">✓ ' + data.msg + '</span>';
                        showToast('Conexion exitosa', 'success');
                    } else {
                        status.innerHTML = '<span class="connection-status error">✗ ' + data.msg + '</span>';
                        showToast('No se pudo conectar', 'error');
                    }
                })
                .catch(function () {
                    status.innerHTML = '<span class="connection-status error">✗ Error de red</span>';
                    showToast('Error de conexion', 'error');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '🔗 Probar';
                });
        }

        function saveConfiguration() {
            var btn = document.getElementById('save-config-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Guardando...';

            var fd = new FormData();
            fd.append('action', 'save_config');
            fd.append('pdf_path', document.getElementById('config-pdf-path').value);
            fd.append('active_csv', document.getElementById('config-csv-active').value);
            
            var suf1 = document.getElementById('config-sufijo-puesto-1').value;
            var suf2 = document.getElementById('config-sufijo-puesto-2').value;
            var suf3 = document.getElementById('config-sufijo-puesto-3').value;

            fd.append('sufijo_puesto_1', suf1);
            fd.append('sufijo_puesto_2', suf2);
            fd.append('sufijo_puesto_3', suf3);

            var usarSufijos = document.getElementById('config-usar-sufijos-puesto').checked;
            fd.append('usar_sufijos_puesto', usarSufijos ? 'true' : 'false');

            // Mapear compatibilidad si todos tienen el mismo sufijo (siempre y cuando usarSufijos este habilitado)
            var forzarGlobal = (usarSufijos && suf1 === suf2 && suf2 === suf3) ? suf1 : '';
            fd.append('forzar_sufijo', forzarGlobal);
            fd.append('forzar_p1', forzarGlobal === 'P1' ? 'true' : 'false');

            fetch(API, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status === 'ok') {
                        showToast('Configuracion guardada', 'success');
                        logEvent('Config guardada');
                    } else {
                        showToast('Error al guardar', 'error');
                    }
                })
                .catch(function () {
                    showToast('Error de conexion', 'error');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '💾 Guardar Config';
                });
        }

        // =====================================================================
        // FAQ MANAGER
        // =====================================================================
        function loadFaqFromServer() {
            fetch(API + '?action=get_faq')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    faqData = data || [];
                    renderFaqAdmin();
                });
        }

        function renderFaqAdmin() {
            var container = document.getElementById('faq-list-admin');
            container.innerHTML = '';
            faqData.forEach(function (item, idx) {
                var div = document.createElement('div');
                div.style.padding = '8px';
                div.style.cursor = 'pointer';
                div.style.borderBottom = '1px solid rgba(128,128,128,0.1)';
                div.textContent = item.title;
                div.onclick = function () { editFaq(idx); };
                container.appendChild(div);
            });
        }

        function editFaq(idx) {
            var item = faqData[idx];
            document.getElementById('faq-id').value = item.id;
            document.getElementById('faq-title').value = item.title;
            document.getElementById('faq-content').value = item.content;
            document.getElementById('faq-id').dataset.idx = idx;
        }

        function newFaq() {
            document.getElementById('faq-id').value = '';
            document.getElementById('faq-id').dataset.idx = '';
            document.getElementById('faq-title').value = '';
            document.getElementById('faq-content').value = '';
        }

        function saveFaqLocal() {
            var title = document.getElementById('faq-title').value;
            var content = document.getElementById('faq-content').value;
            var idx = document.getElementById('faq-id').dataset.idx;

            if (!title) {
                showToast('El título es requerido', 'warning');
                return;
            }

            if (idx !== '' && idx !== undefined && idx !== null) {
                // Update
                faqData[idx].title = title;
                faqData[idx].content = content;
            } else {
                // Create
                var newId = 1;
                if (faqData.length > 0) {
                    // Find max id
                    faqData.forEach(function (i) { if (i.id >= newId) newId = i.id + 1; });
                }
                faqData.push({ id: newId, title: title, content: content });
            }
            renderFaqAdmin();
            newFaq();
            showToast('Item actualizado (recuerde Guardar Todo)', 'success');
        }

        function deleteFaq() {
            var idx = document.getElementById('faq-id').dataset.idx;
            if (idx !== '' && idx !== undefined) {
                if (confirm('Eliminar este item?')) {
                    faqData.splice(idx, 1);
                    renderFaqAdmin();
                    newFaq();
                }
            }
        }

        function commitFaq() {
            var btn = document.getElementById('save-faq-server-btn');
            btn.disabled = true;
            btn.innerHTML = '...';

            var fd = new FormData();
            fd.append('action', 'save_faq');
            fd.append('content', JSON.stringify(faqData));

            fetch(API, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status === 'ok') {
                        showToast('Manual guardado correctamente', 'success');
                    } else {
                        showToast('Error al guardar', 'error');
                    }
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '💾 Guardar<br>Todo';
                });
        }


        // =====================================================================
        // DATOS CSV
        // =====================================================================
        function loadData() {
            var btn = document.getElementById('reload-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span>';

            currentEditingFile = document.getElementById('editor-csv-select').value;
            document.getElementById('table-title').textContent = 'Editando: ' + currentEditingFile;

            var fd = new FormData();
            fd.append('action', 'get_data');
            fd.append('target_csv', currentEditingFile);

            fetch(API, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    renderTable(res.data);
                    logEvent('Datos cargados');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '🔄 Recargar';
                });
        }

        function renderTable(rows) {
            var table = document.getElementById('csv-table');
            table.innerHTML = '';

            if (!rows || rows.length === 0) {
                table.innerHTML = '<tbody><tr><td style="padding:20px;text-align:center;color:var(--text-muted)">Archivo vacio o no encontrado</td></tr></tbody>';
                return;
            }

            var headerRow = rows[0];
            var bodyRows = rows.slice(1);

            var thead = document.createElement('thead');
            var trHead = document.createElement('tr');

            headerRow.forEach(function (cell, i) {
                var th = document.createElement('th');
                th.textContent = cell || 'Col ' + i;
                trHead.appendChild(th);
            });

            var thAct = document.createElement('th');
            thAct.textContent = 'Acciones';
            thAct.style.width = '80px';
            trHead.appendChild(thAct);
            thead.appendChild(trHead);
            table.appendChild(thead);

            var tbody = document.createElement('tbody');
            bodyRows.forEach(function (row, rowIndex) {
                var realIndex = rowIndex + 1;
                var tr = document.createElement('tr');

                row.forEach(function (cell, cellIndex) {
                    var td = document.createElement('td');
                    td.textContent = cell;
                    td.contentEditable = true;
                    td.onblur = function () { saveCell(realIndex, cellIndex, td.textContent, row); };
                    tr.appendChild(td);
                });

                var tdAct = document.createElement('td');
                tdAct.style.textAlign = 'center';
                var btnDel = document.createElement('button');
                btnDel.innerHTML = '🗑️';
                btnDel.className = 'btn btn-sm';
                btnDel.style.background = 'var(--error)';
                btnDel.style.padding = '6px 10px';
                btnDel.onclick = function () { deleteRow(realIndex); };
                tdAct.appendChild(btnDel);
                tr.appendChild(tdAct);

                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
        }

        function saveCell(rowIndex, colIndex, value, originalRow) {
            originalRow[colIndex] = value;
            var fd = new FormData();
            fd.append('action', 'update_row');
            fd.append('target_csv', currentEditingFile);
            fd.append('index', rowIndex);
            originalRow.forEach(function (val, i) { fd.append('row_data[' + i + ']', val); });
            fetch(API, { method: 'POST', body: fd }).then(function () {
                showToast('Celda guardada', 'success');
                logEvent('Celda guardada');
            });
        }

        function addRow() {
            var table = document.getElementById('csv-table');
            var headerCells = table.querySelector('thead tr');
            if (!headerCells) {
                showToast('Primero cargue un archivo CSV', 'warning');
                return;
            }
            var cols = headerCells.children.length - 1;
            var emptyRow = [];
            for (var i = 0; i < cols; i++) emptyRow.push('...');

            var fd = new FormData();
            fd.append('action', 'add_row_top');
            fd.append('target_csv', currentEditingFile);
            emptyRow.forEach(function (val, i) { fd.append('row_data[' + i + ']', val); });

            fetch(API, { method: 'POST', body: fd }).then(function () {
                showToast('Fila agregada', 'success');
                logEvent('Fila agregada');
                loadData();
                var tableScroll = document.querySelector('.table-scroll');
                if (tableScroll) tableScroll.scrollTop = 0;
            });
        }

        function deleteRow(index) {
            if (!confirm('Eliminar esta fila?')) return;
            var fd = new FormData();
            fd.append('action', 'delete_row');
            fd.append('target_csv', currentEditingFile);
            fd.append('index', index);
            fetch(API, { method: 'POST', body: fd }).then(function () {
                showToast('Fila eliminada', 'success');
                logEvent('Fila eliminada');
                loadData();
            });
        }

        function uploadCSV() {
            var fileInput = document.getElementById('csv-upload');
            var btn = document.getElementById('upload-btn');

            if (fileInput.files.length === 0) {
                showToast('Seleccione un archivo CSV', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Subiendo...';

            var fd = new FormData();
            fd.append('action', 'upload_csv');
            fd.append('file', fileInput.files[0]);

            fetch(API, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status === 'ok') {
                        showToast('Archivo subido: ' + d.filename, 'success');
                        logEvent('CSV subido: ' + d.filename);
                        loadConfig();
                        fileInput.value = '';
                    } else {
                        showToast('Error: ' + (d.message || d.msg), 'error');
                    }
                })
                .catch(function () {
                    showToast('Error de conexion', 'error');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = 'Subir';
                });
        }
    </script>
</body>

</html>