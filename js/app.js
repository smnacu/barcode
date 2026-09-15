var UI = {
    elements: {},
    init: function () {
        this.elements = {
            statusBar: document.getElementById('status-bar'),
            statusText: document.getElementById('status-text'),
            logContent: document.getElementById('log-content'),
            historyList: document.getElementById('history-list'),
            manualInput: document.getElementById('manual-search'),
            searchResults: document.getElementById('search-results'),
            resultsList: document.getElementById('results-list')
        };
    },
    setStatus: function (message, type) {
        type = type || 'scanning';
        if (this.elements.statusBar && this.elements.statusText) {
            this.elements.statusBar.className = 'status-message ' + type;
            this.elements.statusText.textContent = message;
        }
        this.addLog(message, type);
    },
    addLog: function (message, type) {
        type = type || 'info';
        if (!this.elements.logContent) return;
        var time = new Date().toLocaleTimeString();
        var entry = document.createElement('div');
        entry.className = 'log-entry ' + type;
        entry.textContent = '[' + time + '] ' + message;
        this.elements.logContent.insertBefore(entry, this.elements.logContent.firstChild);
        if (this.elements.logContent.children.length > 50) {
            this.elements.logContent.removeChild(this.elements.logContent.lastChild);
        }
    },
    addHistoryItem: function (item, prepend) {
        if (!this.elements.historyList) return;
        var empty = this.elements.historyList.querySelector('.empty-history');
        if (empty) empty.remove();
        var el = document.createElement('div');
        el.className = 'history-item ' + (item.success ? 'success' : 'error');
        var actionBtn = (item.success && item.url)
            ? '<a href="' + item.url + '" target="_blank" class="open-pdf-btn">ABRIR</a>'
            : '';
        el.innerHTML =
            '<div class="history-item-info">' +
            '<div class="history-item-code">' + this.escapeHtml(item.ean) + '</div>' +
            '<div class="history-item-desc">' + this.escapeHtml(item.desc) + '</div>' +
            '</div>' + actionBtn;
        if (prepend) {
            this.elements.historyList.insertBefore(el, this.elements.historyList.firstChild);
        } else {
            this.elements.historyList.appendChild(el);
        }
    },
    clearHistoryUI: function () {
        if (this.elements.historyList) {
            this.elements.historyList.innerHTML = '<div class="empty-history">Sin escaneos</div>';
        }
    },
    flashEffect: function (color) {
        color = color || '#22c55e';
        var container = document.querySelector('.scanner-container');
        if (container) {
            container.style.borderColor = color;
            container.style.boxShadow = '0 0 20px ' + color;
            setTimeout(function () {
                container.style.borderColor = '#333';
                container.style.boxShadow = '';
            }, 500);
        }
    },
    toggleSearch: function (show) {
        if (this.elements.searchResults) {
            if (show) this.elements.searchResults.classList.add('visible');
            else this.elements.searchResults.classList.remove('visible');
        }
    },
    escapeHtml: function (text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};
var AudioHandler = {
    context: null,
    init: function () {
        try {
            this.context = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) { }
    },
    resume: function () {
        if (this.context && this.context.state === 'suspended') {
            this.context.resume();
        }
    },
    beep: function (type) {
        if (!this.context) return;
        type = type || 'success';
        try {
            var osc = this.context.createOscillator();
            var gain = this.context.createGain();
            osc.connect(gain);
            gain.connect(this.context.destination);
            if (type === 'success') {
                osc.frequency.value = 1800;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.3, this.context.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.context.currentTime + 0.15);
                osc.start(this.context.currentTime);
                osc.stop(this.context.currentTime + 0.15);
            } else {
                osc.frequency.value = 200;
                osc.type = 'sawtooth';
                gain.gain.setValueAtTime(0.4, this.context.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.context.currentTime + 0.25);
                osc.start(this.context.currentTime);
                osc.stop(this.context.currentTime + 0.25);
            }
        } catch (e) { }
    },
    vibrate: function (pattern) {
        if ('vibrate' in navigator) {
            try { navigator.vibrate(pattern); } catch (e) { }
        }
    }
};

