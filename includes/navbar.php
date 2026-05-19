<?php
$_nav_logged_in = isset($_SESSION['user_id']);
$_nav_is_admin  = !empty($_SESSION['is_admin']);
$_nav_is_envios = !empty($_SESSION['is_envios']);
require_once dirname(__DIR__) . '/includes/csrf.php';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
<style>
.nav-dock { position:fixed;top:0;left:0;width:100%;height:64px;background:#fff;padding:0 24px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:8px;z-index:1000;box-sizing:border-box; }
body.dark .nav-dock { background:#1e293b;border-color:#334155; }
.nav-dock .spacer { flex:1; }
.nav-item { display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:12px;cursor:pointer;text-decoration:none;color:#64748b;font-size:.9rem;transition:all .2s;border:none;background:none;font-family:'Outfit',sans-serif; }
.nav-item:hover { background:#f1f5f9;color:#0f172a; }
body.dark .nav-item:hover { background:#334155;color:#e2e8f0; }
.nav-item .nav-icon { width:22px;height:22px;flex-shrink:0; }
.nav-logos-center { position:absolute;left:50%;transform:translateX(-50%);display:flex;align-items:center;gap:4px; }
.nav-logo { height:40px;width:auto;object-fit:contain; }
.dark-toggle { cursor:pointer; }

/* Pill Subastas */
.nav-pill { background:#f1f5f9;border-radius:50px;padding:8px 18px;font-weight:700;font-size:.85rem;color:#0f172a; }
.nav-pill:hover { background:#3b82f6;color:#fff; }
body.dark .nav-pill { background:#334155;color:#e2e8f0; }

/* Carrito */
.nav-cart-btn { background:#f1f5f9;border-radius:50px;padding:8px 16px;font-weight:700;font-size:.85rem;color:#0f172a; }
.nav-cart-btn:hover { background:#10b981;color:#fff; }
body.dark .nav-cart-btn { background:#334155;color:#e2e8f0; }

/* Botón usuario */
.nav-user-btn { display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:50px;background:#f1f5f9;border:none;cursor:pointer;font-weight:700;font-size:.85rem;color:#0f172a;font-family:'Outfit',sans-serif;transition:all .2s; }
.nav-user-btn:hover, .nav-user-btn.active { background:#3b82f6;color:#fff; }
body.dark .nav-user-btn { background:#334155;color:#e2e8f0; }

/* Punto rojo de notificación (en user btn) */
.nav-user-wrap { position:relative; }
.nav-notif-dot { position:absolute;top:2px;right:2px;width:10px;height:10px;background:#ef4444;border-radius:50%;border:2px solid #fff;display:none;pointer-events:none;z-index:10; }
.nav-notif-dot.visible { display:block; }
body.dark .nav-notif-dot { border-color:#1e293b; }

/* Campana de notificaciones */
.nav-bell-wrap { position:relative; }
.nav-bell-btn { display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:12px;border:none;background:#f1f5f9;cursor:pointer;color:#64748b;transition:all .2s;position:relative;font-family:'Outfit',sans-serif; }
.nav-bell-btn:hover { background:#e2e8f0;color:#0f172a; }
body.dark .nav-bell-btn { background:#334155;color:#94a3b8; }
body.dark .nav-bell-btn:hover { background:#475569;color:#e2e8f0; }
.nav-bell-badge { position:absolute;top:-3px;right:-3px;min-width:16px;height:16px;background:#ef4444;border-radius:50%;border:2px solid #fff;display:none;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#fff;padding:0 2px; }
.nav-bell-badge.visible { display:flex; }
body.dark .nav-bell-badge { border-color:#1e293b; }
.nav-bell-dd { display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.14);z-index:9999;flex-direction:column;overflow:hidden; }
.nav-bell-dd.open { display:flex; }
body.dark .nav-bell-dd { background:#1e293b;border-color:#334155; }
.nav-bell-dd-header { padding:14px 16px 10px;font-weight:800;font-size:.9rem;border-bottom:1px solid #e2e8f0;color:#0f172a; }
body.dark .nav-bell-dd-header { border-color:#334155;color:#e2e8f0; }
.nav-bell-dd-list { max-height:320px;overflow-y:auto; }
.nav-bell-item { padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:.82rem; }
body.dark .nav-bell-item { border-color:#334155; }
.nav-bell-item:last-child { border-bottom:none; }
.nav-bell-item.unread { background:#fef9ec; }
body.dark .nav-bell-item.unread { background:rgba(245,158,11,.08); }
.nav-bell-item-title { font-weight:700;color:#0f172a;margin-bottom:2px; }
body.dark .nav-bell-item-title { color:#e2e8f0; }
.nav-bell-item-sub { color:#64748b;font-size:.78rem; }
.nav-bell-empty { padding:28px 16px;text-align:center;color:#94a3b8;font-size:.85rem; }

/* Dropdown */
.nav-user-wrap { position:relative; }
.nav-dd {
    display:none;
    position:absolute;
    top:calc(100% + 8px);
    right:0;
    min-width:210px;
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    box-shadow:0 16px 48px rgba(0,0,0,.14);
    padding:6px;
    z-index:9999;
    flex-direction:column;
}
.nav-dd.open { display:flex; }
body.dark .nav-dd { background:#1e293b;border-color:#334155; }
.nav-dd a {
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 14px;
    border-radius:10px;
    text-decoration:none;
    color:#0f172a;
    font-weight:600;
    font-size:.88rem;
    transition:background .15s;
    white-space:nowrap;
}
body.dark .nav-dd a { color:#e2e8f0; }
.nav-dd a:hover { background:#f1f5f9; }
body.dark .nav-dd a:hover { background:#334155; }
.nav-dd a.dd-admin { color:#d97706; }
.nav-dd a.dd-envios { color:#0891b2; }
.nav-dd a.dd-logout { color:#ef4444; }
.nav-dd a.dd-logout:hover { background:#fef2f2; }
body.dark .nav-dd a.dd-logout:hover { background:rgba(239,68,68,.1); }
.nav-dd hr { border:none;border-top:1px solid #e2e8f0;margin:4px 0; }
body.dark .nav-dd hr { border-color:#334155; }

/* ── Language selector ── */
.nav-lang-wrap { position:relative; }
.nav-lang-btn { display:inline-flex;align-items:center;gap:4px;cursor:pointer; }
.nav-lang-dd { display:none;position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%);background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 12px 32px rgba(0,0,0,.12);overflow:hidden;z-index:1100;min-width:140px; }
.nav-lang-dd.open { display:block; }
body.dark .nav-lang-dd { background:#1e293b;border-color:#334155; }
.nav-lang-option { display:flex;align-items:center;gap:10px;width:100%;padding:10px 16px;border:none;background:none;font-family:'Outfit',sans-serif;font-size:.88rem;font-weight:600;cursor:pointer;color:#334155;transition:background .15s; }
.nav-lang-option:hover { background:#f1f5f9; }
body.dark .nav-lang-option { color:#e2e8f0; }
body.dark .nav-lang-option:hover { background:#334155; }
.lang-flag { font-size:1.1rem; }
</style>

<nav class="nav-dock">
    <a href="index.php" class="nav-item" title="Inicio">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
    </a>

    <div class="nav-lang-wrap" id="nav-lang-wrap">
        <button class="nav-item nav-lang-btn" id="nav-lang-btn" onclick="toggleLangMenu(event)" title="Idioma" data-i18n-title="lang.label">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10A15.3 15.3 0 0 1 12 2z"/></svg>
            <span id="lang-current" style="font-weight:800;font-size:.75rem;margin-left:2px;">ES</span>
        </button>
        <div class="nav-lang-dd" id="nav-lang-dd">
            <button class="nav-lang-option" onclick="selectLang('es')"><span class="lang-flag">🇪🇸</span> Español</button>
            <button class="nav-lang-option" onclick="selectLang('en')"><span class="lang-flag">🇬🇧</span> English</button>
        </div>
    </div>

    <div class="nav-logos-center">
        <a href="mercado.php?game=pokemon"  class="nav-item"><img src="../img/pokemon.png"  class="nav-logo" alt="Pokémon"></a>
        <a href="mercado.php?game=yugioh"   class="nav-item"><img src="../img/yugioh.png"   class="nav-logo" alt="Yu-Gi-Oh!"></a>
        <a href="mercado.php?game=magic"    class="nav-item"><img src="../img/magic.png"    class="nav-logo" alt="Magic"></a>
        <a href="mercado.php?game=onepiece" class="nav-item"><img src="../img/onepiece.png" class="nav-logo" alt="One Piece"></a>
    </div>

    <div class="spacer"></div>

    <a href="sobres.php" class="nav-item nav-pill">
        <span>Sobres</span>
    </a>

    <a href="apuestas.php" class="nav-item nav-pill">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        <span data-i18n="nav.auctions">Subastas</span>
    </a>

    <a href="rankings.php" class="nav-item" title="Rankings">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
    </a>

    <button class="nav-item dark-toggle" onclick="toggleDarkMode()" title="Tema">
        <svg class="nav-icon icon-sun"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
        <svg class="nav-icon icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    </button>

    <?php if ($_nav_logged_in): ?>
    <div class="nav-bell-wrap" id="nav-bell-wrap">
        <button class="nav-bell-btn" id="nav-bell-btn" onclick="toggleBellMenu(event)" title="Notificaciones">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
            <span class="nav-bell-badge" id="nav-bell-badge"></span>
        </button>
        <div class="nav-bell-dd" id="nav-bell-dd">
            <div class="nav-bell-dd-header" data-i18n="nav.notifications">🔔 Notificaciones</div>
            <div class="nav-bell-dd-list" id="nav-bell-list">
                <div class="nav-bell-empty" data-i18n="nav.loading">Cargando…</div>
            </div>
        </div>
    </div>
    <a href="cart.php" class="nav-item nav-cart-btn" title="Carrito">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        <span data-i18n="nav.cart">Carrito</span>
    </a>
    <div class="nav-user-wrap" id="nav-user-wrap">
        <span class="nav-notif-dot" id="nav-notif-dot"></span>
        <button class="nav-user-btn" id="nav-user-btn" onclick="toggleUserMenu(event)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
            <?php echo htmlspecialchars($_SESSION['username']); ?>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>

        <div class="nav-dd" id="nav-dd">
            <a href="profile.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                <span data-i18n="dd.profile">Mi Perfil</span>
            </a>
            <a href="cart.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                <span data-i18n="nav.cart">Carrito</span>
            </a>
            <a href="mis-cartas.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z"/></svg>
                <span data-i18n="dd.cards">Mis Cartas</span>
            </a>
            <a href="amigos.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                <span data-i18n="dd.friends">Amigos</span>
            </a>
            <a href="lujanitos.php" style="color:#d97706;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                <span data-i18n="dd.buy_lujanitos">Comprar Lujanitos</span>
            </a>
            <a href="avatar_shop.php" style="color:#8b5cf6;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12l2.25 2.25L21 10.5"/></svg>
                <span data-i18n="dd.avatar_shop">Tienda de Avatares</span>
            </a>
            <?php if ($_nav_is_envios): ?>
            <hr>
            <a href="envios.php" class="dd-envios">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                <span data-i18n="dd.shipments">Gestión de Envíos</span>
            </a>
            <?php endif; ?>
            <?php if ($_nav_is_admin): ?>
            <hr>
            <a href="admin.php" class="dd-admin">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                <span data-i18n="dd.admin">Panel Admin</span>
            </a>
            <?php endif; ?>
            <hr>
            <a href="logout.php" class="dd-logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                <span data-i18n="dd.logout">Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <?php else: ?>
    <a href="auth.php" class="nav-item nav-pill">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
        <span data-i18n="nav.login">Iniciar sesión</span>
    </a>
    <?php endif; ?>
</nav>

<script>
function toggleUserMenu(e) {
    e.stopPropagation();
    const dd  = document.getElementById('nav-dd');
    const btn = document.getElementById('nav-user-btn');
    const open = dd.classList.toggle('open');
    btn.classList.toggle('active', open);
}
// Punto rojo en botón usuario + badge campana
(function checkNotifDot() {
    const dot   = document.getElementById('nav-notif-dot');
    const badge = document.getElementById('nav-bell-badge');
    if (!dot && !badge) return;
    fetch('../api/notifications.php?action=count')
        .then(r => r.json())
        .then(d => {
            if (d.unread > 0) {
                if (dot)   dot.classList.add('visible');
                if (badge) { badge.textContent = d.unread > 9 ? '9+' : d.unread; badge.classList.add('visible'); }
            }
        })
        .catch(() => {});
})();

function toggleBellMenu(e) {
    e.stopPropagation();
    const dd = document.getElementById('nav-bell-dd');
    if (!dd) return;
    const isOpen = dd.classList.toggle('open');
    if (isOpen) loadNotifications();
}

function loadNotifications() {
    const list  = document.getElementById('nav-bell-list');
    const badge = document.getElementById('nav-bell-badge');
    const dot   = document.getElementById('nav-notif-dot');
    if (!list) return;
    fetch('../api/notifications.php?action=list')
        .then(r => r.json())
        .then(d => {
            if (!d.items || d.items.length === 0) {
                list.innerHTML = '<div class="nav-bell-empty">Sin notificaciones todavía</div>';
            } else {
                list.innerHTML = d.items.map(n => {
                    const isUnread = !n.read_at;
                    const date = new Date(n.triggered_at).toLocaleDateString('es-ES', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
                    return `<div class="nav-bell-item${isUnread ? ' unread' : ''}">
                        <div class="nav-bell-item-title">🔔 ${n.card_name}</div>
                        <div class="nav-bell-item-sub">Subastada a ${n.target_price} LJ o menos · ${date}</div>
                    </div>`;
                }).join('');
            }
            // Marcar como leídas y ocultar badges
            if (d.unread > 0) {
                fetch('../api/notifications.php?action=mark_read', { method: 'POST' }).catch(() => {});
                if (badge) badge.classList.remove('visible');
                if (dot)   dot.classList.remove('visible');
            }
        })
        .catch(() => { list.innerHTML = '<div class="nav-bell-empty">Error al cargar</div>'; });
}

document.addEventListener('click', function(e) {
    const dd  = document.getElementById('nav-dd');
    const btn = document.getElementById('nav-user-btn');
    const bellDd = document.getElementById('nav-bell-dd');
    const langDd = document.getElementById('nav-lang-dd');
    if (dd)     dd.classList.remove('open');
    if (btn)    btn.classList.remove('active');
    if (bellDd) bellDd.classList.remove('open');
    if (langDd) langDd.classList.remove('open');
});

// ── Language selector ──
function toggleLangMenu(e) {
    e.stopPropagation();
    const dd = document.getElementById('nav-lang-dd');
    if (dd) dd.classList.toggle('open');
}
function selectLang(lang) {
    document.getElementById('nav-lang-dd').classList.remove('open');
    if (window.I18N) window.I18N.setLang(lang);
    else {
        localStorage.setItem('lt_lang', lang);
        const el = document.getElementById('lang-current');
        if (el) el.textContent = lang.toUpperCase();
    }
}
// Set initial display from stored lang
(function(){
    const stored = localStorage.getItem('lt_lang') || 'es';
    const el = document.getElementById('lang-current');
    if (el) el.textContent = stored.toUpperCase();
})();
</script>
<script src="../assets/js/i18n.js?v=<?php echo time(); ?>"></script>
