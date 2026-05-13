/**
 * i18n.js — Sistema de internacionalización Loot&Trading
 * Idiomas: ES (español), EN (inglés)
 * Uso: añadir data-i18n="clave" a elementos HTML
 *       añadir data-i18n-placeholder="clave" para placeholders
 *       añadir data-i18n-title="clave" para títulos
 */
(function () {
    'use strict';

    const TRANSLATIONS = {
        es: {
            // ── Navbar ──
            'nav.auctions':        'Subastas',
            'nav.cart':            'Carrito',
            'nav.notifications':   '🔔 Notificaciones',
            'nav.loading':         'Cargando…',
            'nav.login':           'Entrar',

            // ── User dropdown ──
            'dd.profile':          'Mi Perfil',
            'dd.cards':            'Mis Cartas',
            'dd.friends':          'Amigos',
            'dd.buy_lujanitos':    'Comprar Lujanitos',
            'dd.avatar_shop':      'Tienda de Avatares',
            'dd.shipments':        'Gestión de Envíos',
            'dd.admin':            'Panel Admin',
            'dd.logout':           'Cerrar Sesión',

            // ── Footer ──
            'footer.desc':         'El marketplace definitivo de cartas coleccionables con precios en tiempo real. Pokémon, Yu-Gi-Oh!, Magic y One Piece.',
            'footer.markets':      'Mercados',
            'footer.platform':     'Plataforma',
            'footer.auctions':     'Subastas',
            'footer.cart':         'Carrito',
            'footer.profile':      'Mi Perfil',
            'footer.friends':      'Amigos',
            'footer.help':         'Ayuda',
            'footer.faq':          'Preguntas Frecuentes',
            'footer.rights':       'Todos los derechos reservados.',

            // ── Index / Home ──
            'home.welcome':        '¡Hola de nuevo,',
            'home.subtitle':       'El mercado definitivo de TCGs con precios en tiempo real.',
            'home.explore':        'Explorar Colecciones',
            'home.search_market':  '🔍 Buscar en el Mercado',
            'home.auctions':       '🏷️ Subastas en vivo',
            'home.card_of_day':    'Carta del día',
            'home.community_says': 'Lo que dice la comunidad',
            'home.trending':       '🔥 Lo más buscado esta semana',
            'home.see_all':        'Ver todo el mercado →',
            'home.loading':        'Cargando...',

            // ── Sections index ──
            'home.pokemon_title':  'Pokémon TCG',
            'home.pokemon_desc':   'Explora la colección completa de cartas Pokémon, desde Base Set hasta las últimas expansiones. Precios actualizados al instante.',
            'home.yugioh_title':   'Yu-Gi-Oh!',
            'home.yugioh_desc':    'Descubre cartas de Yu-Gi-Oh! con precios del mercado real. De Blue-Eyes a las últimas Spell Cards.',
            'home.magic_title':    'Magic: The Gathering',
            'home.magic_desc':     'El TCG original. Encuentra desde cartas de Alpha hasta las últimas colecciones de Magic.',
            'home.onepiece_title': 'One Piece TCG',
            'home.onepiece_desc':  'El TCG más nuevo del mercado. Cartas de One Piece con precios en tiempo real.',
            'home.explore_btn':    'Explorar',
            'home.cards_available': 'Cartas disponibles',
            'home.tcgs_supported':  'TCGs soportados',
            'home.prices_updated':  'Precios actualizados',
            'home.free_collectors': 'Gratis para coleccionistas',

            // ── Auth ──
            'auth.login_title':    '🔐 Iniciar Sesión',
            'auth.username':       'Usuario',
            'auth.password':       'Contraseña',
            'auth.login_btn':      'Iniciar Sesión',
            'auth.no_account':     '¿No tienes cuenta?',
            'auth.register_here':  'Regístrate aquí',
            'auth.register_title': '✨ Registrarse',
            'auth.fullname':       'Nombre completo',
            'auth.email':          'Email',
            'auth.address':        'Dirección de envío (calle, ciudad, CP)',
            'auth.register_btn':   'Registrarse',
            'auth.has_account':    '¿Ya tienes cuenta?',
            'auth.login_here':     'Inicia sesión aquí',

            // ── Market ──
            'market.search':       'Buscar carta por nombre...',
            'market.filters':      'Filtros',
            'market.sort':         'Ordenar',
            'market.price':        'Precio',
            'market.add_cart':     'Añadir al carrito',
            'market.view':        'Ver detalles',
            'market.no_results':   'No se encontraron resultados.',
            'market.loading':      'Buscando cartas...',
            'market.price_asc':    'Precio: menor a mayor',
            'market.price_desc':   'Precio: mayor a menor',
            'market.name_asc':     'Nombre: A-Z',
            'market.name_desc':    'Nombre: Z-A',

            // ── Auctions ──
            'auctions.title':      'Subastas en vivo',
            'auctions.create':     'Crear Subasta',
            'auctions.bid':        'Pujar',
            'auctions.current_bid':'Puja actual',
            'auctions.time_left':  'Tiempo restante',
            'auctions.no_auctions':'No hay subastas activas.',
            'auctions.your_bid':   'Tu puja',
            'auctions.min_bid':    'Puja mínima',
            'auctions.place_bid':  'Realizar puja',
            'auctions.my_auctions':'Mis Subastas',
            'auctions.my_bids':    '📋 Mis Pujas Activas',
            'auctions.ended':      'Finalizada',
            'auctions.active':     'Activa',

            // ── Cart ──
            'cart.title':          'Tu Carrito',
            'cart.empty':          'Tu carrito está vacío.',
            'cart.total':          'Total',
            'cart.checkout':       'Tramitar Pedido',
            'cart.remove':         'Eliminar',
            'cart.qty':            'Cantidad',

            // ── Profile ──
            'profile.title':       'Mi Perfil',
            'profile.edit':        'Editar perfil',
            'profile.save':        'Guardar',
            'profile.change_photo':'Cambiar foto',
            'profile.level':       'Nivel',
            'profile.achievements':'Logros',
            'profile.activity':    'Actividad reciente',
            'profile.member_since':'Miembro desde',
            'profile.orders':      'Pedidos',
            'profile.collection':  'Mi Colección',

            // ── Friends ──
            'friends.title':       'Amigos',
            'friends.search':      'Buscar usuarios...',
            'friends.requests':    'Solicitudes',
            'friends.my_friends':  'Mis Amigos',
            'friends.add':         '+ Añadir',
            'friends.accept':      '✓ Aceptar',
            'friends.view_profile':'Ver perfil',
            'friends.no_friends':  'Aún no tienes amigos.',
            'friends.searching':   'Buscando...',
            'friends.no_results':  'Sin resultados',
            'friends.sent':        'Solicitud enviada',

            // ── Lujanitos ──
            'lj.title':            'Tienda de Lujanitos 💰',
            'lj.desc':             'Consigue Lujanitos para pujar en subastas, comprar cartas y participar en el mercado. 1€ = 1 Lujanito.',
            'lj.balance':          'Tu saldo:',
            'lj.buy':              'Comprar',
            'lj.popular':          'Popular',
            'lj.what_are':         '¿Qué son los Lujanitos?',
            'lj.info1':            'La moneda oficial de Loot&Trading. Úsalos para pujar en subastas y comprar cartas en el mercado.',
            'lj.info2':            'Al registrarte recibes <strong>1.000 LJ de bienvenida</strong> sin coste.',
            'lj.info3':            'Cambio fijo: <strong>1 € = 1 Lujanito</strong>. Lo que ves es lo que pagas.',
            'lj.info4':            'Los Lujanitos se añaden al instante a tu saldo después de confirmar.',
            'lj.info5':            'Compra simulada — no se realiza ningún cargo real.',
            'lj.login_to_buy':     'Inicia sesión para comprar',

            // ── Avatar shop ──
            'av.title':            'Tienda de Avatares',
            'av.desc':             'Personaliza tu perfil con avatares únicos. Compra con Lujanitos y equípalos al instante.',
            'av.balance':          'Tu saldo:',
            'av.all':              'Todos',
            'av.free':             'Gratis',
            'av.fantasy':          'Fantasía',
            'av.adventure':        'Aventura',
            'av.legendary':        'Legendario',
            'av.unlocked':         'desbloqueados',
            'av.equip':            'Equipar',
            'av.unequip':          'Desequipar',
            'av.claim':            'Reclamar',
            'av.buy':              'Comprar',
            'av.cancel':           'Cancelar',
            'av.equipped':         '✓ Equipado',
            'av.owned':            '✓ Tuyo',
            'av.in_collection':    'En tu colección',
            'av.how_it_works':     '¿Cómo funciona?',
            'av.info1':            'Compra avatares con tus <strong>Lujanitos</strong> o reclama los gratuitos.',
            'av.info2':            'Haz clic en <strong>Equipar</strong> para usarlo como foto de perfil al instante.',
            'av.info3':            'Tu avatar se mostrará en tu perfil, rankings, amigos y subastas.',
            'av.info4':            'Puedes subir también una foto personalizada desde <strong>Mi Perfil</strong>.',
            'av.info5':            'Los avatares legendarios tienen efectos exclusivos. ¡Colecciónalos todos!',
            'av.loading':          'Cargando avatares...',
            'av.no_category':      'No hay avatares en esta categoría',

            // ── Rankings ──
            'rank.title':          'Rankings',
            'rank.subtitle':       'Los mejores coleccionistas de Loot & Trading',
            'rank.by_level':       'Top Nivel',
            'rank.by_lujanitos':   'Top Lujanitos',
            'rank.by_cards':       'Cartas Más Vendidas',
            'rank.no_level_data':  'Todavía no hay datos de niveles.',
            'rank.no_lj_data':     'Todavía no hay datos de Lujanitos.',

            // ── FAQ ──
            'faq.title':           'Preguntas Frecuentes',
            'faq.subtitle':        'Todo lo que necesitas saber sobre Loot&Trading',

            // ── Admin ──
            'admin.title':         'Panel de Administración',
            'admin.welcome':       'Bienvenido',
            'admin.overview':      'Vista general de la plataforma.',
            'admin.users':         'Usuarios',
            'admin.auctions_tab':  'Subastas',
            'admin.activity':      'Actividad',
            'admin.orders':        'Pedidos',
            'admin.revenue':       'Ingresos mercado',
            'admin.total_auctions':'Subastas totales',
            'admin.active_auctions':'Subastas activas',
            'admin.total_bids':    'Pujas totales',
            'admin.total_coins':   'Lujanitos totales',
            'admin.actions':       'Acciones registradas',
            'admin.search_users':  'Buscar usuario...',
            'admin.search_auctions':'Buscar subasta...',
            'admin.export_csv':    'Exportar CSV',
            'admin.view_live':     'Ver plataforma',
            'admin.chart_users':   'Registro de usuarios',
            'admin.chart_users_sub':'Nuevos usuarios por mes (últimos 6 meses)',
            'admin.chart_sales':   'Ventas del mercado',
            'admin.chart_sales_sub':'Pedidos e ingresos mensuales',
            'admin.chart_by_game': 'Ventas por juego',
            'admin.chart_by_game_sub':'Distribución de ingresos por franquicia',
            'admin.chart_auctions_game':'Subastas por juego',
            'admin.chart_auctions_game_sub':'Activas vs terminadas por franquicia',
            'admin.chart_activity_type':'Tipos de actividad',
            'admin.chart_activity_type_sub':'Distribución de acciones de los usuarios',
            'admin.chart_daily':   'Actividad diaria',
            'admin.chart_daily_sub':'Acciones registradas (últimos 14 días)',
            'admin.chart_bids_hour':'Hora de pujas',
            'admin.chart_bids_hour_sub':'Distribución horaria de pujas en subastas',
            'admin.chart_auction_rev':'Ingresos por subastas',
            'admin.chart_auction_rev_sub':'Valor de pujas ganadoras por mes',
            'admin.rank_coins':    'Top Usuarios por Lujanitos',
            'admin.rank_xp':       'Top Usuarios por XP',
            'admin.top_cards':     'Cartas más vendidas',
            'admin.registered':    'registrados',
            'admin.no_users':      'No hay usuarios registrados',
            'admin.no_users_desc': 'Los usuarios aparecerán aquí cuando se registren en la plataforma.',
            'admin.level':         'Nivel',
            'admin.role':          'Rol',
            'admin.registered_date':'Registro',
            'admin.actions_col':   'Acciones',
            'admin.no_results':    'Sin resultados',
            'admin.no_users_match':'Ningún usuario coincide con tu búsqueda.',
            'admin.recent_auctions':'Subastas recientes',
            'admin.shown':         'mostradas',
            'admin.no_auctions':   'No hay subastas',
            'admin.no_auctions_desc':'Las subastas creadas por los usuarios aparecerán aquí.',
            'admin.card':          'Carta',
            'admin.current_bid':   'Puja actual',
            'admin.seller':        'Vendedor',
            'admin.status':        'Estado',
            'admin.end':           'Fin',
            'admin.action':        'Acción',
            'admin.finished':      'Terminada',
            'admin.no_auctions_match':'Ninguna subasta coincide con tu búsqueda.',
            'admin.global_activity':'Actividad reciente global',
            'admin.latest':        'últimas',
            'admin.no_activity':   'Sin actividad registrada',
            'admin.no_activity_desc':'Las acciones de los usuarios aparecerán aquí.',
            'admin.type':          'Tipo',
            'admin.title_col':     'Título',
            'admin.description':   'Descripción',
            'admin.date':          'Fecha',

            // ── Common ──
            'common.loading':      'Cargando...',
            'common.error':        'Error',
            'common.success':      'Éxito',
            'common.confirm':      'Confirmar',
            'common.cancel':       'Cancelar',
            'common.save':         'Guardar Cambios',
            'common.delete':       'Eliminar',
            'common.close':        'Cerrar',
            'common.yes':          'Sí',
            'common.no':           'No',
            'common.rarity.common':    'Común',
            'common.rarity.rare':      'Raro',
            'common.rarity.epic':      'Épico',
            'common.rarity.legendary': 'Legendario',

            // ── Modal / Card detail ──
            'modal.add_fav':       '⭐ Añadir a favoritos',
            'modal.remove_fav':    '⭐ Quitar de favoritos',
            'modal.price_history': '📈 Histórico de Precios (30 días)',
            'modal.sellers':       '🛒 Ofertas de Vendedores',
            'modal.seller':        'Vendedor',
            'modal.condition':     'Estado',
            'modal.price':         'Precio',
            'modal.action':        'Acción',
            'modal.add_best':      'Añadir mejor oferta al Carrito',
            'modal.related':       'Cartas Relacionadas',
            'modal.price_alert':   '🔔 Alerta de precio',
            'modal.alert_desc':    'Te notificaremos cuando aparezca en subasta a tu precio objetivo o menos.',
            'modal.target_price':  'Precio objetivo (LJ)',
            'modal.create_alert':  'Crear alerta',

            // ── Cart extras ──
            'cart.continue':       'Seguir comprando',
            'cart.clear':          'Vaciar',
            'cart.empty_sub':      'Explora el marketplace y añade tu primera carta.',
            'cart.explore':        'Explorar cartas',
            'cart.update':         'Actualizar',
            'cart.summary':        'Resumen',
            'cart.your_balance':   'Tu saldo',
            'cart.total_pay':      'Total a pagar',
            'cart.insufficient':   '⚠️ Saldo insuficiente.',
            'cart.get_lj':         'Conseguir Lujanitos →',
            'cart.finish':         'Finalizar compra',
            'cart.note':           'Se descuentan Lujanitos de tu saldo. 1€ = 1 LJ.',

            // ── Market extras ──
            'market.apply':        'Aplicar Filtros',
            'market.clear':        'Limpiar',
            'market.condition':    'Estado (Condición)',
            'market.search_card':  'Buscar carta:',
            'market.catalog':      'Catálogo completo con todas las expansiones.',
            'market.load_more':    'Cargar más cartas',
            'market.load_catalog': 'Ver todo el catálogo y filtros',

            // ── Auctions extras ──
            'auctions.active_tab': '⚡ Activas',
            'auctions.ended_tab':  '🏁 Terminadas',
            'auctions.all':        'Todos',
            'auctions.sell':       '📤 Vender',
            'auctions.sell_title': '📤 Poner carta en subasta',
            'auctions.sell_desc':  'Elige la carta, establece el precio de salida y cuánto tiempo durará la subasta. Todos los usuarios podrán pujar.',
            'auctions.card_name':  'Nombre de la carta *',
            'auctions.card_image': 'Imagen de la carta',
            'auctions.game':       'Juego *',
            'auctions.base_price': 'Precio base (Lujanitos) *',
            'auctions.duration':   'Duración',
            'auctions.preview':    'Vista previa',
            'auctions.publish':    '🚀 Publicar subasta',
            'auctions.gallery':    '📁 Galería',
            'auctions.login_bid':  'Inicia sesión para pujar',
            'auctions.hour':        'hora',
            'auctions.hours':       'horas',
            'auctions.days':        'días',
            'auctions.hero_subtitle': 'Puja en tiempo real con Lujanitos en cartas TCG.',

            // ── Lujanitos extras ──
            'lj.your_balance':     'Tu saldo:',
            'lj.info_row1':        'La moneda oficial de Loot&Trading. Úsalos para pujar en subastas y comprar cartas en el mercado.',
            'lj.info_row2':        'Al registrarte recibes <strong>1.000 LJ de bienvenida</strong> sin coste.',
            'lj.info_row3':        'Cambio fijo: <strong>1 € = 1 Lujanito</strong>. Lo que ves es lo que pagas.',
            'lj.info_row4':        'Los Lujanitos se añaden al instante a tu saldo después de confirmar.',
            'lj.info_row5':        'Compra simulada — no se realiza ningún cargo real.',

            // ── Friends extras ──
            'friends.search_btn':  'Buscar',
            'friends.view_profile':'Ver perfil',
            'friends.no_friends':  'Aún no tienes amigos.',

            // ── My Cards ──
            'mycards.title':         '📦 Mis Cartas',
            'mycards.all_games':     'Todos los juegos',
            'mycards.shipping_status':'Estado envío',
            'mycards.refresh_note':  'Actualiza el estado cada 5 min.',
            'mycards.no_cards':      'Aún no tienes cartas',
            'mycards.no_cards_desc': 'Compra en el mercado o gana una subasta para verlas aquí.',
            'mycards.purchase':      '🛒 Compra',
            'mycards.auction_win':   '🏆 Subasta',
            'mycards.received':      'Recibido',
            'mycards.preparing':     'Preparando',
            'mycards.shipped':       'Enviado',
            'mycards.delivered':     'Entregado',

            // ── Profile ──
            'profile.title':         'Mi Perfil',
            'profile.cards':         'Cartas',
            'profile.collections':   'Colecciones',
            'profile.bids':          'Pujas',
            'profile.personal_info': '📋 Información Personal',
            'profile.name':          'Nombre',
            'profile.username':      'Usuario',
            'profile.email':         'Email',
            'profile.member_since':  'Miembro desde',
            'profile.address':       'Dirección',
            'profile.edit':          'Editar Perfil',
            'profile.recent_activity':'🎯 Actividad Reciente',
            'profile.won_auctions':  '🏆 Subastas Ganadas',
            'profile.favorites':     '⭐ Favoritos',
            'profile.orders':        '📦 Mis Pedidos',
            'profile.achievements':  '🏅 Logros',
            'profile.price_alerts':  '🔔 Alertas de Precio',
            'profile.no_activity':   'Aún no tienes actividad',
            'profile.no_activity_desc':'Comienza explorando el catálogo de cartas',
            'profile.no_auctions':   'Aún no has ganado ninguna subasta',
            'profile.explore_auctions':'Explorar subastas',
            'profile.no_orders':     'Aún no has realizado ningún pedido',
            'profile.explore_market': 'Explorar el mercado',
            'profile.no_favorites':  'No tienes cartas favoritas',
            'profile.no_favorites_desc':'Añade cartas a tus favoritos para verlas aquí',
            'profile.no_alerts':     'No tienes alertas configuradas',
            'profile.no_alerts_desc': 'Crea alertas desde la página de Subastas para recibir notificaciones cuando aparezca una carta a tu precio',
            'profile.locked':        'Bloqueado',
            'profile.collection_badges':'🏅 Insignias de Colección',
            'profile.cards_collected':'cartas',
            'profile.cards_short':   'cartas',
            'profile.total_collection':'Colección Total',

            // ── Language ──
            'lang.es':             'ES',
            'lang.en':             'EN',
            'lang.label':          'Idioma',
        },

        en: {
            // ── Navbar ──
            'nav.auctions':        'Auctions',
            'nav.cart':            'Cart',
            'nav.notifications':   '🔔 Notifications',
            'nav.loading':         'Loading…',
            'nav.login':           'Login',

            // ── User dropdown ──
            'dd.profile':          'My Profile',
            'dd.cards':            'My Cards',
            'dd.friends':          'Friends',
            'dd.buy_lujanitos':    'Buy Lujanitos',
            'dd.avatar_shop':      'Avatar Shop',
            'dd.shipments':        'Shipment Management',
            'dd.admin':            'Admin Panel',
            'dd.logout':           'Log Out',

            // ── Footer ──
            'footer.desc':         'The ultimate collectible card marketplace with real-time prices. Pokémon, Yu-Gi-Oh!, Magic and One Piece.',
            'footer.markets':      'Markets',
            'footer.platform':     'Platform',
            'footer.auctions':     'Auctions',
            'footer.cart':         'Cart',
            'footer.profile':      'My Profile',
            'footer.friends':      'Friends',
            'footer.help':         'Help',
            'footer.faq':          'FAQ',
            'footer.rights':       'All rights reserved.',

            // ── Index / Home ──
            'home.welcome':        'Welcome back,',
            'home.subtitle':       'The ultimate TCG marketplace with real-time prices.',
            'home.explore':        'Explore Collections',
            'home.search_market':  '🔍 Search the Market',
            'home.auctions':        '🏷️ Auctions Live',
            'home.card_of_day':    'Card of the day',
            'home.community_says': 'What the community says',
            'home.trending':       '🔥 Trending this week',
            'home.see_all':        'See full market →',
            'home.loading':        'Loading...',

            // ── Sections index ──
            'home.pokemon_title':  'Pokémon TCG',
            'home.pokemon_desc':   'Explore the full Pokémon card collection, from Base Set to the latest expansions. Prices updated instantly.',
            'home.yugioh_title':   'Yu-Gi-Oh!',
            'home.yugioh_desc':    'Discover Yu-Gi-Oh! cards with real market prices. From Blue-Eyes to the latest Spell Cards.',
            'home.magic_title':    'Magic: The Gathering',
            'home.magic_desc':     'The original TCG. Find cards from Alpha to the latest Magic collections.',
            'home.onepiece_title': 'One Piece TCG',
            'home.onepiece_desc':  'The newest TCG on the market. One Piece cards with real-time prices.',
            'home.explore_btn':    'Explore',
            'home.cards_available': 'Cards available',
            'home.tcgs_supported':  'TCGs supported',
            'home.prices_updated':  'Prices updated',
            'home.free_collectors': 'Free for collectors',

            // ── Auth ──
            'auth.login_title':    '🔐 Log In',
            'auth.username':       'Username',
            'auth.password':       'Password',
            'auth.login_btn':      'Log In',
            'auth.no_account':     "Don't have an account?",
            'auth.register_here':  'Sign up here',
            'auth.register_title': '✨ Sign Up',
            'auth.fullname':       'Full name',
            'auth.email':          'Email',
            'auth.address':        'Shipping address (street, city, zip)',
            'auth.register_btn':   'Sign Up',
            'auth.has_account':    'Already have an account?',
            'auth.login_here':     'Log in here',

            // ── Market ──
            'market.search':       'Search card by name...',
            'market.filters':      'Filters',
            'market.sort':         'Sort',
            'market.price':        'Price',
            'market.add_cart':     'Add to cart',
            'market.view':        'View details',
            'market.no_results':   'No results found.',
            'market.loading':      'Searching cards...',
            'market.price_asc':    'Price: low to high',
            'market.price_desc':   'Price: high to low',
            'market.name_asc':     'Name: A-Z',
            'market.name_desc':    'Name: Z-A',

            // ── Auctions ──
            'auctions.title':      'Live Auctions',
            'auctions.create':     'Create Auction',
            'auctions.bid':        'Bid',
            'auctions.current_bid':'Current bid',
            'auctions.time_left':  'Time left',
            'auctions.no_auctions':'No active auctions.',
            'auctions.your_bid':   'Your bid',
            'auctions.min_bid':    'Minimum bid',
            'auctions.place_bid':  'Place bid',
            'auctions.my_auctions':'My Auctions',
            'auctions.my_bids':    '📋 My Active Bids',
            'auctions.ended':      'Ended',
            'auctions.active':     'Active',

            // ── Cart ──
            'cart.title':          'Your Cart',
            'cart.empty':          'Your cart is empty.',
            'cart.total':          'Total',
            'cart.checkout':       'Checkout',
            'cart.remove':         'Remove',
            'cart.qty':            'Quantity',

            // ── Profile ──
            'profile.title':       'My Profile',
            'profile.edit':        'Edit profile',
            'profile.save':        'Save',
            'profile.change_photo':'Change photo',
            'profile.level':       'Level',
            'profile.achievements':'Achievements',
            'profile.activity':    'Recent activity',
            'profile.member_since':'Member since',
            'profile.orders':      'Orders',
            'profile.collection':  'My Collection',

            // ── Friends ──
            'friends.title':       'Friends',
            'friends.search':      'Search users...',
            'friends.requests':    'Requests',
            'friends.my_friends':  'My Friends',
            'friends.add':         '+ Add',
            'friends.accept':      '✓ Accept',
            'friends.view_profile':'View profile',
            'friends.no_friends':  "You don't have any friends yet.",
            'friends.searching':   'Searching...',
            'friends.no_results':  'No results',
            'friends.sent':        'Request sent',

            // ── Lujanitos ──
            'lj.title':            'Lujanitos Store 💰',
            'lj.desc':             'Get Lujanitos to bid on auctions, buy cards and participate in the market. 1€ = 1 Lujanito.',
            'lj.balance':          'Your balance:',
            'lj.buy':              'Buy',
            'lj.popular':          'Popular',
            'lj.what_are':         'What are Lujanitos?',
            'lj.info1':            "Loot&Trading's official currency. Use them to bid on auctions and buy cards in the market.",
            'lj.info2':            'When you register you get <strong>1,000 welcome LJ</strong> for free.',
            'lj.info3':            'Fixed rate: <strong>1 € = 1 Lujanito</strong>. What you see is what you pay.',
            'lj.info4':            'Lujanitos are added instantly to your balance after confirmation.',
            'lj.info5':            'Simulated purchase — no real charges are made.',
            'lj.login_to_buy':     'Log in to buy',

            // ── Avatar shop ──
            'av.title':            'Avatar Shop',
            'av.desc':             'Customize your profile with unique avatars. Buy with Lujanitos and equip them instantly.',
            'av.balance':          'Your balance:',
            'av.all':              'All',
            'av.free':             'Free',
            'av.fantasy':          'Fantasy',
            'av.adventure':        'Adventure',
            'av.legendary':        'Legendary',
            'av.unlocked':         'unlocked',
            'av.equip':            'Equip',
            'av.unequip':          'Unequip',
            'av.claim':            'Claim',
            'av.buy':              'Buy',
            'av.cancel':           'Cancel',
            'av.equipped':         '✓ Equipped',
            'av.owned':            '✓ Yours',
            'av.in_collection':    'In your collection',
            'av.how_it_works':     'How does it work?',
            'av.info1':            'Buy avatars with your <strong>Lujanitos</strong> or claim free ones.',
            'av.info2':            'Click <strong>Equip</strong> to use it as your profile picture instantly.',
            'av.info3':            'Your avatar will be shown on your profile, rankings, friends and auctions.',
            'av.info4':            'You can also upload a custom photo from <strong>My Profile</strong>.',
            'av.info5':            'Legendary avatars have exclusive effects. Collect them all!',
            'av.loading':          'Loading avatars...',
            'av.no_category':      'No avatars in this category',

            // ── Rankings ──
            'rank.title':          'Rankings',
            'rank.subtitle':       'The best collectors on Loot & Trading',
            'rank.by_level':       'Top Level',
            'rank.by_lujanitos':   'Top Lujanitos',
            'rank.by_cards':       'Top Selling Cards',
            'rank.no_level_data':  'No level data yet.',
            'rank.no_lj_data':     'No Lujanitos data yet.',

            // ── FAQ ──
            'faq.title':           'Frequently Asked Questions',
            'faq.subtitle':        'Everything you need to know about Loot&Trading',

            // ── Admin ──
            'admin.title':         'Admin Panel',
            'admin.welcome':       'Welcome',
            'admin.overview':      'Platform overview.',
            'admin.users':         'Users',
            'admin.auctions_tab':  'Auctions',
            'admin.activity':      'Activity',
            'admin.orders':        'Orders',
            'admin.revenue':       'Market revenue',
            'admin.total_auctions':'Total auctions',
            'admin.active_auctions':'Active auctions',
            'admin.total_bids':    'Total bids',
            'admin.total_coins':   'Total Lujanitos',
            'admin.actions':       'Registered actions',
            'admin.search_users':  'Search user...',
            'admin.search_auctions':'Search auction...',
            'admin.export_csv':    'Export CSV',
            'admin.view_live':     'View platform',
            'admin.chart_users':   'User registrations',
            'admin.chart_users_sub':'New users per month (last 6 months)',
            'admin.chart_sales':   'Market sales',
            'admin.chart_sales_sub':'Monthly orders and revenue',
            'admin.chart_by_game': 'Sales by game',
            'admin.chart_by_game_sub':'Revenue distribution by franchise',
            'admin.chart_auctions_game':'Auctions by game',
            'admin.chart_auctions_game_sub':'Active vs ended by franchise',
            'admin.chart_activity_type':'Activity types',
            'admin.chart_activity_type_sub':'User action distribution',
            'admin.chart_daily':   'Daily activity',
            'admin.chart_daily_sub':'Registered actions (last 14 days)',
            'admin.chart_bids_hour':'Bid hours',
            'admin.chart_bids_hour_sub':'Hourly bid distribution in auctions',
            'admin.chart_auction_rev':'Auction revenue',
            'admin.chart_auction_rev_sub':'Winning bid value by month',
            'admin.rank_coins':    'Top Users by Lujanitos',
            'admin.rank_xp':       'Top Users by XP',
            'admin.top_cards':     'Top selling cards',
            'admin.registered':    'registered',
            'admin.no_users':      'No registered users',
            'admin.no_users_desc': 'Users will appear here when they register on the platform.',
            'admin.level':         'Level',
            'admin.role':          'Role',
            'admin.registered_date':'Registered',
            'admin.actions_col':   'Actions',
            'admin.no_results':    'No results',
            'admin.no_users_match':'No user matches your search.',
            'admin.recent_auctions':'Recent auctions',
            'admin.shown':         'shown',
            'admin.no_auctions':   'No auctions',
            'admin.no_auctions_desc':'Auctions created by users will appear here.',
            'admin.card':          'Card',
            'admin.current_bid':   'Current bid',
            'admin.seller':        'Seller',
            'admin.status':        'Status',
            'admin.end':           'End',
            'admin.action':        'Action',
            'admin.finished':      'Ended',
            'admin.no_auctions_match':'No auction matches your search.',
            'admin.global_activity':'Recent global activity',
            'admin.latest':        'latest',
            'admin.no_activity':   'No activity recorded',
            'admin.no_activity_desc':'User actions will appear here.',
            'admin.type':          'Type',
            'admin.title_col':     'Title',
            'admin.description':   'Description',
            'admin.date':          'Date',

            // ── Common ──
            'common.loading':      'Loading...',
            'common.error':        'Error',
            'common.success':      'Success',
            'common.confirm':      'Confirm',
            'common.cancel':       'Cancel',
            'common.save':         'Save Changes',
            'common.delete':       'Delete',
            'common.close':        'Close',
            'common.yes':          'Yes',
            'common.no':           'No',
            'common.rarity.common':    'Common',
            'common.rarity.rare':      'Rare',
            'common.rarity.epic':      'Epic',
            'common.rarity.legendary': 'Legendary',

            // ── Modal / Card detail ──
            'modal.add_fav':       '⭐ Add to favorites',
            'modal.remove_fav':    '⭐ Remove from favorites',
            'modal.price_history': '📈 Price History (30 days)',
            'modal.sellers':       '🛒 Seller Offers',
            'modal.seller':        'Seller',
            'modal.condition':     'Condition',
            'modal.price':         'Price',
            'modal.action':        'Action',
            'modal.add_best':      'Add best offer to Cart',
            'modal.related':       'Related Cards',
            'modal.price_alert':   '🔔 Price alert',
            'modal.alert_desc':    'We will notify you when it appears in auction at or below your target price.',
            'modal.target_price':  'Target price (LJ)',
            'modal.create_alert':  'Create alert',

            // ── Cart extras ──
            'cart.continue':       'Continue shopping',
            'cart.clear':          'Clear',
            'cart.empty_sub':      'Explore the marketplace and add your first card.',
            'cart.explore':        'Explore cards',
            'cart.update':         'Update',
            'cart.summary':        'Summary',
            'cart.your_balance':   'Your balance',
            'cart.total_pay':      'Total to pay',
            'cart.insufficient':   '⚠️ Insufficient balance.',
            'cart.get_lj':         'Get Lujanitos →',
            'cart.finish':         'Complete purchase',
            'cart.note':           'Lujanitos are deducted from your balance. 1€ = 1 LJ.',

            // ── Market extras ──
            'market.apply':        'Apply Filters',
            'market.clear':        'Clear',
            'market.condition':    'Condition',
            'market.search_card':  'Search card:',
            'market.catalog':      'Full catalog with all expansions.',
            'market.load_more':    'Load more cards',
            'market.load_catalog': 'See full catalog & filters',

            // ── Auctions extras ──
            'auctions.active_tab': '⚡ Active',
            'auctions.ended_tab':  '🏁 Ended',
            'auctions.all':        'All',
            'auctions.sell':       '📤 Sell',
            'auctions.sell_title': '📤 List card for auction',
            'auctions.sell_desc':  'Choose the card, set the starting price and how long the auction will last. All users will be able to bid.',
            'auctions.card_name':  'Card name *',
            'auctions.card_image': 'Card image',
            'auctions.game':       'Game *',
            'auctions.base_price': 'Base price (Lujanitos) *',
            'auctions.duration':   'Duration',
            'auctions.preview':    'Preview',
            'auctions.publish':    '🚀 Publish auction',
            'auctions.gallery':    '📁 Gallery',
            'auctions.login_bid':  'Log in to bid',
            'auctions.hour':        'hour',
            'auctions.hours':       'hours',
            'auctions.days':        'days',
            'auctions.hero_subtitle': 'Bid in real time with Lujanitos on TCG cards.',

            // ── Lujanitos extras ──
            'lj.your_balance':     'Your balance:',
            'lj.info_row1':        "Loot&Trading's official currency. Use them to bid on auctions and buy cards in the market.",
            'lj.info_row2':        'When you register you get <strong>1,000 welcome LJ</strong> for free.',
            'lj.info_row3':        'Fixed rate: <strong>1 € = 1 Lujanito</strong>. What you see is what you pay.',
            'lj.info_row4':        'Lujanitos are added instantly to your balance after confirmation.',
            'lj.info_row5':        'Simulated purchase — no real charges are made.',

            // ── Friends extras ──
            'friends.search_btn':  'Search',
            'friends.view_profile':'View profile',
            'friends.no_friends':  "You don't have any friends yet.",

            // ── My Cards ──
            'mycards.title':         '📦 My Cards',
            'mycards.all_games':     'All games',
            'mycards.shipping_status':'Shipping status',
            'mycards.refresh_note':  'Status updates every 5 min.',
            'mycards.no_cards':      "You don't have any cards yet",
            'mycards.no_cards_desc': 'Buy in the market or win an auction to see them here.',
            'mycards.purchase':      '🛒 Purchase',
            'mycards.auction_win':   '🏆 Auction',
            'mycards.received':      'Received',
            'mycards.preparing':     'Preparing',
            'mycards.shipped':       'Shipped',
            'mycards.delivered':     'Delivered',

            // ── Profile ──
            'profile.title':         'My Profile',
            'profile.cards':         'Cards',
            'profile.collections':   'Collections',
            'profile.bids':          'Bids',
            'profile.personal_info': '📋 Personal Information',
            'profile.name':          'Name',
            'profile.username':      'Username',
            'profile.email':         'Email',
            'profile.member_since':  'Member since',
            'profile.address':       'Address',
            'profile.edit':          'Edit Profile',
            'profile.recent_activity':'🎯 Recent Activity',
            'profile.won_auctions':  '🏆 Won Auctions',
            'profile.favorites':     '⭐ Favorites',
            'profile.orders':        '📦 My Orders',
            'profile.achievements':  '🏅 Achievements',
            'profile.price_alerts':  '🔔 Price Alerts',
            'profile.no_activity':   "You don't have any activity yet",
            'profile.no_activity_desc':'Start exploring the card catalog',
            'profile.no_auctions':   "You haven't won any auction yet",
            'profile.explore_auctions':'Explore auctions',
            'profile.no_orders':     "You haven't placed any orders yet",
            'profile.explore_market': 'Explore the market',
            'profile.no_favorites':  "You don't have favorite cards",
            'profile.no_favorites_desc':'Add cards to your favorites to see them here',
            'profile.no_alerts':     'No alerts configured',
            'profile.no_alerts_desc': 'Create alerts from the Auctions page to receive notifications when a card appears at your target price',
            'profile.locked':        'Locked',
            'profile.collection_badges':'🏅 Collection Badges',
            'profile.cards_collected':'cards',
            'profile.cards_short':   'cards',
            'profile.total_collection':'Total Collection',

            // ── Language ──
            'lang.es':             'ES',
            'lang.en':             'EN',
            'lang.label':          'Language',
        }
    };

    // ── Get / set current language ──
    function getLang() {
        return localStorage.getItem('lt_lang') || 'es';
    }
    function setLang(lang) {
        if (!TRANSLATIONS[lang]) return;
        localStorage.setItem('lt_lang', lang);
        applyTranslations();
        document.documentElement.lang = lang;
        // Update selector display
        const sel = document.getElementById('lang-current');
        if (sel) sel.textContent = lang.toUpperCase();
    }

    // ── Translate helper ──
    function t(key) {
        const lang = getLang();
        return (TRANSLATIONS[lang] && TRANSLATIONS[lang][key]) || (TRANSLATIONS['es'] && TRANSLATIONS['es'][key]) || key;
    }

    // ── Apply translations to DOM ──
    function applyTranslations() {
        // data-i18n → textContent
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            const val = t(key);
            // If element contains HTML tags in translation, use innerHTML
            if (val.includes('<strong>') || val.includes('<br>') || val.includes('<a ')) {
                el.innerHTML = val;
            } else {
                el.textContent = val;
            }
        });
        // data-i18n-placeholder → placeholder
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            el.placeholder = t(el.getAttribute('data-i18n-placeholder'));
        });
        // data-i18n-title → title
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            el.title = t(el.getAttribute('data-i18n-title'));
        });
        // data-i18n-html → innerHTML
        document.querySelectorAll('[data-i18n-html]').forEach(el => {
            el.innerHTML = t(el.getAttribute('data-i18n-html'));
        });
    }

    // ── Expose globally ──
    window.I18N = {
        t: t,
        getLang: getLang,
        setLang: setLang,
        apply: applyTranslations
    };

    // ── Auto-apply on load ──
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            applyTranslations();
        });
    } else {
        applyTranslations();
    }

})();
