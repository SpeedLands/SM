// Register manual service worker for development / manual control
if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/sw.js').then(reg => {
			console.log('ServiceWorker registration successful with scope: ', reg.scope);
		}).catch(err => console.warn('ServiceWorker registration failed:', err));
	});
}

// Place any application bootstrap code below (Livewire/Vite loaded by @vite)

// PWA install prompt handling
let deferredInstallPrompt = null;
const pwaButtonId = 'pwa-install-btn';

window.addEventListener('beforeinstallprompt', (e) => {
	// Prevent automatic prompt
	e.preventDefault();
	deferredInstallPrompt = e;
	const btn = document.getElementById(pwaButtonId);
	if (btn) {
		btn.style.display = 'inline-block';
		btn.addEventListener('click', async () => {
			btn.style.display = 'none';
			deferredInstallPrompt.prompt();
			const choice = await deferredInstallPrompt.userChoice;
			if (choice.outcome === 'accepted') {
				console.log('PWA: User accepted the install prompt');
			} else {
				console.log('PWA: User dismissed the install prompt');
			}
			deferredInstallPrompt = null;
		});
	}
	// Show toast if available
	const toast = document.getElementById('pwa-toast');
	const toastInstall = document.getElementById('pwa-toast-install');
	const toastClose = document.getElementById('pwa-toast-close');
	if (toast) {
		toast.style.display = 'block';
		// auto-hide after 8s
		const hideTimeout = setTimeout(() => { toast.style.display = 'none'; }, 8000);
		if (toastInstall) {
			toastInstall.addEventListener('click', async () => {
				toast.style.display = 'none';
				clearTimeout(hideTimeout);
				deferredInstallPrompt.prompt();
				const choice = await deferredInstallPrompt.userChoice;
				deferredInstallPrompt = null;
			});
		}
		if (toastClose) {
			toastClose.addEventListener('click', () => { toast.style.display = 'none'; clearTimeout(hideTimeout); });
		}
	}
});

window.addEventListener('appinstalled', (evt) => {
	console.log('PWA was installed.', evt);
	const btn = document.getElementById(pwaButtonId);
	if (btn) btn.style.display = 'none';
});

function isIos() {
	return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

function isInStandaloneMode() {
	return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
}

function showInstallToast() {
	const toast = document.getElementById('pwa-toast');
	const btn = document.getElementById(pwaButtonId);
	if (toast) toast.style.display = 'block';
	if (btn) btn.style.display = 'inline-block';

	const toastInstall = document.getElementById('pwa-toast-install');
	const toastClose = document.getElementById('pwa-toast-close');
	const hideTimeout = setTimeout(() => { if (toast) toast.style.display = 'none'; }, 10000);

	if (toastInstall) {
		toastInstall.addEventListener('click', async () => {
			if (deferredInstallPrompt) {
				deferredInstallPrompt.prompt();
				const choice = await deferredInstallPrompt.userChoice;
				deferredInstallPrompt = null;
			} else if (isIos()) {
				alert('Para instalar en iOS: pulsa el botón Compartir y luego "Añadir a pantalla de inicio".');
			} else {
				alert('Para instalar la aplicación, usa la opción "Instalar" desde el navegador (menú).');
			}
			if (toast) toast.style.display = 'none';
			if (btn) btn.style.display = 'none';
			clearTimeout(hideTimeout);
		});
	}
	if (toastClose) {
		toastClose.addEventListener('click', () => { if (toast) toast.style.display = 'none'; if (btn) btn.style.display = 'none'; clearTimeout(hideTimeout); });
	}
}

// Proactively show install toast when criteria met and not already installed
window.addEventListener('load', () => {
	const hasManifest = !!document.querySelector('link[rel="manifest"]');
	const canUseSW = 'serviceWorker' in navigator;
	const secureContext = location.protocol === 'https:' || location.hostname === 'localhost';

	if (!isInStandaloneMode() && hasManifest && secureContext && (canUseSW || isIos())) {
		// If beforeinstallprompt already fired, handler will show toast; otherwise show proactively
		if (deferredInstallPrompt) {
			// will be handled in beforeinstallprompt
		} else {
			// show proactive toast with instructions; clicking will either prompt (if available) or show instructions
			showInstallToast();
		}
	}
});
