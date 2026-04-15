<style>
.site-footer {
    position: relative;
    background: linear-gradient(180deg, var(--bg-main) 0%, rgba(15,23,42,0.06) 100%);
    border-top: none;
    padding: 60px 24px 32px;
    margin-top: 80px;
    overflow: hidden;
    color: var(--text-secondary);
}
body.dark .site-footer {
    background: linear-gradient(180deg, var(--bg-main) 0%, rgba(8,12,26,0.95) 100%);
}
.site-footer::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(59,130,246,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(59,130,246,0.04) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    mask-image: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.5) 40%, rgba(0,0,0,0.5) 100%);
    -webkit-mask-image: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.5) 40%, rgba(0,0,0,0.5) 100%);
}
.footer-inner {
    position: relative;
    z-index: 1;
    max-width: 1000px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 28px;
}
.footer-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.3rem;
    font-weight: 900;
    letter-spacing: -0.5px;
}
.footer-brand-text {
    background: linear-gradient(135deg, #60a5fa 0%, #06b6d4 50%, #a78bfa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.footer-brand img { height: 32px; width: auto; opacity: 0.9; }
.footer-links {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    justify-content: center;
}
.footer-links a {
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 700;
    transition: all 0.25s;
    padding: 6px 14px;
    border-radius: 8px;
}
.footer-links a:hover { color: var(--accent-blue); background: rgba(59,130,246,0.06); }
body.dark .footer-links a { color: #94a3b8; }
body.dark .footer-links a:hover { color: #60a5fa; background: rgba(96,165,250,0.08); }
.footer-divider {
    width: 100%;
    max-width: 400px;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(59,130,246,0.15), transparent);
}
.footer-copy {
    font-size: 0.8rem;
    color: var(--text-secondary);
    text-align: center;
    line-height: 1.5;
    opacity: 0.7;
}
.footer-copy span { font-weight: 700; }
@media(max-width: 1024px) {
    .site-footer { padding-bottom: 80px; }
}
</style>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="../img/lujanito.svg" alt="Loot&Trading">
            <span class="footer-brand-text">Loot&Trading</span>
        </div>
        <div class="footer-links">
            <a href="index.php">Inicio</a>
            <a href="mercado.php">Mercado</a>
            <a href="apuestas.php">Subastas</a>
            <a href="amigos.php">Amigos</a>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-copy">
            &copy; <?php echo date('Y'); ?> <span>Loot&Trading</span> &mdash; Marketplace de cartas coleccionables
        </div>
    </div>
</footer>
