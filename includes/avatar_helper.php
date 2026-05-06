<?php
/**
 * Resuelve la URL del avatar de un usuario.
 * Soporta:
 *   - avatar_shop:KEY  → avatar estándar de la tienda (SVG generado)
 *   - img/avatars/...  → avatar subido por el usuario
 *   - URL absoluta     → imagen externa
 *   - vacío            → null (usar placeholder)
 *
 * @param string $avatar_url  Valor de users.avatar_url
 * @param string $basePrefix  Prefijo para rutas relativas (ej: '../')
 * @return string|null        URL de la imagen o null si no tiene
 */
function resolveAvatarUrl(?string $avatar_url, string $basePrefix = ''): ?string {
    if (empty($avatar_url)) return null;

    // Avatar de la tienda
    if (str_starts_with($avatar_url, 'avatar_shop:')) {
        $key = substr($avatar_url, strlen('avatar_shop:'));
        return getShopAvatarSvgUrl($key);
    }

    // URL absoluta
    if (str_starts_with($avatar_url, 'http://') || str_starts_with($avatar_url, 'https://')) {
        return $avatar_url;
    }

    // Ruta relativa (avatar subido)
    if (str_starts_with($avatar_url, '/')) return $avatar_url;
    return $basePrefix . $avatar_url;
}

/**
 * Genera un SVG data URI para un avatar estándar de la tienda.
 */
function getShopAvatarSvgUrl(string $key): string {
    $avatarDefs = getAvatarDefinitions();
    $def = $avatarDefs[$key] ?? null;
    if (!$def) return '';
    return $def['svg_data_uri'];
}

/**
 * Definiciones de todos los avatares estándar con SVG inline.
 * Cada avatar tiene: colores de gradiente, emoji/icono, y colores de fondo.
 */
function getAvatarDefinitions(): array {
    return [
        // ── Básicos (gratis) ──
        'explorer' => [
            'gradient' => ['#3b82f6','#1d4ed8'],
            'icon' => '🧭',
            'svg_data_uri' => buildAvatarDataUri('#3b82f6', '#1d4ed8', '🧭'),
        ],
        'rookie' => [
            'gradient' => ['#10b981','#059669'],
            'icon' => '🌱',
            'svg_data_uri' => buildAvatarDataUri('#10b981', '#059669', '🌱'),
        ],
        'traveler' => [
            'gradient' => ['#8b5cf6','#6d28d9'],
            'icon' => '🗺️',
            'svg_data_uri' => buildAvatarDataUri('#8b5cf6', '#6d28d9', '🗺️'),
        ],

        // ── Comunes ──
        'warrior' => [
            'gradient' => ['#ef4444','#b91c1c'],
            'icon' => '⚔️',
            'svg_data_uri' => buildAvatarDataUri('#ef4444', '#b91c1c', '⚔️'),
        ],
        'dark_mage' => [
            'gradient' => ['#7c3aed','#4c1d95'],
            'icon' => '🔮',
            'svg_data_uri' => buildAvatarDataUri('#7c3aed', '#4c1d95', '🔮'),
        ],
        'archer' => [
            'gradient' => ['#059669','#065f46'],
            'icon' => '🏹',
            'svg_data_uri' => buildAvatarDataUri('#059669', '#065f46', '🏹'),
        ],
        'pirate' => [
            'gradient' => ['#d97706','#92400e'],
            'icon' => '🏴‍☠️',
            'svg_data_uri' => buildAvatarDataUri('#d97706', '#92400e', '🏴‍☠️'),
        ],
        'astronaut' => [
            'gradient' => ['#0ea5e9','#0369a1'],
            'icon' => '🚀',
            'svg_data_uri' => buildAvatarDataUri('#0ea5e9', '#0369a1', '🚀'),
        ],

        // ── Raros ──
        'golden_dragon' => [
            'gradient' => ['#f59e0b','#d97706'],
            'icon' => '🐉',
            'svg_data_uri' => buildAvatarDataUri('#f59e0b', '#d97706', '🐉'),
        ],
        'shadow_ninja' => [
            'gradient' => ['#334155','#0f172a'],
            'icon' => '🥷',
            'svg_data_uri' => buildAvatarDataUri('#334155', '#0f172a', '🥷'),
        ],
        'phoenix' => [
            'gradient' => ['#f97316','#ea580c'],
            'icon' => '🔥',
            'svg_data_uri' => buildAvatarDataUri('#f97316', '#ea580c', '🔥'),
        ],
        'samurai' => [
            'gradient' => ['#dc2626','#7f1d1d'],
            'icon' => '⛩️',
            'svg_data_uri' => buildAvatarDataUri('#dc2626', '#7f1d1d', '⛩️'),
        ],
        'hacker' => [
            'gradient' => ['#22c55e','#15803d'],
            'icon' => '💻',
            'svg_data_uri' => buildAvatarDataUri('#22c55e', '#15803d', '💻'),
        ],

        // ── Épicos ──
        'celestial_king' => [
            'gradient' => ['#eab308','#a16207'],
            'icon' => '👑',
            'svg_data_uri' => buildAvatarDataUri('#eab308', '#a16207', '👑'),
        ],
        'valkyrie' => [
            'gradient' => ['#ec4899','#be185d'],
            'icon' => '🦋',
            'svg_data_uri' => buildAvatarDataUri('#ec4899', '#be185d', '🦋'),
        ],
        'cyborg' => [
            'gradient' => ['#64748b','#334155'],
            'icon' => '🤖',
            'svg_data_uri' => buildAvatarDataUri('#64748b', '#334155', '🤖'),
        ],
        'fox_spirit' => [
            'gradient' => ['#f97316','#9a3412'],
            'icon' => '🦊',
            'svg_data_uri' => buildAvatarDataUri('#f97316', '#9a3412', '🦊'),
        ],

        // ── Legendarios ──
        'deck_god' => [
            'gradient' => ['#fbbf24','#b45309'],
            'icon' => '🃏',
            'svg_data_uri' => buildAvatarDataUri('#fbbf24', '#b45309', '🃏'),
        ],
        'cosmic_phoenix' => [
            'gradient' => ['#a855f7','#581c87'],
            'icon' => '🌌',
            'svg_data_uri' => buildAvatarDataUri('#a855f7', '#581c87', '🌌'),
        ],
        'ancient_titan' => [
            'gradient' => ['#78716c','#292524'],
            'icon' => '🗿',
            'svg_data_uri' => buildAvatarDataUri('#78716c', '#292524', '🗿'),
        ],
    ];
}

/**
 * Genera un SVG data URI para un avatar con gradiente y emoji.
 */
function buildAvatarDataUri(string $c1, string $c2, string $emoji): string {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">'
         . '<defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">'
         . '<stop offset="0%" style="stop-color:' . $c1 . '"/>'
         . '<stop offset="100%" style="stop-color:' . $c2 . '"/>'
         . '</linearGradient></defs>'
         . '<rect width="200" height="200" rx="100" fill="url(#g)"/>'
         . '<text x="100" y="115" text-anchor="middle" font-size="80">' . $emoji . '</text>'
         . '</svg>';
    return 'data:image/svg+xml,' . rawurlencode($svg);
}
