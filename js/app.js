// ============================================================================
// VARIABLES GLOBALES Y CONSTANTES
// ============================================================================

let html5QrcodeScanner = null;
let isProcessing = false;
let currentFacingMode = "user"; // Arranca con la frontal (Selfie)

// Control de deduplicación de escaneos
let lastScannedEAN = null;
let lastScanTime = 0;
const SCAN_COOLDOWN_MS = 3000; // 3 segundos entre escaneos iguales

// Persistencia en Storage
const STORAGE_KEY = 'barcodeC_history';
const MAX_HISTORY_ITEMS = 30;

// Sonido "Beep" corto y profesional
const beep = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU');

// ============================================================================
// UTILIDADES DE STORAGE
// ============================================================================

function loadHistoryFromStorage() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        console.warn('⚠️ LocalStorage no disponible:', e.message);
        return [];
    }
}

function saveHistoryToStorage(items) {
    try {
        const limited = items.slice(0, MAX_HISTORY_ITEMS);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(limited));
    } catch (e) {
        console.error('❌ Error guardando historial:', e.message);
    }
}

function clearHistory() {
    try {
        localStorage.removeItem(STORAGE_KEY);
        document.getElementById('history-list').innerHTML = '';
    } catch (e) {
        console.warn('Error limpiando storage:', e.message);
    }
}

function ensureAndroidCompatibility() {
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute('content',
            'width=device-width, initial-scale=1.0, maximum-scale=1.0, ' +
            'user-scalable=no, viewport-fit=cover'
        );
    }
    document.addEventListener('touchstart', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            e.preventDefault();
        }
    }, { passive: false });
    document.body.addEventListener('touchmove', (e) => {
        if (e.touches.length > 1) {
            e.preventDefault();
        }
    }, { passive: false });
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Iniciando aplicación...');
    ensureAndroidCompatibility();

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('./sw.js')
            .then(reg => console.log('✅ Service Worker registrado:', reg.scope))
            .catch(err => console.warn('⚠️ Error registrando SW:', err.message));
    }

    const stored = loadHistoryFromStorage();
    const historyList = document.getElementById('history-list');
    stored.forEach(item => {
        renderHistoryItem(historyList, item.ean, item.desc, item.url, item.success);
    });

    // Iniciar directamente
    requestCameraPermission();
});

async function requestCameraPermission() {
    // Simplificado: Llamar directamente a startScanner.
    // La librería maneja los permisos.
    await startScanner();
}

async function startScanner() {
    const startScreen = document.getElementById('start-screen');
    const scannerContainer = document.getElementById('scanner-container');
    const errorMsg = document.getElementById('error-msg');
    const statusBadge = document.getElementById('scan-status');

    if (errorMsg) errorMsg.classList.add('hidden');

    // Limpieza básica si ya existe
    if (html5QrcodeScanner) {
        try {
            await html5QrcodeScanner.stop();
            html5QrcodeScanner.clear();
        } catch (e) {
            console.warn('⚠️ Error deteniendo scanner anterior:', e);
        }
        html5QrcodeScanner = null;
    }

    try {
        if (startScreen) startScreen.classList.add('hidden');
        if (scannerContainer) scannerContainer.classList.remove('hidden');

        // Asegurar elemento limpio
        document.getElementById('reader').innerHTML = '';

        html5QrcodeScanner = new Html5Qrcode("reader");

        const config = {
            fps: 15,
            qrbox: { width: 250, height: 150 },
            aspectRatio: 1.0,
            disableFlip: false,
            formatsToSupport: [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8
            ]
        };

        // Intentar iniciar
        try {
            await html5QrcodeScanner.start(
                { facingMode: currentFacingMode },
                config,
                onScanSuccess,
                onScanFailure
            );
            console.log('✅ Scanner iniciado');
        } catch (err) {
            console.warn('⚠️ Falló inicio normal, probando fallback {video: true}');
            await html5QrcodeScanner.start(
                { video: true },
                config,
                onScanSuccess,
                onScanFailure
            );
        }

        if (statusBadge) {
            statusBadge.innerHTML = '<span class="w-2 h-2 bg-black rounded-full animate-pulse"></span> ACTIVO';
            statusBadge.className = "bg-green-500/90 text-black text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider shadow-lg flex items-center gap-1";
        }

    } catch (err) {
        console.error("❌ Error fatal iniciando cámara:", err);

        // CRÍTICO: NO ocultar el contenedor de video si falla, para ver si hay feed
        // scannerContainer.classList.add('hidden');
        // startScreen.classList.remove('hidden');

        let msg = `Error: ${err.message || err}`;
        if (errorMsg) {
            errorMsg.innerText = msg;
            errorMsg.classList.remove('hidden');
        }
        updateScanLog(msg);
    }
}

