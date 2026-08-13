const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Registration can fail on insecure origins; online use is unaffected.
        });
    });
};

const showOfflineWriteError = () => {
    const heading = document
        .querySelector('meta[name="pwa-offline-write-heading"]')
        ?.getAttribute('content');
    const text = document
        .querySelector('meta[name="pwa-offline-write-text"]')
        ?.getAttribute('content');

    if (typeof window.Flux?.toast === 'function') {
        window.Flux.toast({
            heading: heading ?? 'Could not save',
            text: text ?? 'You are offline. Changes were not saved.',
            variant: 'danger',
            duration: 8000,
        });
    }
};

const interceptLivewireOfflineWrites = () => {
    document.addEventListener('livewire:init', () => {
        Livewire.interceptRequest(({ onFailure }) => {
            onFailure(() => {
                showOfflineWriteError();
            });
        });
    });
};

registerServiceWorker();
interceptLivewireOfflineWrites();
