# Loot & Trading — Registro de desarrollo (Sprint 3)

## Resumen del proyecto

Plataforma de trading de cartas coleccionables (Pokémon, Yu-Gi-Oh!, Magic, One Piece) con sistema de usuarios, mercado, subastas, wallapop entre usuarios y sistema de amigos con perfiles públicos.

Stack: PHP + MySQL (XAMPP), JavaScript vanilla, CSS propio. Sin frameworks.

---

## 1. Sistema de subastas infinitas

**Problema:** Las subastas de la BD expiraban y la página mostraba "No hay subastas activas".

**Solución:**
- `api/auction.php` — función `resolveEnded()`: cuando una subasta expira, se crea automáticamente una nueva con los mismos datos y duración aleatoria (1–6 horas).
- Función `spawnDefaultAuctions()`: catálogo de 8 cartas predefinidas (Charizard, Black Lotus, Blue-Eyes, etc.) que se insertan cuando no hay ninguna subasta activa (arranque en frío).
- Las subastas son infinitas: siempre hay al menos una activa.

---

## 2. Eliminación del sistema de chat

**Motivo:** El chat entre usuarios requería gestión de intermediación (comisión) que no entraba en el alcance del proyecto.

**Cambios:**
- Eliminado `api/chat.php` completo.
- Reescrito `amigos.php`: eliminado el panel de chat, mensajes, ofertas de cartas y estados de envío.
- Se mantiene el sistema de amigos completo (buscar, enviar solicitud, aceptar/rechazar, eliminar).

---

## 3. Perfil público de amigos

**Objetivo:** Al hacer clic en "Ver perfil" de un amigo, mostrar una vista idéntica al perfil propio (`profile.php`).

**Implementación:**
- `amigos.php` — overlay full-screen con fondo de cartas animado, avatar, nombre, stats y secciones.
- `api/friends.php` (case `profile`) devuelve:
  - Datos básicos del usuario
  - Stats: cartas compradas, colecciones distintas, subastas pujadas
  - Actividad reciente (tabla `user_activity`)
  - Subastas ganadas con estado de reclamación
  - Favoritos (últimos 12)
- JS: función `openProfile(userId)` que carga los datos vía fetch y los inyecta en el overlay dinámicamente.

---

## 4. Reorganización en carpetas

**Estructura final:**

```
Loot_and_Trading_Proyecto_Final/
├── index.php             ← stub: redirige a pages/index.php
├── pages/                ← todas las vistas PHP
│   ├── index.php
│   ├── mercado.php
│   ├── apuestas.php
│   ├── amigos.php
│   ├── profile.php
│   ├── mis-cartas.php
│   ├── wallapop.php
│   ├── cart.php
│   ├── auth.php
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── api/                  ← endpoints JSON (fetch desde JS)
│   ├── auction.php
│   ├── favorites.php
│   ├── friends.php
│   ├── market.php
│   ├── wallapop.php
│   ├── cart.php
│   ├── update_profile.php
│   ├── upload_avatar.php
│   ├── upload_card.php
│   ├── shipments.php
│   └── api_proxy.php
├── assets/
│   ├── css/styles.css
│   └── js/
│       ├── script.js     ← lógica global (mercado, favoritos, carrito)
│       └── csrf.js       ← wrapper CSRF para fetch()
├── includes/
│   ├── db.php            ← conexión MySQL
│   ├── navbar.php        ← nav compartida (incluida en cada página)
│   └── csrf.php          ← funciones de token CSRF
├── img/                  ← logos de juegos, avatares, cartas wallapop
│   ├── avatars/
│   └── wallapop/
└── cache/
    └── cache_onepiece.json
```

**Ajustes de rutas tras el movimiento:**
- CSS/JS: `assets/css/styles.css` → `../assets/css/styles.css`
- PHP includes: `require_once dirname(__DIR__) . '/includes/db.php'`
- Fetch JS: `fetch('api/xxx')` → `fetch('../api/xxx')`
- Navbar imgs: `src="img/xxx"` → `src="../img/xxx"`
- `api/cart.php` stub actualizado a `pages/cart.php`