async function switchCamera() {
    const btnIcon = document.querySelector('button[onclick="switchCamera()"] i');
    if (btnIcon) btnIcon.classList.add('animate-spin');

    currentFacingMode = (currentFacingMode === "user") ? "environment" : "user";
    await startScanner();

    if (btnIcon) setTimeout(() => btnIcon.classList.remove('animate-spin'), 500);
}

async function onScanSuccess(decodedText, decodedResult) {
    const now = Date.now();
    if (decodedText === lastScannedEAN && (now - lastScanTime) < SCAN_COOLDOWN_MS) {
        return;
    }

    if (isProcessing) return;
    isProcessing = true;

    lastScannedEAN = decodedText;
    lastScanTime = now;

    beep.play().catch(e => { });

    const statusBadge = document.getElementById('scan-status');
    if (statusBadge) {
        statusBadge.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> PROCESANDO';
        statusBadge.className = "bg-blue-500 text-white text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider shadow-lg flex items-center gap-1";
    }

    try {
        const response = await fetch(`api/buscar.php?ean=${encodeURIComponent(decodedText)}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();

        if (data.found) {
            handleFound(data);
        } else {
            handleNotFound(decodedText);
        }
    } catch (error) {
        console.error('❌ Error búsqueda:', error);
        addToHistory(decodedText, `Error: ${error.message}`, null, false);
    } finally {
        setTimeout(() => {
            isProcessing = false;
            if (statusBadge) {
                statusBadge.innerHTML = '<span class="w-2 h-2 bg-black rounded-full animate-pulse"></span> ACTIVO';
                statusBadge.className = "bg-green-500/90 text-black text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider shadow-lg flex items-center gap-1";
            }
        }, SCAN_COOLDOWN_MS);
    }
}

function onScanFailure(error) {
    // Ignorar errores de frame
}

function handleFound(data) {
    const fullUrl = data.pdf_base_url + data.pdf_name + '.pdf';
    addToHistory(data.ean, data.descripcion, fullUrl, true);
    window.open(fullUrl, '_blank');
    updateScanLog(`OK · ${data.ean} · ${data.descripcion}`);
}

function handleNotFound(ean) {
    addToHistory(ean, '❌ No encontrado', null, false);
    updateScanLog(`NO · ${ean} · No encontrado`);
}

function updateScanLog(message) {
    const el = document.getElementById('scan-log');
    if (el) {
        const ts = new Date().toLocaleTimeString();
        el.innerText = `${ts} — ${message}`;
    }
}

function renderHistoryItem(list, ean, desc, url, success) {
    const item = document.createElement('div');
    item.className = "history-item bg-gray-800 rounded-lg p-3 flex justify-between items-center border border-gray-700 shadow-sm relative overflow-hidden group";
    const colorClass = success ? "bg-green-500" : "bg-red-500";

    let actionButton = success && url ?
        `<a href="${url}" target="_blank" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-md font-bold text-xs transition-colors shadow-lg z-10"><span>ABRIR</span><i class="ph-bold ph-arrow-square-out text-lg"></i></a>` :
        `<span class="text-gray-600 text-xs font-mono px-2">---</span>`;

    item.innerHTML = `
        <div class="absolute left-0 top-0 bottom-0 w-1 ${colorClass}"></div>
        <div class="flex flex-col overflow-hidden mr-3 pl-2">
            <span class="text-[10px] text-gray-400 font-mono tracking-wider uppercase mb-0.5">EAN: ${ean}</span>
            <span class="text-sm font-medium text-gray-100 truncate leading-tight" title="${desc}">${desc}</span>
        </div>
        <div class="shrink-0">${actionButton}</div>
    `;
    list.insertBefore(item, list.firstChild);
}

function addToHistory(ean, desc, url, success) {
    const list = document.getElementById('history-list');
    renderHistoryItem(list, ean, desc, url, success);

    const newItem = { ean, desc, url, success, timestamp: new Date().toISOString() };
    const existing = loadHistoryFromStorage();
    saveHistoryToStorage([newItem, ...existing]);

    while (list.children.length > MAX_HISTORY_ITEMS) {
        list.removeChild(list.lastChild);
    }
}