// Register manual service worker for development / manual control
if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/sw.js').then(reg => {
			// registration successful
		}).catch(() => {});
	});
}

// PWA install prompt handling (only show toast if present on page)
let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
    const toast = document.getElementById('pwa-toast');
    if (toast) {
        toast.style.display = 'block';
        const installBtn = document.getElementById('pwa-toast-install');
        const closeBtn = document.getElementById('pwa-toast-close');
        const hide = () => { toast.style.display = 'none'; };
        if (installBtn) installBtn.addEventListener('click', async () => {
            hide();
            try {
                deferredInstallPrompt.prompt();
                await deferredInstallPrompt.userChoice;
            } catch (err) { }
            deferredInstallPrompt = null;
        });
        if (closeBtn) closeBtn.addEventListener('click', () => { hide(); });
    }
});

window.addEventListener('appinstalled', () => {
	const toast = document.getElementById('pwa-toast');
	if (toast) toast.style.display = 'none';
});

// Place any application bootstrap code below (Livewire/Vite loaded by @vite)

// NOTE: PWA install UI & beforeinstallprompt handling removed per request.
