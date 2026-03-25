// Fetch wrapper: añade X-CSRF-Token automáticamente en todas las peticiones POST
(function () {
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
    const _orig = window.fetch.bind(window);
    window.fetch = function (url, opts) {
        opts = opts || {};
        if (opts.method && opts.method.toUpperCase() === 'POST') {
            opts.headers = opts.headers || {};
            if (opts.headers instanceof Headers) {
                opts.headers.set('X-CSRF-Token', getCsrfToken());
            } else {
                opts.headers['X-CSRF-Token'] = getCsrfToken();
            }
        }
        return _orig(url, opts);
    };
})();