var DataManager = {
    STORAGE_KEY: 'barcodeC_history',
    MAX_ITEMS: 30,
    loadHistory: function () {
        try {
            var stored = localStorage.getItem(this.STORAGE_KEY);
            var items = stored ? JSON.parse(stored) : [];
            if (items.length > 0 && items[0].timestamp) {
                var lastDate = new Date(items[0].timestamp).toDateString();
                var today = new Date().toDateString();
                if (lastDate !== today) {
                    this.clearHistory();
                    items = [];
                }
            }
            if (items.length === 0) {
                UI.clearHistoryUI();
            } else {
                for (var i = 0; i < items.length; i++) {
                    UI.addHistoryItem(items[i], false);
                }
            }
        } catch (e) { }
    },
    saveItem: function (ean, desc, url, success) {
        var newItem = {
            ean: ean,
            desc: desc,
            url: url,
            success: success,
            timestamp: new Date().toISOString()
        };
        try {
            var stored = localStorage.getItem(this.STORAGE_KEY);
            var existing = stored ? JSON.parse(stored) : [];
            existing = existing.filter(function (item) {
                return item.ean !== ean;
            });
            var updated = [newItem].concat(existing).slice(0, this.MAX_ITEMS);
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(updated));
            if (UI.elements.historyList) {
                UI.elements.historyList.innerHTML = '';
                for (var i = 0; i < updated.length; i++) {
                    UI.addHistoryItem(updated[i], false);
                }
            }
        } catch (e) {
            UI.addHistoryItem(newItem, true);
        }
    },
    clearHistory: function () {
        localStorage.removeItem(this.STORAGE_KEY);
        UI.clearHistoryUI();
    },
    searchCode: function (code) {
        var puesto = (typeof AppConfig !== 'undefined') ? AppConfig.puesto : 1;
        return fetch('api/buscar.php?codigo=' + encodeURIComponent(code) + '&puesto=' + puesto)
            .then(function (res) { return res.json(); });
    },
    searchList: function (query) {
        var fd = new FormData();
        fd.append('codigo', query);
        fd.append('modo', 'lista');
        return fetch('api/buscar.php', { method: 'POST', body: fd })
            .then(function (res) { return res.json(); });
    }
};
var Scanner = {
    instance: null,
    isBusy: false,
    isProcessing: false,
    facingMode: "user",
    lastScan: { code: null, time: 0 },
    lastPdfCode: null,
    lastPdfTime: 0,
    COOLDOWN: 1500,
    SAFETY_TIMEOUT: 10000,
    safetyTimer: null,
    resetFlags: function () {
        this.isProcessing = false;
        this.isBusy = false;
        if (this.safetyTimer) {
            clearTimeout(this.safetyTimer);
            this.safetyTimer = null;
        }
    },
    startSafetyTimer: function () {
        var self = this;
        if (this.safetyTimer) clearTimeout(this.safetyTimer);
        this.safetyTimer = setTimeout(function () {
            self.resetFlags();
            UI.setStatus('Listo - Apunta el codigo', 'success');
        }, this.SAFETY_TIMEOUT);
    },
    start: function () {
        var self = this;
        if (AppConfig.puesto > 1) {
            return;
        }
        if (this.isBusy) {
            UI.setStatus('Camara ocupada, reintentando...', 'scanning');
            setTimeout(function () {
                if (self.isBusy) {
                    self.isBusy = false;
                    self.start();
                }
            }, 2000);
            return;
        }
        this.isBusy = true;
        UI.setStatus('Abriendo camara...', 'scanning');
        this.stop().then(function () {
            return new Promise(function (resolve) { setTimeout(resolve, 300); });
        }).then(function () {
            var reader = document.getElementById('reader');
            if (!reader) {
                UI.setStatus('ERROR: No se encontro el visor', 'error');
                self.isBusy = false;
                return Promise.reject('No reader');
            }
            reader.innerHTML = '';
            self.instance = new Html5Qrcode("reader");
            var fpsConfig = (AppConfig.lowPerf) ? 10 : 15;
            var config = {
                fps: fpsConfig,
                disableFlip: true,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.QR_CODE
                ]
            };
            return self.instance.start(
                { facingMode: self.facingMode },
                config,
                function (text, res) { self.onScan(text, res); },
                function () { }
            );
        }).then(function () {
            UI.setStatus('Listo - Apunta el codigo', 'success');
            self.isBusy = false;
            setTimeout(function () {
                try {
                    var reader = document.getElementById('reader');
                    if (!reader) return;
                    var video = reader.querySelector('video');
                    if (video) {
                        video.style.cssText = 'width: 100% !important; height: 100% !important; object-fit: cover !important; z-index: 10 !important; position: absolute !important; top: 0; left: 0;';
                    }
                    var allElements = reader.getElementsByTagName('*');
                    for (var i = 0; i < allElements.length; i++) {
                        var el = allElements[i];
                        if (el.tagName === 'VIDEO') continue;
                        if (el.contains(video)) continue;
                        var style = window.getComputedStyle(el);
                        if (el.tagName === 'CANVAS' ||
                            style.position === 'absolute' ||
                            style.boxShadow !== 'none' ||
                            el.id.indexOf('scan_region') !== -1) {
                            el.style.display = 'none';
                            el.style.opacity = '0';
                        }
                    }
                } catch (e) {
                }
            }, 500);
        }).catch(function (err) {
            if (self.instance) {
                self.instance.start("environment", { fps: 10 },
                    function (t, r) { self.onScan(t, r); },
                    function () { }
                ).then(function () {
                    UI.setStatus('Modo compatibilidad', 'warning');
                    self.isBusy = false;
                }).catch(function (e) {
                    UI.setStatus('ERROR CAMARA', 'error');
                    self.isBusy = false;
                });
            } else {
                self.isBusy = false;
            }
        });
    },
    stop: function () {
        var self = this;
        return new Promise(function (resolve) {
            if (!self.instance) return resolve();
            try {
                var state = self.instance.getState();
                if (state === Html5QrcodeScannerState.SCANNING || state === Html5QrcodeScannerState.PAUSED) {
                    self.instance.stop().then(function () {
                        try { self.instance.clear(); } catch (e) { }
                        self.instance = null;
                        resolve();
                    }).catch(function () {
                        self.instance = null;
                        resolve();
                    });
                } else {
                    try { self.instance.clear(); } catch (e) { }
                    self.instance = null;
                    resolve();
                }
            } catch (e) {
                self.instance = null;
                resolve();
            }
        });
    },
    switchCamera: function () {
        if (this.isBusy) return;
        this.facingMode = (this.facingMode === "user") ? "environment" : "user";
        UI.setStatus('Cambiando camara...', 'scanning');
        this.start();
    },
    handleVisibilityChange: function () {
        var self = this;
        if (document.visibilityState === 'visible') {
            self.isProcessing = false;
            if (typeof SyncManager !== 'undefined' && SyncManager.active) {
                SyncManager.poll();
            }
            setTimeout(function () {
                if (!self.instance || !self.isBusy) {
                    self.start();
                } else {
                    UI.setStatus('Listo - Apunta el codigo', 'success');
                }
            }, 500);
        }
    },
    onScan: function (decodedText, decodedResult) {
        var self = this;
        var now = Date.now();
        var isSameCode = (decodedText === this.lastScan.code);
        if (isSameCode && (now - this.lastScan.time) < 20000) {
            return;
        }
        if (this.isProcessing) {
            return;
        }
        this.isProcessing = true;
        this.lastScan = { code: decodedText, time: now };
        this.startSafetyTimer();
        AudioHandler.vibrate(200);
        AudioHandler.beep('success');
        UI.flashEffect('#22c55e');
        UI.setStatus('Escaneando...', 'scanning');
        DataManager.searchCode(decodedText)
            .then(function (data) {
                if (data.encontrado) {
                    var desc = data.producto ? data.producto.descripcion : 'Encontrado';
                    var code = data.producto ? (data.producto.ean || data.producto.codigo) : decodedText;
                    var pdfUrl = data.pdf_url;
                    var imgUrl = data.img_url;
                    if (!pdfUrl && data.pdf && !imgUrl) {
                        pdfUrl = data.pdf.indexOf('http') === 0 ? data.pdf : 'api/ver_pdf.php?file=' + encodeURIComponent(data.pdf);
                    }
                    UI.setStatus('Escaneo exitoso', 'success');
                    var navProd = document.getElementById('nav-scanned-product');
                    if (navProd) navProd.innerText = 'NUEVA ORDEN: ' + desc;
                    AudioHandler.vibrate([100, 50, 100]);
                    DataManager.saveItem(code, desc, pdfUrl || imgUrl, true);
                    if (typeof SyncManager !== 'undefined') {
                        SyncManager.notify(code, desc, pdfUrl || imgUrl);
                    }
                    var isRepeated = (self.lastPdfCode === decodedText);
                    if ((now - self.lastPdfTime) > 10000) isRepeated = false;
                    
                    var isPuesto1 = (typeof AppConfig === 'undefined' || AppConfig.puesto == 1);
                    var btnScan = document.getElementById('btn-scan-again');
                    if (btnScan && isPuesto1) btnScan.style.display = 'flex';

                    if (imgUrl) {
                        var existingImg = document.getElementById('product-image');
                        if (existingImg) existingImg.remove();
                        var img = new Image();
                        img.id = 'product-image';
                        img.className = 'scanned-image-result';
                        img.onload = function() {
                            var container = document.querySelector('.scanner-container');
                            var reader = document.getElementById('reader');
                            var scanRegion = document.querySelector('.scan-region');
                            var statusBar = document.querySelector('.status-bar');
                            var btnScan = document.getElementById('btn-scan-again');
                            if (reader) reader.style.display = 'none';
                            if (scanRegion) scanRegion.style.display = 'none';
                            if (statusBar) statusBar.classList.add('hidden');
                            if (btnScan && isPuesto1) btnScan.style.display = 'flex';
                            if (container) {
                                container.classList.add('has-image');
                                container.appendChild(img);
                            }
                            document.body.classList.add('hide-navs');
                            self.lastPdfCode = decodedText;
                            self.lastPdfTime = now;
                            // Detener la camara por completo para liberar CPU y memoria WebRTC
                            self.stop();
                        };
                        img.onerror = function() {
                            console.warn('Imagen no disponible para:', decodedText);
                            UI.setStatus('Imagen no disponible', 'warning');
                        };
                        img.src = imgUrl;
                    } else {
                        UI.setStatus('Escaneo registrado (Sin imagen de plano)', 'warning');
                        self.lastPdfCode = decodedText;
                        self.lastPdfTime = now;
                    }
                } else if (data.error || data.offline) {
                    UI.setStatus('SIN CONEXIÓN', 'warning');
                    UI.flashEffect('#f59e0b');
                    AudioHandler.vibrate([200]);
                    AudioHandler.beep('error');
                    DataManager.saveItem(decodedText, 'Sin conexion con servidor', null, false);
                } else {
                    UI.setStatus('NO EN CSV: ' + decodedText, 'error');
                    UI.flashEffect('#ef4444');
                    AudioHandler.vibrate([50, 100, 50, 100]);
                    AudioHandler.beep('error');
                    DataManager.saveItem(decodedText, 'No encontrado en CSV', null, false);
                }
            })
            .catch(function (err) {
                UI.setStatus('ERROR RED', 'error');
                UI.flashEffect('#ef4444');
                AudioHandler.vibrate([300]);
                AudioHandler.beep('error');
                DataManager.saveItem(decodedText, 'Error de conexion', null, false);
            })
            .finally(function () {
                if (self.safetyTimer) {
                    clearTimeout(self.safetyTimer);
                    self.safetyTimer = null;
                }
                setTimeout(function () {
                    self.isProcessing = false;
                    var statusBar = document.querySelector('.status-bar');
                    if (statusBar && !statusBar.classList.contains('hidden')) {
                        UI.setStatus('Listo - Apunta el codigo', 'success');
                    }
                }, self.COOLDOWN);
            });
    }
};
var ManualSearch = {
    timeout: null,
    init: function () {
        var self = this;
        if (!UI.elements.manualInput) return;
        UI.elements.manualInput.addEventListener('input', function (e) {
            var query = e.target.value.trim();
            clearTimeout(self.timeout);
            if (query.length < 2) {
                UI.toggleSearch(false);
                return;
            }
            self.timeout = setTimeout(function () {
                self.search(query);
            }, 400);
        });
    },
    search: function (query) {
        DataManager.searchList(query)
            .then(function (data) {
                if (!UI.elements.resultsList) return;
                UI.elements.resultsList.innerHTML = '';
                if (!data.resultados || data.resultados.length === 0) {
                    UI.elements.resultsList.innerHTML = '<div class="result-item">Sin resultados</div>';
                    UI.toggleSearch(true);
                    return;
                }
                for (var i = 0; i < data.resultados.length; i++) {
                    (function (item) {
                        var el = document.createElement('div');
                        el.className = 'result-item';
                        el.innerHTML =
                            '<div class="result-item-title">' + UI.escapeHtml(item.descripcion) + '</div>' +
                            '<div class="result-item-code">EAN: ' + UI.escapeHtml(item.ean) + '</div>';
                        el.onclick = function () {
                            UI.toggleSearch(false);
                            UI.elements.manualInput.value = '';
                            Scanner.onScan(item.codigo, { result: { format: { formatName: 'MANUAL' } } });
                        };
                        UI.elements.resultsList.appendChild(el);
                    })(data.resultados[i]);
                }
                UI.toggleSearch(true);
            })
            .catch(function (err) { });
    }
};

