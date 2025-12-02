/**
 * Lógica Core - Scanner, Buscador Manual e Historial
 * Daruma Consulting SRL
 * Refactorizado para usar Html5Qrcode (Core) y permitir cambio de cámara
 */

const CONFIG = {
    fps: 10,
    qrbox: 250,
    aspectRatio: 1.0,
    historyLimit: 5
};

let html5QrCode = null;
let isScanning = false;
let currentFacingMode = "user"; // "user" (frontal) o "environment" (trasera)

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar lector
    html5QrCode = new Html5Qrcode("reader");

    // Iniciar scanner con cámara frontal por defecto
    startScanner();

    setupEvents();
    renderHistory();
});

function startScanner() {
    if (isScanning) return;

    const config = {
        fps: CONFIG.fps,
        qrbox: { width: 250, height: 250 },
        aspectRatio: CONFIG.aspectRatio
    };

    // Si es móvil, usamos facingMode. Si es desktop, el browser elige la default.
    const cameraConfig = { facingMode: currentFacingMode };

    html5QrCode.start(
        cameraConfig,
        config,
        onScanSuccess,
        (err) => { /* ignorar errores de frame vacío */ }
    ).then(() => {
        isScanning = true;
        updateStatus("Cámara activa (" + (currentFacingMode === 'user' ? 'Frontal' : 'Trasera') + ")", "success");
    }).catch(err => {
        console.error("Error al iniciar cámara", err);
        updateStatus("Error de cámara: " + err, "error");
    });
}

function stopScanner() {
    if (!isScanning || !html5QrCode) return Promise.resolve();

    return html5QrCode.stop().then(() => {
        isScanning = false;
        console.log("Scanner detenido.");
    }).catch(err => {
        console.error("Fallo al detener", err);
    });
}

function toggleCamera() {
    stopScanner().then(() => {
        currentFacingMode = currentFacingMode === "user" ? "environment" : "user";
        startScanner();
    });
}

// --- CORE SEARCH LOGIC ---