---

## 5. Fix foto de perfil tras reorganización

**Problema:** Las fotos de avatar dejaron de mostrarse porque la BD guarda rutas relativas (`img/avatars/xxx.jpg`) y desde `pages/` el navegador las buscaba en `pages/img/avatars/`.

**Solución:**
- `amigos.php` — función JS `fixUrl(u)`: añade `../` a URLs relativas antes de asignarlas como `src`.
- `profile.php` — PHP prefija `../` al `avatar_url` antes de renderizar, y el JS hace lo mismo al actualizar la foto tras upload.

---

## 6. Mejoras de seguridad

### 6.1 Prepared statements (SQL Injection)
- Reemplazados todos los `$conn->query("... $variable ...")` por prepared statements con `bind_param()`.
- Afectaba a: `api/auction.php` (función `resolveEnded`, `spawnDefaultAuctions`, `claimWin`) y `api/wallapop.php` (case `list` y case `buy`).

### 6.2 Ocultación de errores de BD
- `includes/db.php`: el error de conexión ya no se muestra al usuario — se loguea con `error_log()` y se devuelve un mensaje genérico.
- `api/favorites.php`: reemplazados los `$conn->error` / `$stmt->error` en las respuestas JSON por "Error interno del servidor".

### 6.3 Validación MIME en uploads
- `api/wallapop.php`: añadida validación con `finfo_file(FILEINFO_MIME_TYPE)` además de la extensión, igual que ya tenían `upload_avatar.php` y `upload_card.php`. Evita que un `.php` renombrado a `.jpg` se suba al servidor.

### 6.4 Protección XSS
- `pages/wallapop.php`: añadida función `esc()` que convierte texto a nodo DOM antes de insertarlo en `innerHTML`, evitando XSS almacenado con nombres de carta o vendedores maliciosos.

### 6.5 Protección CSRF (Cross-Site Request Forgery)
- `includes/csrf.php`: genera un token aleatorio de 64 hex chars en sesión (`csrf_token()`) y lo valida (`csrf_verify()`).
- `includes/navbar.php`: inyecta el token como `<meta name="csrf-token">` en todas las páginas.
- `assets/js/csrf.js`: override de `window.fetch` que añade automáticamente el header `X-CSRF-Token` en todas las peticiones POST.
- Validado en: `api/auction.php`, `api/favorites.php`, `api/friends.php`, `api/wallapop.php`, `api/update_profile.php`, `api/upload_avatar.php`, `api/upload_card.php`.

---

## Funcionalidades principales del proyecto

| Módulo | Descripción |
|--------|-------------|
| **Mercado** | Catálogo de cartas por juego con filtros, buscador, favoritos y carrito de compra |
| **Subastas** | Pujas en tiempo real con LootCoins, historial, mis pujas, mis victorias y reclamación |
| **Wallapop** | Anuncios entre usuarios para vender cartas físicas o digitales |
| **Amigos** | Buscar usuarios, enviar/aceptar solicitudes, ver perfil público completo |
| **Perfil** | Avatar, stats, información personal, actividad reciente, subastas ganadas, favoritos |
| **Mis Cartas** | Historial de cartas compradas y ganadas en subasta |
| **Carrito** | Compra con pedidos, historial de órdenes |
| **Auth** | Registro, login, sesión, logout con hashing bcrypt |

---

## Tecnologías usadas

- **Backend:** PHP 8, MySQLi con prepared statements, sesiones nativas
- **Base de datos:** MySQL / MariaDB (XAMPP)
- **Frontend:** JavaScript ES6+ (fetch, async/await, DOM), CSS3 (custom properties, grid, animations)
- **APIs externas:** PokémonTCG API, YGOPRODECK API, Scryfall API, TCGDex API (One Piece con caché local)
- **Servidor:** Apache (XAMPP localhost)