var AppConfig = {
    line: 1,
    puesto: 1,
    lowPerf: false,
    init: function () {
        var urlParams = new URLSearchParams(window.location.search);
        var pLine = urlParams.get('line');
        var pPuesto = urlParams.get('puesto');
        if (pLine && pPuesto) {
            this.line = parseInt(pLine);
            this.puesto = parseInt(pPuesto);
            localStorage.setItem('barcode_app_config', JSON.stringify({
                line: this.line,
                puesto: this.puesto,
                lowPerf: this.lowPerf
            }));
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        } else {
            var saved = localStorage.getItem('barcode_app_config');
            if (saved) {
                try {
                    var c = JSON.parse(saved);
                    this.line = c.line || 1;
                    this.puesto = c.puesto || 1;
                    this.lowPerf = c.lowPerf || false;
                } catch (e) { }
            }
        }
        if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) {
            this.lowPerf = true;
        }
        if (navigator.deviceMemory && navigator.deviceMemory < 4) {
            this.lowPerf = true;
        }
        this.updateUI();
    },
    save: function (l, p, lp) {
        this.line = l;
        this.puesto = p;
        if (lp !== undefined) this.lowPerf = lp;
        localStorage.setItem('barcode_app_config', JSON.stringify({
            line: this.line,
            puesto: this.puesto,
            lowPerf: this.lowPerf
        }));
        this.updateUI();
        setTimeout(function () {
            window.location.reload();
        }, 500);
    },
    updateUI: function () {
        var el = document.getElementById('header-badge');
        if (el) el.textContent = 'L' + this.line + '-P' + this.puesto + (this.lowPerf ? ' (LP)' : '');
        if (this.lowPerf) {
            document.body.classList.add('low-perf');
        } else {
            document.body.classList.remove('low-perf');
        }
        this.applyPuestoMode();
    },
    applyPuestoMode: function () {
        var isScanner = (this.puesto == 1);
        var scannerContainer = document.querySelector('.scanner-container');
        var manualSearch = document.querySelector('.search-container');
        var rescanBtn = document.getElementById('btn-force-rescan');
        var floatingScanBtn = document.getElementById('btn-scan-again');
        if (!isScanner) {
            if (scannerContainer) {
                scannerContainer.innerHTML = '<div class="viewer-mode-screen">' +
                    '<div class="viewer-icon">📡</div>' +
                    '<div class="viewer-text">ESPERANDO ORDEN</div>' +
                    '<div class="viewer-sub">Línea ' + this.line + ' - Puesto ' + this.puesto + '</div>' +
                    '<div class="viewer-help">' +
                    '<p>La pantalla se abrirá automáticamente.</p>' +
                    '<p>Si se traba, presiona el botón:</p>' +
                    '<button class="viewer-retry-btn" onclick="window.location.reload()">REINICIAR PAGINA</button>' +
                    '</div>' +
                    '<div id="viewer-current-job"></div>' +
                    '</div>';
                scannerContainer.style.background = '';
                scannerContainer.style.display = 'flex';
                scannerContainer.style.flexDirection = 'column';
                scannerContainer.style.justifyContent = 'center';
            }
            if (manualSearch) manualSearch.style.display = 'none';
            if (rescanBtn) rescanBtn.style.display = 'none';
            if (floatingScanBtn) floatingScanBtn.style.display = 'none';
            if (typeof SyncManager !== 'undefined') {
                SyncManager.active = true;
                setTimeout(function () { SyncManager.start(); }, 1000);
            }
        } else {
            if (manualSearch) manualSearch.style.display = 'block';
            if (rescanBtn) rescanBtn.style.display = 'inline-flex';
        }
    }
};
var SyncManager = {
    active: false,
    timer: null,
    lastTime: 0,
    lastProcessedEAN: null,
    lastProcessedTimestamp: null,
    lastNotifiedEAN: null,
    lastNotifiedTime: 0,
    errors: 0,
    MAX_ERRORS: 5,
    interval: 3000,
    baseInterval: 3000,
    updateIntervalForPerf: function () {
        if (typeof AppConfig !== 'undefined' && AppConfig.lowPerf) {
            this.baseInterval = 6000;
        } else {
            this.baseInterval = 2500;
        }
        this.interval = this.baseInterval;
    },
    init: function () {
        var self = this;
        this.updateIntervalForPerf();
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                self.stop();
            } else {
                if (self.active) {
                    self.start();
                }
            }
        });
        window.addEventListener('online', function () {
            UI.setStatus('Conexión restablecida', 'success');
            self.errors = 0;
            self.updateIntervalForPerf();
            var btn = document.getElementById('sync-btn');
            if (btn && self.active) {
                btn.style.borderColor = 'var(--success)';
            }
            if (self.active) {
                self.start();
            }
        });
    },
    isCircuitBroken: function () {
        return this.errors >= this.MAX_ERRORS;
    },
    toggle: function () {
        this.active = !this.active;
        var btn = document.getElementById('sync-btn');
        if (this.active) {
            this.errors = 0;
            this.updateIntervalForPerf();
            if (btn) {
                btn.classList.add('sync-active');
                btn.style.borderColor = 'var(--success)';
                btn.title = "Sincronizando Línea " + AppConfig.line;
            }
            this.start();
        } else {
            this.stop();
            if (btn) {
                btn.classList.remove('sync-active');
                btn.style.borderColor = '';
                btn.style.animation = 'none';
                btn.title = "Sincronizar (OFF)";
            }
        }
    },
    start: function () {
        this.stop();
        if (document.hidden) return;
        this.poll();
    },
    stop: function () {
        if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
        }
    },
    adjustInterval: function (success) {
        if (success) {
            if (this.interval > this.baseInterval) {
                this.interval = this.baseInterval;
            }
            this.errors = 0;
            var btn = document.getElementById('sync-btn');
            if (btn) btn.style.borderColor = 'var(--success)';
        } else {
            this.errors++;
            if (this.errors > 2 && this.interval < 10000) {
                this.interval = 10000;
            }
            if (this.isCircuitBroken()) {
                this.interval = 15000;
                UI.setStatus('Reconectando red...', 'warning');
                var btn = document.getElementById('sync-btn');
                if (btn) {
                    btn.style.borderColor = 'var(--warning)';
                }
            }
        }
    },
    poll: function () {
        if (!this.active) return;
        var self = this;
        var controller = new AbortController();
        var id = setTimeout(function () { controller.abort(); }, 5000);
        fetch('api/sync_line.php?line=' + AppConfig.line, { signal: controller.signal })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                clearTimeout(id);
                self.adjustInterval(true);
                if (data.success && data.data) {
                    var isViewer = AppConfig.puesto > 1;
                    if (data.data.timestamp > self.lastTime || (isViewer && self.lastTime === 0)) {
                        var isRecent = (Date.now() / 1000) - data.data.timestamp < 60;
                        if (data.data.puesto_origen == AppConfig.puesto && !isViewer) {
                            self.lastTime = data.data.timestamp;
                            return;
                        }
                        if (self.lastTime === data.data.timestamp && !isViewer) return;
                        self.lastTime = data.data.timestamp;
                        if (!isRecent && !isViewer) return;
                        
                        // Si el puesto visor ya está mostrando esta misma orden, no reiniciar ni recrear la UI
                        if (isViewer && self.lastProcessedEAN === data.data.ean) {
                            return;
                        }
                        self.lastProcessedEAN = data.data.ean;
                        self.lastProcessedTimestamp = data.data.timestamp;

                        var container = document.querySelector('.scanner-container');
                        if (container) container.classList.remove('has-image');
                        var viewerScreen = document.querySelector('.viewer-mode-screen');
                        if (viewerScreen) viewerScreen.style.display = 'flex';
                        var statusBar = document.querySelector('.status-bar');
                        if (statusBar) statusBar.classList.remove('hidden');
                        var existingImg = document.getElementById('product-image');
                        if (existingImg) existingImg.remove();
                        
                        if (isViewer) {
                            UI.setStatus('Plano sincronizado', 'success');
                            var navProdViewer = document.getElementById('nav-scanned-product');
                            if (navProdViewer) navProdViewer.innerText = 'NUEVA ORDEN: ' + data.data.desc;
                            AudioHandler.vibrate([100, 100, 100]);
                            AudioHandler.beep('success');

                            DataManager.searchCode(data.data.ean)
                                .then(function (localData) {
                                    if (localData.encontrado && (localData.pdf_url || localData.img_url)) {
                                        var newPdf = localData.pdf_url;
                                        var newImg = localData.img_url;
                                        if (newImg) {
                                            var img = new Image();
                                            img.id = 'product-image';
                                            img.className = 'scanned-image-result';
                                            img.onload = function() {
                                                if (viewerScreen) viewerScreen.style.display = 'none';
                                                if (statusBar) statusBar.classList.add('hidden');
                                                if (container) {
                                                    container.classList.add('has-image');
                                                    container.appendChild(img);
                                                }
                                                document.body.classList.add('hide-navs');
                                            };
                                            img.onerror = function() {
                                                console.warn("No se pudo cargar la imagen para visor:", data.data.ean);
                                                var jobDisplay = document.getElementById('viewer-current-job');
                                                if (jobDisplay) {
                                                    jobDisplay.innerHTML = '<div class="job-status-msg" style="color: var(--warning); margin-top:20px;">⚠️ IMAGEN NO DISPONIBLE</div>';
                                                }
                                            };
                                            img.src = newImg;
                                        } else if (newPdf) {
                                            var jobDisplay = document.getElementById('viewer-current-job');
                                            if (jobDisplay) {
                                                jobDisplay.innerHTML =
                                                    '<div class="job-status-msg" style="color: var(--warning); margin-top:20px;">Plano disponible en PDF:</div>' +
                                                    '<a href="' + newPdf + '" target="_blank" class="manual-open-btn">ABRIR PDF</a>';
                                            }
                                        }
                                    } else {
                                        var jobDisplay = document.getElementById('viewer-current-job');
                                        if (jobDisplay) {
                                            jobDisplay.innerHTML = '<div class="job-status-msg" style="color: var(--error); margin-top:20px;">❌ PLANO NO DISPONIBLE</div>';
                                        }
                                    }
                                });
                        } else {
                            UI.setStatus('Recibido de P' + data.data.puesto_origen + ': ' + data.data.desc, 'success');
                            AudioHandler.vibrate([50]);
                            DataManager.saveItem(data.data.ean, data.data.desc + ' (P' + data.data.puesto_origen + ')', data.data.pdf, true);
                        }
                    }
                }
            })
            .catch(function (e) {
                clearTimeout(id);
                self.adjustInterval(false);
            })
            .finally(function () {
                if (self.active) {
                    self.timer = setTimeout(self.poll.bind(self), self.interval);
                }
            });
    },
    notify: function (ean, desc, pdf) {
        if (!ean) return;
        var now = Date.now();
        if (this.lastNotifiedEAN === ean && (now - this.lastNotifiedTime) < 30000) {
            return;
        }
        this.lastNotifiedEAN = ean;
        this.lastNotifiedTime = now;
        var fd = new FormData();
        fd.append('action', 'update');
        fd.append('line', AppConfig.line);
        fd.append('puesto', AppConfig.puesto);
        fd.append('ean', ean);
        fd.append('desc', desc);
        fd.append('pdf', pdf || '');
        fetch('api/sync_line.php', { method: 'POST', body: fd }).catch(function () { });
    }
};
document.addEventListener('DOMContentLoaded', function () {
    UI.init();
    AppConfig.init();
    UI.setStatus('Inicializando...', 'scanning');
    AudioHandler.init();
    DataManager.loadHistory();
    ManualSearch.init();
    SyncManager.init();
    if (typeof Html5Qrcode === 'undefined') {
        UI.setStatus('ERROR: Libreria no cargada', 'error');
        return;
    }
    
    // Toggle HUD/fullscreen on container click
    var container = document.querySelector('.scanner-container');
    if (container) {
        container.addEventListener('click', function (e) {
            // Prevent toggling when clicking on interactive elements
            if (e.target.tagName === 'BUTTON' || e.target.closest('button') ||
                e.target.tagName === 'A' || e.target.closest('a') ||
                e.target.tagName === 'INPUT' || e.target.closest('input') ||
                e.target.tagName === 'SELECT' || e.target.closest('select')) {
                return;
            }
            var isHidden = document.body.classList.contains('hide-navs');
            setHUDVisibility(isHidden); // Toggle HUD and Fullscreen
        });
    }

    document.body.addEventListener('touchstart', function () { AudioHandler.resume(); }, { once: true });
    document.body.addEventListener('click', function () { AudioHandler.resume(); }, { once: true });
    document.addEventListener('visibilitychange', function () {
        Scanner.handleVisibilityChange();
    });
    setInterval(function () {
        if (document.visibilityState === 'visible' && !Scanner.isProcessing) {
            var statusEl = document.getElementById('status-text');
            if (statusEl && statusEl.textContent.indexOf('ERROR') !== -1) {
                Scanner.resetFlags();
                Scanner.start();
            }
        }
    }, 30000);
    setTimeout(function () { Scanner.start(); }, 500);
});

