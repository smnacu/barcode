/**
 * PowerManager - Gestion de energia y Wake Lock
 * Mantiene la pantalla encendida mientras se usa la camara.
 */
var PowerManager = {
    wakeLock: null,
    isSupported: ('wakeLock' in navigator),

    init: function() {
        if (this.isSupported) {
            console.log('[POWER] Wake Lock API soportada');
            // Re-solicitar el bloqueo si la pestaña vuelve a ser visible
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && !this.wakeLock) {
                    // Solo si deberiamos estar activos? 
                    // Dejaremos que el Scanner lo invoque cuando arranque.
                }
            });
        } else {
            console.warn('[POWER] Wake Lock API no soportada en este navegador');
        }
    },

    requestWakeLock: async function() {
        if (!this.isSupported) return;
        
        try {
            this.wakeLock = await navigator.wakeLock.request('screen');
            console.log('[POWER] Pantalla mantenida ENCENDIDA (Wake Lock active)');
            
            this.wakeLock.addEventListener('release', () => {
                console.log('[POWER] Wake Lock liberado');
                this.wakeLock = null;
            });
        } catch (err) {
            console.error('[POWER] Fallo al solicitar Wake Lock:', err);
        }
    },

    releaseWakeLock: function() {
        if (this.wakeLock) {
            this.wakeLock.release()
                .then(() => {
                    this.wakeLock = null;
                })
                .catch((err) => console.error('[POWER] Error al liberar:', err));
        }
    }
};
