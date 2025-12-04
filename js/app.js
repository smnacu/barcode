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
            // Permitir foco en inputs
        } else {
            // e.preventDefault(); // Comentado para no bloquear scroll
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
        navigator.serviceWorker.register('./js/sw.js')
            .then(reg => console.log('✅ Service Worker registrado:', reg.scope))
            .catch(err => console.warn('⚠️ Error registrando SW:', err.message));
    }

    const stored = loadHistoryFromStorage();
    const historyList = document.getElementById('history-list');
    if (historyList) {
        stored.forEach(item => {
            renderHistoryItem(historyList, item.ean, item.desc, item.url, item.success);
        });
    }

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
        const reader = document.getElementById('reader');
        if (reader) reader.innerHTML = '';

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
        // Fix: Usar 'codigo' como parámetro unificado para el backend
        const response = await fetch(`api/buscar.php?codigo=${encodeURIComponent(decodedText)}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();

        if (data.encontrado) {
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
    // La URL ya viene construida desde el backend (api/buscar.php)
    // Si es intranet, vendrá como http://192.168.../CODIGO.pdf
    let fullUrl = data.pdf_url;

    // Fallback por si acaso el backend no mandó pdf_url (aunque debería)
    if (!fullUrl && data.pdf) {
        if (data.pdf.startsWith('http')) {
            fullUrl = data.pdf;
        } else {
            fullUrl = 'api/ver_pdf.php?file=' + urlencode(data.pdf);
        }
    }

    addToHistory(data.producto.ean || data.producto.codigo, data.producto.descripcion, fullUrl, true);
    if (fullUrl) {
        window.open(fullUrl, '_blank');
    }
    updateScanLog(`OK · ${data.producto.ean || data.producto.codigo} · ${data.producto.descripcion}`);
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
    if (!list) return;

    renderHistoryItem(list, ean, desc, url, success);

    const newItem = { ean, desc, url, success, timestamp: new Date().toISOString() };
    const existing = loadHistoryFromStorage();
    saveHistoryToStorage([newItem, ...existing]);

    while (list.children.length > MAX_HISTORY_ITEMS) {
        list.removeChild(list.lastChild);
    }
}

// ============================================================================
// BÚSQUEDA MANUAL
// ============================================================================

const searchInput = document.getElementById('manual-search');
const searchResults = document.getElementById('search-results');
const resultsList = document.getElementById('results-list');
let searchTimeout = null;

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            if (searchResults) searchResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            performManualSearch(query);
        }, 400); // Debounce
    });
}

function toggleSearch() {
    if (!searchResults || !searchInput) return;

    const isHidden = searchResults.classList.contains('hidden');
    if (isHidden && searchInput.value.trim().length >= 2) {
        searchResults.classList.remove('hidden');
    } else if (!isHidden) {
        searchResults.classList.add('hidden');
    }
}

function closeSearch() {
    if (searchResults) searchResults.classList.add('hidden');
}

async function performManualSearch(query) {
    try {
        const fd = new FormData();
        fd.append('codigo', query);
        fd.append('modo', 'lista'); // Nuevo modo lista

        const res = await fetch('api/buscar.php', { method: 'POST', body: fd });
        const data = await res.json();

        renderSearchResults(data);
    } catch (err) {
        console.error('Error en búsqueda manual:', err);
    }
}

function renderSearchResults(data) {
    if (!resultsList) return;
    resultsList.innerHTML = '';

    if (!data.encontrado || !data.resultados || data.resultados.length === 0) {
        resultsList.innerHTML = '<div class="text-gray-500 text-center p-4">No se encontraron resultados</div>';
        if (searchResults) searchResults.classList.remove('hidden');
        return;
    }

    data.resultados.forEach(item => {
        const el = document.createElement('div');
        el.className = 'bg-gray-800 p-3 rounded-lg border border-gray-700 hover:bg-gray-700 cursor-pointer transition-colors flex flex-col gap-1';
        el.innerHTML = `
            <div class="flex justify-between items-start">
                <span class="font-bold text-white text-sm">${item.descripcion}</span>
                <span class="text-xs bg-gray-900 text-gray-400 px-1.5 py-0.5 rounded border border-gray-600">${item.codigo}</span>
            </div>
            <div class="text-xs text-gray-400 flex gap-2">
                <span>EAN: ${item.ean}</span>
            </div>
        `;
        el.onclick = () => {
            // Simular escaneo exitoso
            onScanSuccess(item.codigo);
            closeSearch();
            if (searchInput) searchInput.value = '';
        };
        resultsList.appendChild(el);
    });

    if (searchResults) searchResults.classList.remove('hidden');
}