function performSearch(query) {
    if (!query || query.trim().length < 2) {
        updateStatus("Ingrese al menos 2 caracteres", "error");
        return;
    }

    // Detener scanner para liberar recursos
    stopScanner();

    updateStatus("Buscando...", "warning");

    fetch(`api/scan.php?code=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                showResult(data);
                addToHistory(data);
            } else {
                updateStatus("No encontrado: " + query, "error");
                // Si no encuentra, reiniciamos scanner automáticamente? 
                // Mejor dejar que el usuario decida o reiniciar tras unos segundos.
                // Por ahora, reiniciamos manual con "Nueva Búsqueda" o botón reset.
            }
        })
        .catch(err => {
            console.error(err);
            updateStatus("Error de conexión", "error");
        });
}

function onScanSuccess(decodedText, decodedResult) {
    if (!isScanning) return;
    performSearch(decodedText);
}

// --- UI HANDLING ---

function showResult(data) {
    const resultDiv = document.getElementById('result-panel');
    const scannerDiv = document.getElementById('scanner-container');
    const searchDiv = document.querySelector('.search-bar-wrapper');

    // Ocultar búsqueda y scanner
    scannerDiv.classList.add('hidden');
    if (searchDiv) searchDiv.parentElement.classList.add('hidden'); // Ocultar wrapper
    resultDiv.classList.remove('hidden');

    // Render Datos
    const infoContainer = document.getElementById('info-content');
    const mainTitle = data.data[1] ? data.data[1] : data.data[0];
    const subTitle = data.data[0];

    let html = `
        <div class="result-header">
            <h2 class="result-title">${mainTitle}</h2>
            <div class="result-meta">REF: ${subTitle}</div>
        </div>
        <ul class="data-list">
    `;

    data.data.forEach((val, index) => {
        if (index > 1 && val.trim() !== "") {
            html += `<li><strong>Col ${index}:</strong> ${val}</li>`;
        }
    });
    html += `</ul>`;
    infoContainer.innerHTML = html;

    // Botón PDF
    const btnPdf = document.getElementById('btn-open-pdf');
    const pdfContainer = document.getElementById('pdf-container');

    if (data.pdf_available) {
        btnPdf.style.display = 'inline-flex';
        btnPdf.onclick = () => openPdfViewer(data.pdf_url);
    } else {
        btnPdf.style.display = 'none';
        pdfContainer.innerHTML = '<p class="text-muted"><i class="fa fa-exclamation-triangle"></i> Sin PDF asociado</p>';
    }

    updateStatus("Datos cargados", "success");
}

function resetApp() {
    closePdfViewer();
    document.getElementById('result-panel').classList.add('hidden');

    // Mostrar scanner y search
    document.getElementById('scanner-container').classList.remove('hidden');
    const searchDiv = document.querySelector('.search-bar-wrapper');
    if (searchDiv) searchDiv.parentElement.classList.remove('hidden');

    document.getElementById('manual-search-input').value = '';

    // Reiniciar cámara
    startScanner();
}

// --- PDF VIEWER ---

function openPdfViewer(url) {
    const viewer = document.getElementById('pdf-modal');
    const iframe = document.getElementById('pdf-iframe');
    iframe.src = `${url}?t=${new Date().getTime()}`;
    viewer.classList.remove('hidden');
}

function closePdfViewer() {
    const viewer = document.getElementById('pdf-modal');
    const iframe = document.getElementById('pdf-iframe');
    iframe.src = "about:blank";
    viewer.classList.add('hidden');
}

// --- HISTORIAL (LOCAL STORAGE) ---

function addToHistory(data) {
    let history = JSON.parse(localStorage.getItem('scan_history') || '[]');

    const item = {
        code: data.code,
        title: data.data[1] || data.data[0],
        pdf_url: data.pdf_url,
        timestamp: new Date().getTime()
    };

    history = history.filter(h => h.code !== item.code);
    history.unshift(item);
    if (history.length > CONFIG.historyLimit) history.pop();

    localStorage.setItem('scan_history', JSON.stringify(history));
    renderHistory();
}

function renderHistory() {
    const container = document.getElementById('history-list');
    const history = JSON.parse(localStorage.getItem('scan_history') || '[]');

    if (history.length === 0) {
        container.innerHTML = '<p class="text-muted text-center" style="padding:10px;">Sin historial reciente.</p>';
        return;
    }

    container.innerHTML = '';
    history.forEach(h => {
        const div = document.createElement('div');
        div.className = 'history-item';

        div.onclick = () => {
            if (h.pdf_url) openPdfViewer(h.pdf_url);
            else alert('Este item no tiene PDF');
        };

        div.innerHTML = `
            <div class="h-info">
                <span class="h-title">${h.title}</span>
                <span class="h-code">${h.code}</span>
            </div>
            <div class="h-icon" style="color: ${h.pdf_url ? 'var(--brand-red)' : '#ccc'}">
                <i class="fa ${h.pdf_url ? 'fa-file-pdf' : 'fa-ban'}"></i>
            </div>
        `;
        container.appendChild(div);
    });
}

function clearHistory() {
    if (confirm('¿Borrar historial?')) {
        localStorage.removeItem('scan_history');
        renderHistory();
    }
}

// --- UTILS & EVENTS ---

function updateStatus(msg, type) {
    const el = document.getElementById('status-bar');
    if (el) {
        el.innerText = msg;
        // Reset classes
        el.className = 'status-info';
        if (type === 'success') el.style.color = 'var(--success)';
        if (type === 'error') el.style.color = 'var(--error)';
        if (type === 'warning') el.style.color = 'var(--warning)';
    }
}

function setupEvents() {
    document.getElementById('btn-reset').addEventListener('click', resetApp);
    document.getElementById('btn-close-pdf').addEventListener('click', closePdfViewer);

    // Switch Cam
    const btnSwitch = document.getElementById('btn-switch-cam');
    if (btnSwitch) {
        btnSwitch.addEventListener('click', toggleCamera);
    }

    // Buscador Manual
    const searchBtn = document.getElementById('btn-manual-search');
    const searchInput = document.getElementById('manual-search-input');

    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', () => {
            performSearch(searchInput.value);
        });

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch(searchInput.value);
        });
    }
}
