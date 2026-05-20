<?php
require_once dirname(__DIR__) . '/includes/session.php';
$nombre_usuario = isset($_SESSION['username']) ? $_SESSION['username'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot&Trading | Preguntas Frecuentes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <script>(function(){ if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark'); })();</script>

    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <div class="main-wrapper">
        <div class="faq-page">
            <!-- Header -->
            <div class="faq-header">
                <div class="faq-header-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 18h.01"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                </div>
                <h1 class="faq-title" data-i18n="faq.title">Preguntas Frecuentes</h1>
                <p class="faq-subtitle" data-i18n="faq.subtitle">Todo lo que necesitas saber sobre Loot&Trading, el marketplace de cartas coleccionables.</p>
            </div>

            <!-- Categoría: Plataforma General -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Plataforma General
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué es Loot&Trading?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Loot&Trading es un marketplace online especializado en cartas coleccionables de los juegos más populares: <strong>Pokémon TCG</strong>, <strong>Yu-Gi-Oh!</strong>, <strong>Magic: The Gathering</strong> y <strong>One Piece TCG</strong>. Ofrecemos precios en tiempo real, un sistema de subastas, gamificación con XP y Lujanitos, y herramientas sociales para conectar con otros coleccionistas.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Es gratis usar la plataforma?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí, registrarse y navegar por el catálogo de cartas es completamente gratuito. Al crear tu cuenta recibes <strong>1.000 Lujanitos</strong> de bienvenida que puedes usar para participar en subastas y comprar cartas en el mercado.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo me registro en Loot&Trading?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Haz clic en el botón <strong>"Iniciar sesión"</strong> en la barra de navegación y selecciona la opción de registro. Solo necesitas un nombre de usuario, tu email y una contraseña. Una vez registrado, ya puedes explorar el mercado, añadir cartas a favoritos y participar en subastas.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué juegos de cartas están disponibles?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Actualmente soportamos cuatro franquicias de TCG:</p>
                        <ul>
                            <li><strong>Pokémon TCG</strong> — Desde la Base Set hasta las últimas expansiones de Scarlet & Violet.</li>
                            <li><strong>Yu-Gi-Oh!</strong> — Cartas de todos los sets, incluyendo ediciones limitadas y cartas clásicas.</li>
                            <li><strong>Magic: The Gathering</strong> — Catálogo completo desde Alpha hasta las ediciones más recientes.</li>
                            <li><strong>One Piece TCG</strong> — Todas las expansiones del juego de cartas de One Piece.</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo funciona el modo oscuro?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Puedes activar o desactivar el modo oscuro haciendo clic en el icono de sol/luna en la barra de navegación. Tu preferencia se guarda automáticamente en el navegador, así que se mantendrá la próxima vez que visites la plataforma.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría: Cartas y Mercado -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z"/></svg>
                    Cartas y Mercado
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿De dónde vienen los precios de las cartas?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Los precios se obtienen en tiempo real a través de APIs oficiales de cada TCG. Utilizamos datos del mercado secundario para mostrar precios actualizados y tendencias de cada carta. Puedes ver el histórico de precios en la vista detallada de cada carta.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo busco una carta específica?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Accede al <strong>Mercado</strong> desde la barra de navegación seleccionando el juego que te interesa (Pokémon, Yu-Gi-Oh!, Magic u One Piece). Una vez dentro, usa la barra de búsqueda y los filtros del panel lateral para buscar por nombre, tipo, rareza, set o rango de precio.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué significan los estados de condición de las cartas?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Las cartas en el mercado pueden tener distintos estados de condición:</p>
                        <ul>
                            <li><strong>Mint (M)</strong> — Carta en perfecto estado, sin ningún defecto visible.</li>
                            <li><strong>Near Mint (NM)</strong> — Carta en excelente estado con desgaste mínimo o imperceptible.</li>
                            <li><strong>Played (PL)</strong> — Carta usada con desgaste visible pero que sigue siendo jugable.</li>
                        </ul>
                        <p>El estado afecta directamente al precio. Las cartas en estado Mint son las más valoradas.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo ver el historial de precios de una carta?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. Al hacer clic en cualquier carta del catálogo se abre un modal con información detallada que incluye un <strong>gráfico de precios de los últimos 30 días</strong>, la tendencia (subida o bajada) y las ofertas disponibles de distintos vendedores.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué es la "Carta del Día"?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Cada día se selecciona automáticamente una carta destacada que aparece en la página de inicio. Incluye información sobre su precio actual, tendencia y opiniones de la comunidad. Es una buena forma de descubrir cartas interesantes.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría: Compras y Carrito -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                    Compras y Carrito
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo añado una carta al carrito?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Haz clic en cualquier carta del mercado para ver sus detalles. Verás las ofertas de diferentes vendedores con precios y estados distintos. Puedes añadir una oferta específica al carrito con el botón <strong>"Comprar"</strong>, o usar <strong>"Añadir mejor oferta al Carrito"</strong> para añadir automáticamente la más barata.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo modificar las cantidades en el carrito?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. En la página del carrito puedes cambiar la cantidad de cada carta usando el campo numérico. También puedes eliminar artículos individuales o vaciar todo el carrito de una vez.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo se procesan los pedidos?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Al hacer checkout, se genera un pedido con el total en Lujanitos. El pedido pasa al equipo de envíos que gestiona la preparación y el envío de las cartas. Puedes seguir el estado de tus pedidos desde tu perfil.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué métodos de pago aceptan?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Actualmente las transacciones dentro de la plataforma se realizan con <strong>Lujanitos</strong>, la moneda virtual de Loot&Trading. Recibes Lujanitos al registrarte y puedes ganar más participando en actividades, subiendo de nivel y ganando logros.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría: Subastas -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Subastas
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo funcionan las subastas?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Las subastas permiten a los usuarios pujar por cartas durante un tiempo limitado. Cada subasta tiene un precio de salida y una fecha de finalización. Puedes pujar en cualquier momento mientras la subasta esté activa, siempre que tu puja sea mayor que la actual. Cuando finaliza el tiempo, el usuario con la puja más alta gana la carta.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo crear mis propias subastas?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. Desde la sección de <strong>Subastas</strong> puedes crear una nueva subasta seleccionando una carta de tu colección, estableciendo el precio de salida y la duración. Otros usuarios podrán pujar por tu carta y recibirás los Lujanitos cuando la subasta termine.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué pasa si gano una subasta?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Si eres el mayor postor cuando la subasta finaliza, se te deducen los Lujanitos de tu puja y la carta se añade a tu colección. Además, ganarás <strong>XP extra</strong> por haber ganado la subasta, lo que te ayuda a subir de nivel.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo cancelar una puja?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>No. Una vez que realizas una puja, es definitiva y no puede cancelarse. Asegúrate de que quieres pujar la cantidad indicada antes de confirmar. Si alguien puja más que tú, quedarás libre de la obligación de pago.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cuánto dura una subasta?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>La duración la decide el creador de la subasta. Puede ir desde unas pocas horas hasta varios días. El tiempo restante se muestra en cada tarjeta de subasta con una cuenta regresiva en vivo.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría: Gamificación y Lujanitos -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                    Gamificación y Lujanitos
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué son los Lujanitos?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p><strong>Lujanitos</strong> son la moneda virtual de la plataforma. Se usan para comprar cartas en el mercado y pujar en subastas. Al registrarte recibes 1.000 Lujanitos de bienvenida. Puedes ganar más completando logros, subiendo de nivel y participando activamente en la plataforma.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo funciona el sistema de XP y niveles?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Cada acción en la plataforma te otorga puntos de experiencia (XP):</p>
                        <ul>
                            <li><strong>Comprar cartas</strong> — Ganas XP por cada compra completada.</li>
                            <li><strong>Ganar subastas</strong> — XP extra por ser el postor ganador.</li>
                            <li><strong>Completar logros</strong> — Recompensas especiales de XP.</li>
                            <li><strong>Actividad general</strong> — Iniciar sesión, explorar el mercado, etc.</li>
                        </ul>
                        <p>A medida que acumulas XP subes de nivel, lo que desbloquea nuevas recompensas.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Qué son los logros?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Los logros son metas que puedes completar realizando acciones específicas en la plataforma. Por ejemplo, el logro <strong>"Bienvenido"</strong> se desbloquea al acceder por primera vez a tu perfil. Cada logro completado te otorga XP y Lujanitos adicionales. Puedes ver tus logros en tu página de perfil.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo ver mi ranking frente a otros usuarios?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. El panel de administración muestra rankings de los usuarios con más Lujanitos y más XP. Tu nivel y progreso también son visibles en tu perfil público, donde otros usuarios pueden ver tus estadísticas y logros.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría: Social y Amigos -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    Social y Amigos
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo añadir amigos en la plataforma?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. Desde la sección de <strong>Amigos</strong> puedes buscar usuarios por nombre y enviarles una solicitud de amistad. Una vez aceptada, podrás ver su perfil, sus cartas y su actividad reciente.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo ver la colección de otros usuarios?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. Si un usuario es tu amigo, puedes visitar su perfil y ver sus cartas, sus logros y estadísticas. Es una buena forma de descubrir cartas interesantes y comparar colecciones.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría: Envíos -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                    Envíos
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo se gestionan los envíos?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Una vez que completas un pedido, este pasa al equipo de gestión de envíos. Los operadores se encargan de preparar las cartas, empaquetar el pedido y gestionar la entrega. El estado del envío se actualiza en tiempo real.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Necesito tener dirección de envío configurada?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. Para poder recibir tus cartas necesitas configurar tu dirección de envío en tu <strong>perfil</strong>. Ve a tu perfil y añade tu dirección completa antes de realizar tu primer pedido.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría: Cuenta y Seguridad -->
            <div class="faq-category">
                <h2 class="faq-category-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    Cuenta y Seguridad
                </h2>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Cómo cambio mi contraseña?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Puedes cambiar tu contraseña accediendo a tu <strong>perfil</strong> y editando los datos de tu cuenta. Introduce tu contraseña actual y la nueva contraseña que desees para confirmar el cambio.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Mis datos están seguros?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. Las contraseñas se almacenan con encriptación segura (hash). Además, la plataforma utiliza tokens CSRF para proteger contra ataques de falsificación de solicitudes y sesiones seguras del lado del servidor.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <strong>¿Puedo personalizar mi avatar?</strong>
                        <svg class="faq-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="faq-answer">
                        <p>Sí. Desde tu perfil puedes subir un avatar personalizado que se mostrará en tu perfil público y junto a tus actividades. Si no subes uno, se mostrará un avatar por defecto.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>

    <script src="../assets/js/csrf.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>

    <script>
    function toggleFaq(btn) {
        const item = btn.closest('.faq-item');
        const wasOpen = item.classList.contains('open');

        // Close all items in the same category
        const category = item.closest('.faq-category');
        category.querySelectorAll('.faq-item.open').forEach(function(openItem) {
            openItem.classList.remove('open');
        });

        // Toggle the clicked one
        if (!wasOpen) {
            item.classList.add('open');
        }
    }

    function toggleDarkMode() {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    }
    </script>
</body>
</html>
