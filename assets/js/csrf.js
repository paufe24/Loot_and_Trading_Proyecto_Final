// Fetch wrapper: añade X-CSRF-Token en los POST y auto-renueva el token si caduca.
(function () {
    const _orig = window.fetch.bind(window);

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function setHeader(opts, token) {
        opts.headers = opts.headers || {};
        if (opts.headers instanceof Headers) {
            opts.headers.set('X-CSRF-Token', token);
        } else {
            opts.headers['X-CSRF-Token'] = token;
        }
    }

    // Pide un token fresco al servidor y actualiza (o crea) el <meta name="csrf-token">.
    async function refreshCsrfToken() {
        try {
            const r = await _orig('../api/csrf_token.php', { credentials: 'same-origin' });
            const j = await r.json();
            if (j && j.token) {
                let meta = document.querySelector('meta[name="csrf-token"]');
                if (!meta) {
                    meta = document.createElement('meta');
                    meta.setAttribute('name', 'csrf-token');
                    (document.head || document.documentElement).appendChild(meta);
                }
                meta.setAttribute('content', j.token);
                return j.token;
            }
        } catch (_) {}
        return getCsrfToken();
    }

    window.fetch = async function (url, opts) {
        opts = opts || {};
        const isPost = opts.method && String(opts.method).toUpperCase() === 'POST';
        if (isPost) setHeader(opts, getCsrfToken());

        let res = await _orig(url, opts);

        // Auto-recuperación: si un POST falla por token CSRF caducado, renovar y reintentar 1 vez.
        if (isPost && res.status === 403) {
            let isCsrf = false;
            try {
                const txt = await res.clone().text();
                isCsrf = txt.indexOf('Token de seguridad') !== -1;
            } catch (_) {}
            if (isCsrf) {
                const fresh = await refreshCsrfToken();
                setHeader(opts, fresh);
                res = await _orig(url, opts);
            }
        }
        return res;
    };
})();