function switchCamera() { Scanner.switchCamera(); }
function clearHistory() { DataManager.clearHistory(); }
function closeSearch() { UI.toggleSearch(false); }

function forceRescan() {
    if (typeof Scanner !== 'undefined') {
        Scanner.lastScan = { code: null, time: 0 };
        Scanner.lastPdfCode = null;
        Scanner.lastPdfTime = 0;
        Scanner.isProcessing = false;
        Scanner.isBusy = false;
        if (Scanner.safetyTimer) {
            clearTimeout(Scanner.safetyTimer);
            Scanner.safetyTimer = null;
        }
    }
    if (typeof SyncManager !== 'undefined') {
        SyncManager.lastNotifiedEAN = null;
        SyncManager.lastNotifiedTime = 0;
    }

    var reader = document.getElementById('reader');
    var scanRegion = document.querySelector('.scan-region');
    var img = document.getElementById('product-image');
    var btnScan = document.getElementById('btn-scan-again');
    var navProd = document.getElementById('nav-scanned-product');
    var container = document.querySelector('.scanner-container');
    var statusBar = document.querySelector('.status-bar');

    setHUDVisibility(true);

    if (reader) reader.style.display = 'block';
    if (scanRegion) scanRegion.style.display = 'block';
    if (img) img.remove();
    if (btnScan) btnScan.style.display = 'none';
    if (navProd) navProd.innerText = '';
    if (container) container.classList.remove('has-image');
    if (statusBar) statusBar.classList.remove('hidden');

    AudioHandler.beep('success');
    UI.flashEffect('#3b82f6');
    document.body.classList.remove('show-sidebar');

    if (typeof Scanner !== 'undefined') {
        Scanner.start();
    }
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

function resetScannerUI() {
    forceRescan();
}

function toggleFullScreen() {
    try {
        var docEl = document.documentElement;
        var isFullscreen = document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement;
        
        if (!isFullscreen) {
            if (docEl.requestFullscreen) {
                docEl.requestFullscreen();
            } else if (docEl.webkitRequestFullscreen) {
                docEl.webkitRequestFullscreen();
            } else if (docEl.mozRequestFullScreen) {
                docEl.mozRequestFullScreen();
            } else if (docEl.msRequestFullscreen) {
                docEl.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    } catch (e) {
        console.warn("Fullscreen API not supported or blocked:", e);
    }
}

function setHUDVisibility(visible) {
    try {
        var docEl = document.documentElement;
        var isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
        
        if (visible) {
            document.body.classList.remove('hide-navs');
            if (isFullscreen) {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        } else {
            document.body.classList.add('hide-navs');
            if (!isFullscreen) {
                if (docEl.requestFullscreen) {
                    docEl.requestFullscreen();
                } else if (docEl.webkitRequestFullscreen) {
                    docEl.webkitRequestFullscreen();
                } else if (docEl.mozRequestFullScreen) {
                    docEl.mozRequestFullScreen();
                } else if (docEl.msRequestFullscreen) {
                    docEl.msRequestFullscreen();
                }
            }
        }
    } catch (e) {
        console.warn("HUD visibility toggle error:", e);
    }
}