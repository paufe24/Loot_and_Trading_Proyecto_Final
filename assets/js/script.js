function toggleDarkMode() {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
}

function showToast(message, type = 'info') {
    const container = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    toast.innerHTML = `<span><span>${icon}</span><span>${message}</span></span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

let MODAL_CARD = null;

function setFavButtonState(favorited) {
    const btn = document.getElementById('modal-toggle-fav');
    if (!btn) return;
    btn.textContent = favorited ? '🗑️ Eliminar de favoritos' : '⭐ Añadir a favoritos';
    btn.dataset.favorited = favorited ? '1' : '0';
    btn.classList.toggle('is-favorited', !!favorited);
}

async function refreshFavoriteStatus(cardId) {
    const btn = document.getElementById('modal-toggle-fav');
    if (!btn) return;
    if (!cardId) {
        setFavButtonState(false);
        btn.disabled = true;
        return;
    }

    btn.disabled = true;
    try {
        const res = await fetch(`../api/favorites.php?action=status&card_id=${encodeURIComponent(cardId)}`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' }
        });
        const data = await res.json();
        if (res.ok && data && data.ok) {
            setFavButtonState(!!data.favorited);
        } else {
            setFavButtonState(false);
        }
    } catch (e) {
        setFavButtonState(false);
    } finally {
        btn.disabled = false;
    }
}

async function toggleFavorite(card) {
    const btn = document.getElementById('modal-toggle-fav');
    if (!btn) return;
    if (!card || !card.card_id || !card.name || !card.img || !card.badge) {
        showToast('No se pudo guardar en favoritos', 'error');
        return;
    }

    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'toggle');
        fd.append('card_id', card.card_id);
        fd.append('card_name', card.name);
        fd.append('card_image', card.img);
        fd.append('card_game', card.badge);

        const res = await fetch('../api/favorites.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' },
            body: fd
        });
        const data = await res.json();

        if (res.ok && data && data.ok) {
            setFavButtonState(!!data.favorited);
            showToast(data.favorited ? 'Añadida a favoritos' : 'Eliminada de favoritos', 'success');
        } else {
            showToast((data && data.message) ? data.message : 'No se pudo actualizar favoritos', 'error');
        }
    } catch (e) {
        showToast('Error al actualizar favoritos', 'error');
    } finally {
        btn.disabled = false;
    }
}

async function addToCart(cardId, cardName, cardImage, cardPrice, cardGame, condition = 'Near Mint', seller = 'TCGVerse') {
    try {
        const fd = new FormData();
        fd.append('action', 'add');
        fd.append('card_id', cardId);
        fd.append('card_name', cardName);
        fd.append('card_image', cardImage);
        fd.append('card_price', cardPrice);
        fd.append('card_game', cardGame);
        fd.append('condition', condition);
        fd.append('seller', seller);

        const res = await fetch('../api/cart.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch' },
            body: fd
        });

        const raw = await res.text();
        let data = null;
        try { data = JSON.parse(raw); } catch (_) { data = null; }

        if (!data) {
            showToast('No se pudo añadir al carrito', 'error');
            return;
        }

        if (res.ok && data.ok) {
            const niceName = (cardName || '').trim();
            showToast(niceName ? `Añadido al carrito: ${niceName}` : (data.message || 'Añadido al carrito'), 'success');
            return;
        }

        showToast(data.message || 'No se pudo añadir al carrito', 'error');
    } catch (e) {
        showToast('Error al añadir al carrito', 'error');
    }
}

const STATE = {
    pokemon: { page: 1, loading: false },
    yugioh: { offset: 0, loading: false },
    magic: { page: 1, cache: [], loading: false },
    onepiece: { page: 0, loading: false }
};

const BACKUPS = {
    pokemon: 'https://upload.wikimedia.org/wikipedia/en/3/3b/Pokemon_Trading_Card_Game_cardback.jpg',
    yugioh: 'https://upload.wikimedia.org/wikipedia/en/2/2b/Yugioh_Card_Back.jpg',
    magic: 'https://upload.wikimedia.org/wikipedia/en/a/aa/Magic_the_gathering-card_back.jpg',
    onepiece: 'https://asia-en.onepiece-cardgame.com/images/common/back.jpg'
};

function createCardHTML(data) {
    const div = document.createElement('div');
    div.className = 'tcg-item';

    // Data attributes para filtros
    const price = parseFloat(data.price) || 0;
    div.dataset.game   = (data.badge || '').toLowerCase().replace(/[^a-z]/g, '');
    div.dataset.rarity = price > 50 ? 'ultra' : price > 10 ? 'rare' : 'common';
    const condRoll = Math.random();
    div.dataset.cond = condRoll < 0.05 ? 'gem-mint' : condRoll < 0.20 ? 'mint' : condRoll < 0.78 ? 'near-mint' : 'played';

    let backup = BACKUPS.pokemon;
    if (data.badge === 'Yu-Gi-Oh!') backup = BACKUPS.yugioh;
    if (data.badge === 'Magic') backup = BACKUPS.magic;
    if (data.badge === 'One Piece') backup = BACKUPS.onepiece;

    div.innerHTML = `
        <div class="tcg-bg">
            <img src="${data.img}" alt="${data.name}" onerror="this.onerror=null;this.src='${backup}'" loading="lazy">
        </div>
        <div class="scan-line"></div>
        <div class="tcg-info">
            <span class="card-badge" style="background-color: ${data.color}">${data.badge}</span>
            <div class="card-name">${data.name}</div>
            <div class="card-price">${(parseFloat(data.price) > 0) ? '$' + data.price : 'Sin precio'}</div>
        </div>
    `;

    div.addEventListener('click', () => openModal(data));
    return div;
}

function openModal(data) {
    const cardId = (data.card_id || data.id || data.name);
    MODAL_CARD = {
        card_id: (cardId || '').toString(),
        name: (data.name || '').toString(),
        img: (data.img || '').toString(),
        badge: (data.badge || '').toString()
    };

    document.getElementById('modal-img').src = data.img;
    document.getElementById('modal-title').textContent = data.name;
    document.getElementById('modal-badge').textContent = data.badge;
    document.getElementById('modal-badge').style.backgroundColor = data.color;
    document.getElementById('modal-price').textContent = (parseFloat(data.price) > 0) ? '$' + data.price : 'Sin precio';

    refreshFavoriteStatus(MODAL_CARD.card_id);

    // Sellers con condición 1-10
    const list = document.getElementById('market-list');
    list.innerHTML = '';
    const basePrice = parseFloat(data.price) || 25.00;

    const SELLER_POOL = ['TitanCards_Pro','LootMaster99','BargainTCG','CardKing_EU','MintCollector','RareDragon','AlphaCards','PokeVault','DuelZone','FoilHunter'];
    const COND_MAP = [
        {r:10, label:'Gem Mint',  mult:1.12, bg:'#ede9fe', color:'#7c3aed'},
        {r:9,  label:'Mint',      mult:1.00, bg:'#dcfce7', color:'#16a34a'},
        {r:8,  label:'Near Mint', mult:0.88, bg:'#bbf7d0', color:'#15803d'},
        {r:7,  label:'Near Mint', mult:0.78, bg:'#d1fae5', color:'#059669'},
        {r:6,  label:'Excellent', mult:0.65, bg:'#fef9c3', color:'#ca8a04'},
        {r:5,  label:'Good',      mult:0.54, bg:'#fef3c7', color:'#d97706'},
        {r:4,  label:'Played',    mult:0.42, bg:'#ffedd5', color:'#ea580c'},
        {r:3,  label:'Played',    mult:0.32, bg:'#fee2e2', color:'#dc2626'},
        {r:2,  label:'Poor',      mult:0.20, bg:'#fecaca', color:'#b91c1c'},
        {r:1,  label:'Poor',      mult:0.12, bg:'#fee2e2', color:'#991b1b'},
    ];

    // Elige 4-5 condiciones variadas (sesgadas hacia calidad media-alta)
    const weights = [0.05,0.15,0.20,0.18,0.14,0.11,0.08,0.05,0.03,0.01];
    const chosen = [];
    const shuffledSellers = [...SELLER_POOL].sort(() => Math.random() - 0.5);
    let attempts = 0;
    while (chosen.length < 5 && attempts < 50) {
        attempts++;
        const r = Math.random();
        let cum = 0;
        for (let i = 0; i < weights.length; i++) {
            cum += weights[i];
            if (r < cum) { chosen.push(i); break; }
        }
    }
    // Eliminar duplicados de condición
    const uniqueChosen = [...new Set(chosen)].slice(0,5);
    uniqueChosen.sort((a,b) => COND_MAP[a].r - COND_MAP[b].r); // orden asc condición

    const condFilter = window._selectedCondFilter || '';
    const condMatchFn = (c) => {
        if (!condFilter) return false;
        if (condFilter === 'gem-mint')  return c.r === 10;
        if (condFilter === 'mint')      return c.r === 9;
        if (condFilter === 'near-mint') return c.r === 7 || c.r === 8;
        if (condFilter === 'played')    return c.r <= 6;
        return false;
    };

    uniqueChosen.forEach((idx, i) => {
        const c = COND_MAP[idx];
        const noise = 0.96 + Math.random() * 0.08;
        const finalPrice = (basePrice * c.mult * noise).toFixed(2);
        const seller = shuffledSellers[i] || 'Seller' + i;
        const highlight = condMatchFn(c) ? ' style="outline:2px solid #3b82f6;outline-offset:-2px;border-radius:6px;"' : '';
        list.innerHTML += `
            <tr${highlight}>
                <td>${seller}</td>
                <td>
                    <span class="cond-rating" style="background:${c.bg};color:${c.color}">
                        ${c.r}/10 ${c.label}
                    </span>
                </td>
                <td style="color:#16a34a;font-weight:800;">$${finalPrice}</td>
                <td>
                    <button class="btn-buy-small js-add-to-cart"
                        data-card-id="${encodeURIComponent(data.card_id || data.id || data.name)}"
                        data-card-name="${encodeURIComponent(data.name)}"
                        data-card-image="${encodeURIComponent(data.img)}"
                        data-card-price="${encodeURIComponent(finalPrice)}"
                        data-card-game="${encodeURIComponent(data.badge)}"
                        data-condition="${encodeURIComponent(c.r + '/10 ' + c.label)}"
                        data-seller="${encodeURIComponent(seller)}"
                    >Comprar</button>
                </td>
            </tr>`;
    });

    document.getElementById('card-modal').style.display = 'flex';
    setTimeout(() => { document.getElementById('card-modal').classList.add('open'); }, 10);

    // Cargar historial de precios
    fetchPriceHistory(data);
}

let _priceChart = null;
async function fetchPriceHistory(data) {
    const badge = document.getElementById('price-chart-change');
    const canvas = document.getElementById('price-chart');
    if (!canvas) return;

    if (badge) { badge.textContent = ''; badge.className = 'price-change-badge'; }

    const price = parseFloat(data.price) || 0;
    if (price <= 0) {
        const ctx = canvas.getContext('2d');
        if (_priceChart) { _priceChart.destroy(); _priceChart = null; }
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        return;
    }

    try {
        const params = new URLSearchParams({
            action: 'price_history',
            card_id: data.card_id || data.name,
            game: (data.badge || '').toLowerCase().replace(/[^a-z]/g,''),
            price: price
        });
        const res = await fetch('../api/market.php?' + params);
        const json = await res.json();
        if (!json.ok || !json.prices?.length) return;

        const prices = json.prices;
        const labels = json.labels;
        const first = prices[0], last = prices[prices.length - 1];
        const pct = ((last - first) / first * 100).toFixed(1);
        const up = last >= first;

        if (badge) {
            badge.textContent = (up ? '▲ +' : '▼ ') + pct + '%';
            badge.className = 'price-change-badge ' + (up ? 'up' : 'down');
        }

        if (_priceChart) { _priceChart.destroy(); }
        const isDark = document.body.classList.contains('dark');
        const lineColor = up ? '#3b82f6' : '#ef4444';
        const fillColor = up ? 'rgba(59,130,246,0.12)' : 'rgba(239,68,68,0.12)';

        _priceChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels.map(l => l.slice(5)), // MM-DD
                datasets: [{
                    data: prices,
                    borderColor: lineColor,
                    backgroundColor: fillColor,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: {
                    callbacks: { label: ctx => '$' + ctx.parsed.y.toFixed(2) }
                }},
                scales: {
                    x: { ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 9 }, maxTicksLimit: 7 }, grid: { display: false } },
                    y: { ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 9 }, callback: v => '$'+v }, grid: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)' } }
                }
            }
        });
    } catch(e) { console.error('Price history error:', e); }
}

function closeModal() {
    document.getElementById('card-modal').classList.remove('open');
    setTimeout(() => { document.getElementById('card-modal').style.display = 'none'; }, 300);
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-add-to-cart');
    if (!btn) return;

    const cardId = decodeURIComponent(btn.dataset.cardId || '');
    const cardName = decodeURIComponent(btn.dataset.cardName || '');
    const cardImage = decodeURIComponent(btn.dataset.cardImage || '');
    const cardPrice = decodeURIComponent(btn.dataset.cardPrice || '');
    const cardGame = decodeURIComponent(btn.dataset.cardGame || '');
    const condition = decodeURIComponent(btn.dataset.condition || 'Near Mint');
    const seller = decodeURIComponent(btn.dataset.seller || 'TCGVerse');

    addToCart(cardId, cardName, cardImage, cardPrice, cardGame, condition, seller);
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest('#modal-add-best');
    if (!btn) return;

    const best = document.querySelector('#market-list .js-add-to-cart');
    if (!best) {
        showToast('No hay ofertas disponibles', 'error');
        return;
    }

    best.click();
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest('#modal-toggle-fav');
    if (!btn) return;
    toggleFavorite(MODAL_CARD);
});

async function loadPokemonCards(gridId) {
    if (STATE.pokemon.loading) return;
    STATE.pokemon.loading = true;
    const grid = document.getElementById(gridId);

    try {
        const listRes = await fetch(`https://api.tcgdex.net/v2/en/cards?pagination:page=${STATE.pokemon.page}&pagination:itemsPerPage=20`);
        if (!listRes.ok) throw new Error('TCGdex list error');
        const cards = await listRes.json();
        const withImage = cards.filter(c => c.image).slice(0, 12);
        const details = await Promise.all(
            withImage.map(c => fetch(`https://api.tcgdex.net/v2/en/cards/${c.id}`).then(r => r.json()))
        );
        details.forEach(card => {
            const cmPrice = card.pricing?.cardmarket?.trend;
            const tcgPrice = card.pricing?.tcgplayer?.holofoil?.marketPrice;
            const realPrice = cmPrice || tcgPrice;
            grid.appendChild(createCardHTML({
                badge: 'Pokémon', color: '#eab308', name: card.name,
                img: card.image + '/high.png', price: realPrice ? parseFloat(realPrice).toFixed(2) : null,
                card_id: card.id,
                id: card.id
            }));
        });
        STATE.pokemon.page++;
    } catch (err) {}
    STATE.pokemon.loading = false;
}

async function loadYugiohCards(gridId) {
    if (STATE.yugioh.loading) return;
    STATE.yugioh.loading = true;
    const grid = document.getElementById(gridId);
    
    try {
        const res = await fetch(`https://db.ygoprodeck.com/api/v7/cardinfo.php?num=12&offset=${STATE.yugioh.offset}&sort=price`);
        const json = await res.json();
        json.data.forEach(card => {
            const p = card.card_prices?.[0] || {};
            const realPrice = [p.tcgplayer_price, p.cardmarket_price, p.ebay_price, p.coolstuffinc_price]
                .map(v => parseFloat(v)).find(v => v > 0) || null;
            grid.appendChild(createCardHTML({
                badge: 'Yu-Gi-Oh!', color: '#a855f7', name: card.name,
                img: card.card_images?.[0]?.image_url, price: realPrice ? realPrice.toFixed(2) : null
            }));
        });
        STATE.yugioh.offset += 12;
    } catch (err) {}
    STATE.yugioh.loading = false;
}

async function loadMagicCards(gridId) {
    if (STATE.magic.loading) return;
    STATE.magic.loading = true;
    const grid = document.getElementById(gridId);
    
    try {
        if (STATE.magic.cache.length === 0) {
            const res = await fetch(`https://api.scryfall.com/cards/search?q=f:commander&order=usd&dir=desc&page=${STATE.magic.page}`);
            const json = await res.json();
            STATE.magic.cache = json.data.filter(c => c.image_uris && c.image_uris.normal);
            STATE.magic.page++;
        }
        const items = STATE.magic.cache.splice(0, 12);
        items.forEach(card => {
            let realPrice = card.prices?.usd || card.prices?.usd_foil;
            grid.appendChild(createCardHTML({
                badge: 'Magic', color: '#ef4444', name: card.name,
                img: card.image_uris.normal, price: realPrice ? parseFloat(realPrice).toFixed(2) : null
            }));
        });
    } catch (err) {}
    STATE.magic.loading = false;
}

// ===== CARTA DEL DÍA =====
async function fetchRandomPokemon() {
    const randomPage = Math.floor(Math.random() * 100) + 1;
    const res = await fetch(`https://api.tcgdex.net/v2/en/cards?pagination:page=${randomPage}&pagination:itemsPerPage=20`);
    const cards = await res.json();
    const withImg = cards.filter(c => c.image);
    if (withImg.length === 0) return null;
    const pick = withImg[Math.floor(Math.random() * withImg.length)];
    const detail = await fetch(`https://api.tcgdex.net/v2/en/cards/${pick.id}`).then(r => r.json());
    const price = detail.pricing?.cardmarket?.trend || detail.pricing?.tcgplayer?.holofoil?.marketPrice;
    return { badge: 'Pokémon', color: '#eab308', name: detail.name, img: detail.image + '/high.png', price: price ? parseFloat(price).toFixed(2) : null };
}

async function fetchRandomYugioh() {
    const randomOffset = Math.floor(Math.random() * 5000);
    const res = await fetch(`https://db.ygoprodeck.com/api/v7/cardinfo.php?num=1&offset=${randomOffset}`);
    const json = await res.json();
    const card = json.data[0];
    const p = card.card_prices?.[0] || {};
    const price = [p.tcgplayer_price, p.cardmarket_price, p.ebay_price, p.coolstuffinc_price]
        .map(v => parseFloat(v)).find(v => v > 0) || null;
    return { badge: 'Yu-Gi-Oh!', color: '#a855f7', name: card.name, img: card.card_images?.[0]?.image_url, price: price ? price.toFixed(2) : null };
}

async function fetchRandomMagic() {
    const randomPage = Math.floor(Math.random() * 50) + 1;
    const res = await fetch(`https://api.scryfall.com/cards/random`);
    const card = await res.json();
    const img = card.image_uris?.normal || card.card_faces?.[0]?.image_uris?.normal;
    if (!img) return null;
    const price = card.prices?.usd || card.prices?.usd_foil;
    return { badge: 'Magic', color: '#ef4444', name: card.name, img: img, price: price ? parseFloat(price).toFixed(2) : null };
}

function renderFeaturedCard(containerId, data) {
    const container = document.getElementById(containerId);
    if (!container || !data) return;
    container.classList.add('fade-out');
    setTimeout(() => {
        container.innerHTML = `
            <img class="featured-card-img" src="${data.img}" alt="${data.name}" onerror="this.style.display='none'">
            <div class="featured-card-body">
                <span class="featured-card-badge" style="background:${data.color}">${data.badge}</span>
                <div class="featured-card-name">${data.name}</div>
                <div class="featured-card-price">${data.price ? '$' + data.price : 'Sin stock'}</div>
            </div>
        `;
        container.style.cursor = 'pointer';
        container.onclick = () => openModal(data);
        container.classList.remove('fade-out');
        container.classList.add('fade-in');
    }, 400);
}

async function loadFeaturedCards() {
    const fetchers = [
        { id: 'featured-pokemon', fn: fetchRandomPokemon },
        { id: 'featured-yugioh', fn: fetchRandomYugioh },
        { id: 'featured-magic', fn: fetchRandomMagic },
        { id: 'featured-onepiece', fn: fetchRandomOnePiece }
    ];
    await Promise.all(fetchers.map(async ({ id, fn }) => {
        try {
            const data = await fn();
            if (data) renderFeaturedCard(id, data);
        } catch (e) {}
    }));
}

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const gameParam = urlParams.get('game');
    const openFav = urlParams.get('open_fav');
    
    if (document.getElementById('mercado-grid')) {
        const loadBtn = document.getElementById('mercado-load-more');
        const title = document.getElementById('mercado-title');
        
        if (gameParam === 'pokemon') {
            title.innerText = 'Mercado: Pokémon TCG';
            loadPokemonCards('mercado-grid');
            if(loadBtn) loadBtn.onclick = () => loadPokemonCards('mercado-grid');
        } else if (gameParam === 'yugioh') {
            title.innerText = 'Mercado: Yu-Gi-Oh!';
            loadYugiohCards('mercado-grid');
            if(loadBtn) loadBtn.onclick = () => loadYugiohCards('mercado-grid');
        } else if (gameParam === 'magic') {
            title.innerText = 'Mercado: Magic The Gathering';
            loadMagicCards('mercado-grid');
            if(loadBtn) loadBtn.onclick = () => loadMagicCards('mercado-grid');
        } else if (gameParam === 'onepiece') {
            title.innerText = 'Mercado: One Piece';
            loadOnePieceCards('mercado-grid');
            if(loadBtn) loadBtn.onclick = () => loadOnePieceCards('mercado-grid');
        }

        // Mostrar badge de condición activa
        const condParam2 = urlParams.get('cond');
        if (condParam2) {
            const condLabels = { 'gem-mint': 'Gem Mint', 'mint': 'Mint', 'near-mint': 'Near Mint', 'played': 'Played' };
            const condLabel = condLabels[condParam2] || condParam2;
            const subtitle = document.querySelector('.mercado-page .section-head p');
            if (subtitle) subtitle.innerHTML = `Catálogo completo con todas las expansiones. &nbsp;<span style="background:#dbeafe;color:#1d4ed8;font-weight:800;padding:3px 12px;border-radius:20px;font-size:0.8rem;">Estado: ${condLabel}</span>`;
        }

        if (openFav === '1') {
            const cardId = urlParams.get('card_id') || '';
            const cardName = urlParams.get('card_name') || '';
            const cardImage = urlParams.get('card_image') || '';
            const cardGame = urlParams.get('card_game') || (gameParam || '');

            const colorMap = {
                pokemon: '#eab308',
                yugioh: '#a855f7',
                magic: '#ef4444',
                onepiece: '#f97316'
            };

            if (cardName && cardImage) {
                openModal({
                    card_id: cardId || cardName,
                    id: cardId || cardName,
                    name: cardName,
                    img: cardImage,
                    badge: cardGame,
                    color: colorMap[gameParam] || '#3b82f6',
                    price: null
                });
            }
        }
    } 
    else if (document.getElementById('main-content')) {
        loadPokemonCards('pokemon-grid');
        loadYugiohCards('yugioh-grid');
        loadMagicCards('magic-grid');
        loadOnePieceCards('onepiece-grid');

        // Carta del Día: carga inicial + rotación cada 30s
        if (document.getElementById('featured-grid')) {
            loadFeaturedCards();
            setInterval(loadFeaturedCards, 30000);
        }
    }

    // Abrir modal de favorito si viene desde perfil
    const favDataRaw = urlParams.get('fav_data');
    if (openFav === '1' && favDataRaw) {
        try {
            const favData = JSON.parse(decodeURIComponent(favDataRaw));
            if (favData.card_id && favData.name && favData.img && favData.badge) {
                // Abrir modal con los datos de la carta favorita
                setTimeout(() => openModal(favData), 400);
            }
        } catch (e) {}
    }

    const modal = document.getElementById('card-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    }
    
    const closeBtn = document.getElementById('close-modal-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    // ===== FILTROS MERCADO EN TIEMPO REAL =====
    function applyMercadoFilters() {
        const q = (document.getElementById('filter-search')?.value || '').toLowerCase().trim();
        const conds   = [...document.querySelectorAll('#cond-gem-mint,#cond-mint,#cond-near-mint,#cond-played')].filter(c=>c.checked).map(c=>c.value);
        const rarities= [...document.querySelectorAll('#rar-common,#rar-rare,#rar-ultra')].filter(c=>c.checked).map(c=>c.value);
        window._selectedCondFilter = conds[0] || '';
        document.querySelectorAll('#mercado-grid .tcg-item').forEach(card => {
            const name = (card.querySelector('.card-name')?.textContent||'').toLowerCase();
            let show = true;
            if (q && !name.includes(q)) show = false;
            if (conds.length    && !conds.includes(card.dataset.cond))     show = false;
            if (rarities.length && !rarities.includes(card.dataset.rarity)) show = false;
            card.style.display = show ? '' : 'none';
        });
    }

    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    if (applyFiltersBtn) {
        const condParam3 = urlParams.get('cond');
        if (condParam3) { const cb = document.getElementById('cond-' + condParam3); if (cb) cb.checked = true; }
        applyFiltersBtn.addEventListener('click', applyMercadoFilters);
    }

    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            document.querySelectorAll('#cond-gem-mint,#cond-mint,#cond-near-mint,#cond-played,#rar-common,#rar-rare,#rar-ultra').forEach(cb => cb.checked = false);
            const fs = document.getElementById('filter-search'); if (fs) fs.value = '';
            document.querySelectorAll('#mercado-grid .tcg-item').forEach(c => c.style.display = '');
            window._selectedCondFilter = '';
        });
    }

    const filterSearch = document.getElementById('filter-search');
    if (filterSearch) {
        const qParam = urlParams.get('q');
        if (qParam) { filterSearch.value = qParam; setTimeout(applyMercadoFilters, 1400); }
        filterSearch.addEventListener('input', applyMercadoFilters);
    }

    const condParam = urlParams.get('cond');
    if (condParam) window._selectedCondFilter = condParam;
});
// ==========================================================
// SISTEMA DE APUESTAS (TCG PREDICTOR) - AÑADIDO SIN ROMPER NADA
// ==========================================================
let currentUserBalance = parseInt(localStorage.getItem('lootcoins_balance')) || 1000;
let currentBets = JSON.parse(localStorage.getItem('lootcoins_bets')) || [];
let activeBetCard = null;
let activeBetType = null;
let activePredictorCards = [];

// Base de datos de cartas Premium para apostar
const PREDICTOR_POOL = [
    { id: 'p1', name: 'Umbreon VMAX (Alt Art)', game: 'Pokémon', badgeColor: '#eab308', img: 'https://images.pokemontcg.io/swsh7/215_hires.png', currentPrice: 950.00 },
    { id: 'p2', name: 'Charizard ex (SIR)', game: 'Pokémon', badgeColor: '#eab308', img: 'https://images.pokemontcg.io/sv3/223_hires.png', currentPrice: 110.50 },
    { id: 'p3', name: 'Lugia V (Alt Art)', game: 'Pokémon', badgeColor: '#eab308', img: 'https://images.pokemontcg.io/swsh12/186_hires.png', currentPrice: 180.00 },
    { id: 'y1', name: 'Blue-Eyes White Dragon', game: 'Yu-Gi-Oh!', badgeColor: '#a855f7', img: 'https://images.ygoprodeck.com/images/cards/89631139.jpg', currentPrice: 85.00 },
    { id: 'y2', name: 'Dark Magician Girl', game: 'Yu-Gi-Oh!', badgeColor: '#a855f7', img: 'https://images.ygoprodeck.com/images/cards/38033121.jpg', currentPrice: 120.00 },
    { id: 'y3', name: 'Red-Eyes Black Dragon', game: 'Yu-Gi-Oh!', badgeColor: '#a855f7', img: 'https://images.ygoprodeck.com/images/cards/74677422.jpg', currentPrice: 75.00 },
    { id: 'm1', name: 'Black Lotus', game: 'Magic', badgeColor: '#ef4444', img: 'https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg', currentPrice: 15000.00 },
    { id: 'm2', name: 'Mox Diamond', game: 'Magic', badgeColor: '#ef4444', img: 'https://cards.scryfall.io/large/front/0/b/0b6d2745-b46d-4959-b1d5-8d59174f89d3.jpg', currentPrice: 650.00 },
    { id: 'o1', name: 'Monkey D. Luffy (Manga)', game: 'One Piece', badgeColor: '#f97316', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-119.png', currentPrice: 2500.00 },
    { id: 'o2', name: 'Roronoa Zoro (Manga)', game: 'One Piece', badgeColor: '#f97316', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP06-118.png', currentPrice: 1100.00 }
];

function initArena() {
    const arenaGrid = document.getElementById('arena-grid');
    if (!arenaGrid) return; 

    const balanceEl = document.getElementById('user-balance');
    if (balanceEl) balanceEl.textContent = currentUserBalance;

    let shuffled = [...PREDICTOR_POOL].sort(() => 0.5 - Math.random());
    activePredictorCards = shuffled.slice(0, 4).map(card => ({
        ...card,
        expiresAt: Date.now() + (Math.floor(Math.random() * 45) + 15) * 1000
    }));

    renderArenaGrid();
    
    if (window.predictorInterval) clearInterval(window.predictorInterval);
    window.predictorInterval = setInterval(updateArenaTimers, 1000);

    renderMyBets();
}

function renderArenaGrid() {
    const arenaGrid = document.getElementById('arena-grid');
    if (!arenaGrid) return;
    arenaGrid.innerHTML = '';

    activePredictorCards.forEach((card, index) => {
        let timeLeft = Math.max(0, Math.floor((card.expiresAt - Date.now()) / 1000));
        let mins = Math.floor(timeLeft / 60).toString().padStart(2, '0');
        let secs = (timeLeft % 60).toString().padStart(2, '0');

        arenaGrid.innerHTML += `
            <div class="bet-item">
                <div class="timer-badge" id="timer-${index}">⏳ ${mins}:${secs}</div>
                <img src="${card.img}" alt="${card.name}" class="bet-item-img">
                <div class="bet-item-info">
                    <span class="card-badge" style="background-color: ${card.badgeColor}; width: fit-content; margin: 0 auto 10px; color: white;">${card.game}</span>
                    <div class="bet-item-title">${card.name}</div>
                    <div class="bet-item-price">$${card.currentPrice.toFixed(2)}</div>
                    <div class="bet-buttons">
                        <button class="btn-bull" onclick="openBetModal('${card.id}', 'bull')">Sube 📈</button>
                        <button class="btn-bear" onclick="openBetModal('${card.id}', 'bear')">Baja 📉</button>
                    </div>
                </div>
            </div>
        `;
    });
}

function updateArenaTimers() {
    let needsRender = false;
    const now = Date.now();

    activePredictorCards.forEach((card, index) => {
        let timeLeft = Math.floor((card.expiresAt - now) / 1000);

        if (timeLeft <= 0) {
            let available = PREDICTOR_POOL.filter(c => !activePredictorCards.find(ac => ac.id === c.id));
            if (available.length === 0) available = PREDICTOR_POOL; 
            
            let newCard = available[Math.floor(Math.random() * available.length)];
            activePredictorCards[index] = {
                ...newCard,
                expiresAt: now + (Math.floor(Math.random() * 45) + 30) * 1000
            };
            needsRender = true;
        } else {
            let mins = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            let secs = (timeLeft % 60).toString().padStart(2, '0');
            let timerEl = document.getElementById(`timer-${index}`);
            if (timerEl) {
                timerEl.innerText = `⏳ ${mins}:${secs}`;
                if(timeLeft <= 5) timerEl.style.color = '#ef4444'; 
            }
        }
    });

    if (needsRender) renderArenaGrid();
}

function openBetModal(cardId, type) {
    activeBetCard = PREDICTOR_POOL.find(c => c.id === cardId);
    activeBetType = type;

    const typeText = type === 'bull' ? 'subirá 📈' : 'bajará 📉';
    const typeColor = type === 'bull' ? '#10b981' : '#ef4444';

    document.getElementById('bet-modal-title').innerHTML = `Apostar a <span style="color:${typeColor}">${activeBetType === 'bull' ? 'ALZA' : 'BAJA'}</span>`;
    document.getElementById('bet-modal-desc').innerHTML = `¿Cuántas LootCoins apuestas a que <b>${activeBetCard.name}</b> ${typeText}?`;
    document.getElementById('bet-amount').value = '';

    const betModal = document.getElementById('bet-modal');
    if(betModal) {
        betModal.style.display = 'flex';
        setTimeout(() => { betModal.classList.add('open'); }, 10);
    }
}

function closeBetModal() {
    const betModal = document.getElementById('bet-modal');
    if(betModal) {
        betModal.classList.remove('open');
        setTimeout(() => { betModal.style.display = 'none'; }, 300);
    }
}

function renderMyBets() {
    const list = document.getElementById('my-bets-list');
    if (!list) return;

    list.innerHTML = '';
    if (currentBets.length === 0) {
        list.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 30px; color: var(--text-secondary);">Aún no has hecho ninguna predicción.</td></tr>`;
        return;
    }

    [...currentBets].reverse().forEach(bet => {
        const tagClass = bet.type === 'bull' ? 'tag-bull' : 'tag-bear';
        const tagText = bet.type === 'bull' ? 'SUBE 📈' : 'BAJA 📉';

        list.innerHTML += `
            <tr>
                <td><b>${bet.cardName}</b></td>
                <td><span class="${tagClass}">${tagText}</span></td>
                <td><b>${bet.amount}</b> 🪙</td>
                <td style="color: #10b981;"><b>+${bet.potentialWin.toFixed(0)}</b> 🪙</td>
                <td><span class="tag-pending">En espera (7d)</span></td>
            </tr>
        `;
    });
}

// Inicializador independiente solo para la Arena
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('arena-grid')) {
        initArena();
        
        const btnConfirm = document.getElementById('btn-confirm-bet');
        if (btnConfirm) {
            btnConfirm.addEventListener('click', () => {
                const amountInput = document.getElementById('bet-amount');
                const amount = parseInt(amountInput.value);

                if (isNaN(amount) || amount <= 0) { 
                    if(typeof showToast === 'function') showToast('Introduce una cantidad válida', 'error');
                    return; 
                }
                if (amount > currentUserBalance) { 
                    if(typeof showToast === 'function') showToast('No tienes suficientes LootCoins', 'error');
                    return; 
                }

                currentUserBalance -= amount;
                localStorage.setItem('lootcoins_balance', currentUserBalance);
                document.getElementById('user-balance').textContent = currentUserBalance;

                currentBets.push({
                    cardName: activeBetCard.name,
                    type: activeBetType,
                    amount: amount,
                    potentialWin: amount * 1.8
                });
                localStorage.setItem('lootcoins_bets', JSON.stringify(currentBets));

                closeBetModal();
                renderMyBets();
                if(typeof showToast === 'function') showToast(`Apuesta de ${amount}🪙 registrada con éxito`, 'success');
            });
        }
    }
    /* ========================================
   ONE PIECE - DATOS ESTÁTICOS (API caída)
   ======================================== */
const ONEPIECE_STATIC = [
    { cardname: 'Monkey D. Luffy', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-060.png', marketprice: '45.00' },
    { cardname: 'Shanks', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png', marketprice: '120.00' },
    { cardname: 'Roronoa Zoro', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png', marketprice: '30.00' },
    { cardname: 'Nico Robin', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png', marketprice: '18.00' },
    { cardname: 'Sanji', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png', marketprice: '22.00' },
    { cardname: 'Yamato', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png', marketprice: '55.00' },
    { cardname: 'Luffy Manga Alt', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-119.png', marketprice: '2500.00' },
    { cardname: 'Zoro Manga Alt', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP06-118.png', marketprice: '1100.00' },
    { cardname: 'Portgas D. Ace', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP02-013.png', marketprice: '65.00' },
    { cardname: 'Marco', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP02-016.png', marketprice: '25.00' },
    { cardname: 'Boa Hancock', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP03-040.png', marketprice: '35.00' },
    { cardname: 'Trafalgar Law', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP03-060.png', marketprice: '40.00' },
    { cardname: 'Whitebeard', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP02-004.png', marketprice: '90.00' },
    { cardname: 'Kaido', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP04-001.png', marketprice: '150.00' },
    { cardname: 'Dracule Mihawk', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP04-035.png', marketprice: '60.00' },
    { cardname: 'Jewelry Bonney', cardimage: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-088.png', marketprice: '20.00' }
];

async function loadOnePieceCards(gridId) {
    if (STATE.onepiece.loading) return;
    STATE.onepiece.loading = true;
    const grid = document.getElementById(gridId);
    try {
        if (!ONEPIECE_CACHE) {
            try {
                const res = await fetch('../api/api_proxy.php?game=onepiece');
                if (res.ok) {
                    const all = await res.json();
                    if (Array.isArray(all) && all.length > 0) {
                        // Normalizar campos de la API al mismo formato que ONEPIECE_STATIC
                        ONEPIECE_CACHE = all
                            .filter(c => c.card_image)
                            .sort((a, b) => (b.market_price || 0) - (a.market_price || 0))
                            .map(c => ({
                                cardname:    c.card_name,
                                cardimage:   c.card_image,
                                marketprice: c.market_price
                            }));
                    }
                }
            } catch(e) {}
            if (!ONEPIECE_CACHE) ONEPIECE_CACHE = ONEPIECE_STATIC;
        }
        const start = STATE.onepiece.page * 12;
        const items = ONEPIECE_CACHE.slice(start, start + 12);
        if (items.length === 0) {
            const btn = document.getElementById('mercado-load-more');
            if (btn) btn.style.display = 'none';
            STATE.onepiece.loading = false;
            return;
        }
        items.forEach(card => {
            grid.appendChild(createCardHTML({
                badge: 'One Piece', color: '#f97316',
                name: card.cardname, img: card.cardimage,
                price: (parseFloat(card.marketprice) > 0) ? parseFloat(card.marketprice).toFixed(2) : null,
                card_id: card.cardname.replace(/\s+/g, '-').toLowerCase()
            }));
        });
        STATE.onepiece.page++;
        STATE.onepiece.loading = false;
    } catch(err) { STATE.onepiece.loading = false; }
}

async function fetchRandomOnePiece() {
    const pool = ONEPIECE_CACHE || ONEPIECE_STATIC;
    const pick = pool[Math.floor(Math.random() * pool.length)];
    return { badge: 'One Piece', color: '#f97316', name: pick.cardname, img: pick.cardimage,
             price: (parseFloat(pick.marketprice) > 0) ? parseFloat(pick.marketprice).toFixed(2) : null };
}

/* ========================================
   CARTA DEL DÍA
   ======================================== */
const COD_CARD_POOL = [
    { name: 'Umbreon VMAX Alt Art', badge: 'Pokémon', color: '#e63329',
      img: 'https://images.pokemontcg.io/swsh7/215_hires.png', price: '$189.00', trend: '+18%', up: true,
      desc: 'Una de las cartas más buscadas de la era Sword & Shield. El arte alternativo de Umbreon VMAX es considerado el mejor de toda la generación.',
      card_id: 'swsh7-215', game: 'pokemon' },
    { name: 'Charizard Base Set', badge: 'Pokémon', color: '#e63329',
      img: 'https://images.pokemontcg.io/base1/4_hires.png', price: '$420.00', trend: '+5%', up: true,
      desc: 'La carta más icónica de la historia del TCG. Una copia en Near Mint sin gradear puede cambiar de manos por cientos de euros. La que empezó todo.',
      card_id: 'base1-4', game: 'pokemon' },
    { name: 'Blue-Eyes White Dragon', badge: 'Yu-Gi-Oh!', color: '#7c3aed',
      img: 'https://images.ygoprodeck.com/images/cards/89631139.jpg', price: '$85.00', trend: '+3%', up: true,
      desc: 'El dragón blanco de ojos azules. 3000 de ATK. La carta que definió una generación entera de jugadores. Las primeras ediciones valen una fortuna.',
      card_id: '89631139', game: 'yugioh' },
    { name: 'Black Lotus', badge: 'Magic', color: '#1d7b4e',
      img: 'https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg', price: '$15.000', trend: '+2%', up: true,
      desc: 'La carta más valiosa de Magic: The Gathering. Baneada en todos los formatos modernos. Una Alpha PSA 10 se vendió por 540.000$. Eso lo dice todo.',
      card_id: 'bd8fa327', game: 'magic' },
    { name: 'Luffy Manga Alt Art', badge: 'One Piece', color: '#f97316',
      img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-119.png', price: '$2.100', trend: '+31%', up: true,
      desc: 'La carta más buscada del One Piece TCG. El arte directo del manga de Eiichiro Oda en formato holo. Precio en subida constante desde su salida.',
      card_id: 'OP05-119', game: 'onepiece' },
    { name: 'Zoro Manga Alt Art', badge: 'One Piece', color: '#f97316',
      img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP06-118.png', price: '$980', trend: '+22%', up: true,
      desc: 'Roronoa Zoro con arte del manga original. Una de las cartas más escasas del One Piece TCG, especialmente en condición Near Mint o superior.',
      card_id: 'OP06-118', game: 'onepiece' },
];

const COD_OPINIONS = {
    pokemon: [
        { text: '"llevaba 2 años buscando esta exacta con buen centrado. la encontré aquí por 80€. casi lloro literal"', author: '— trap3r_tcg, coleccionista desde 2004' },
        { text: '"la vendí en 2018 por 40€ para pagar el móvil. ahora vale 600. no preguntéis cómo estoy"', author: '— cardflipper_, Bilbao' },
        { text: '"primer pull que hice en mi vida. la perdí de pequeño. 20 años después la recuperé aquí"', author: '— pikachu_irl, Madrid' },
        { text: '"la cotización subió un 40% el mes pasado y yo ya la tenía. sensación rarísima de haber acertado"', author: '— prof_juniper_real' },
        { text: '"mi versión favorita del set, el arte es diferente a todo lo demás de la época. no la suelto"', author: '— zoroark_tcg, Sevilla' },
    ],
    yugioh: [
        { text: '"este arte es el que tenía en mi cabeza desde que era crío. el anime, el manga, todo. esto ES Yu-Gi-Oh"', author: '— duelista_404, Barcelona' },
        { text: '"la compré como inversión y ahora no me atrevo a venderla. síndrome del coleccionista máximo"', author: '— yusei_real, Valencia' },
        { text: '"PSA 10 de esta carta tiene lista de espera de 6 meses. merece la pena esperar"', author: '— royal_decree, Zaragoza' },
        { text: '"mi padre me regaló una de estas en el 2002. la tengo enmarcada, no está en ningún mazo"', author: '— exodia_pls, Málaga' },
        { text: '"hay cartas raras y hay cartas con historia. esta es de las segundas"', author: '— trap3r_tcg' },
    ],
    magic: [
        { text: '"la primera vez que vi esta carta en una draft pensé que estaba rota. 15 años después sigo pensando lo mismo"', author: '— mox_hunter, MTG desde Tempest' },
        { text: '"la vendí para pagar el alquiler en 2015. fue en su momento la decisión correcta. me sigue doliendo"', author: '— fetchlands_, Lisboa' },
        { text: '"Legacy sin esto es otro juego. punto"', author: '— mtg_goblin, Berlin' },
        { text: '"la compré en un mercadillo por 3€ porque el vendedor no sabía lo que era. el mejor día de mi vida"', author: '— mtg_archaeologist' },
        { text: '"el arte original tiene algo que las reimpresiones no capturan. no es nostalgia, es real"', author: '— mox_hunter' },
    ],
    onepiece: [
        { text: '"Luffy Alt Art en foil es literalmente la carta más bonita que he tenido en las manos. no es discutible"', author: '— luffygang99, Madrid' },
        { text: '"el juego lleva 2 años y ya tiene cartas de 2000€. esto va a ser el Pokémon de la próxima generación"', author: '— ace_alive_fr, Paris' },
        { text: '"pillé la Shanks en pre-release sin saber el precio. fui el más feliz del torneo sin saberlo"', author: '— zoroark_tcg' },
        { text: '"la gradeé PSA 10 y ahora no sé si venderla o guardarla para el retiro"', author: '— luffygang99' },
        { text: '"tiene mejor arte que el 90% de cartas Pokémon con el doble de antigüedad"', author: '— ace_alive_fr' },
    ]
};

let _codCard = null;

function codOpenModal() {
    if (!_codCard) return;
    openModal({
        card_id: _codCard.card_id, id: _codCard.card_id,
        name: _codCard.name, img: _codCard.img,
        badge: _codCard.badge, color: _codCard.color,
        price: parseFloat((_codCard.price || '0').replace(/[^0-9.]/g, '')) || null
    });
}

(function initCardOfDay() {
    const todayKey = new Date().toISOString().slice(0, 10);
    const savedKey = localStorage.getItem('cod_date');
    let cardIndex = parseInt(localStorage.getItem('cod_index') || '0');
    if (savedKey !== todayKey) {
        cardIndex = Math.floor(Math.random() * COD_CARD_POOL.length);
        localStorage.setItem('cod_date', todayKey);
        localStorage.setItem('cod_index', cardIndex);
    }

    const card = COD_CARD_POOL[cardIndex % COD_CARD_POOL.length];
    _codCard = card;
    const opinions = COD_OPINIONS[card.game];

    const imgEl    = document.getElementById('cod-img');
    const nameEl   = document.getElementById('cod-name');
    const priceEl  = document.getElementById('cod-price');
    const trendEl  = document.getElementById('cod-trend');
    const descEl   = document.getElementById('cod-desc');
    const badgeEl  = document.getElementById('cod-badge');
    if (!imgEl) return;

    imgEl.src = card.img;
    nameEl.textContent = card.name;
    priceEl.textContent = card.price;
    trendEl.textContent = (card.up ? '↑ ' : '↓ ') + card.trend + ' esta semana';
    trendEl.className = 'cod-trend ' + (card.up ? 'up' : 'down');
    descEl.textContent = card.desc;
    badgeEl.textContent = card.badge;
    badgeEl.style.background = card.color;

    let opIdx = 0;
    const textEl   = document.getElementById('cod-opinion-text');
    const authorEl = document.getElementById('cod-opinion-author');
    const dotsEl   = document.getElementById('cod-dots');
    const box      = document.getElementById('cod-opinion-box');

    dotsEl.innerHTML = opinions.map((_, i) =>
        `<div class="cod-dot${i === 0 ? ' active' : ''}" data-i="${i}"></div>`
    ).join('');

    function showOpinion(i) {
        box.classList.add('fading');
        setTimeout(() => {
            textEl.textContent  = opinions[i].text;
            authorEl.textContent = opinions[i].author;
            dotsEl.querySelectorAll('.cod-dot').forEach((d, di) =>
                d.classList.toggle('active', di === i));
            box.classList.remove('fading');
        }, 400);
    }
    showOpinion(0);

    dotsEl.addEventListener('click', e => {
        const dot = e.target.closest('.cod-dot');
        if (!dot) return;
        opIdx = parseInt(dot.dataset.i);
        showOpinion(opIdx);
        clearInterval(opTimer);
        opTimer = setInterval(nextOp, 5000);
    });

    function nextOp() { opIdx = (opIdx + 1) % opinions.length; showOpinion(opIdx); }
    let opTimer = setInterval(nextOp, 5000);
})();

/* ========================================
   FLIP DEL DÍA
   ======================================== */
(function initFlip() {
    const FLIP_POOL = [
        { name: 'Umbreon VMAX Alt Art', badge: 'Pokémon', color: '#e63329',
          img: 'https://images.pokemontcg.io/swsh7/215_hires.png',
          price: '$189', trend: '+18%', up: true,
          desc: 'El arte alternativo más buscado de Sword & Shield. Difícil de encontrar en NM.',
          card_id: 'swsh7-215' },
        { name: 'Charizard Base Set', badge: 'Pokémon', color: '#e63329',
          img: 'https://images.pokemontcg.io/base1/4_hires.png',
          price: '$420', trend: '+5%', up: true,
          desc: 'La carta que empezó el coleccionismo masivo. Cualquier copia en buen estado vale dinero serio.',
          card_id: 'base1-4' },
        { name: 'Blue-Eyes White Dragon', badge: 'Yu-Gi-Oh!', color: '#7c3aed',
          img: 'https://images.ygoprodeck.com/images/cards/89631139.jpg',
          price: '$85', trend: '+3%', up: true,
          desc: 'Primera edición SDK. 3000 ATK. La carta que definió una generación entera de duelistas.',
          card_id: '89631139' },
        { name: 'Dark Magician Girl 1st Ed', badge: 'Yu-Gi-Oh!', color: '#7c3aed',
          img: 'https://images.ygoprodeck.com/images/cards/38033121.jpg',
          price: '$290', trend: '+12%', up: true,
          desc: 'Una de las cartas más icónicas del manga y el anime. Primera edición en buenas condiciones es cada vez más difícil.',
          card_id: '38033121' },
        { name: 'Black Lotus', badge: 'Magic', color: '#1d7b4e',
          img: 'https://cards.scryfall.io/large/front/b/d/bd8fa327-dd41-4737-8f19-2cf5eb1f7cdd.jpg',
          price: '$15.000', trend: '+2%', up: true,
          desc: 'La carta más cara y baneada de Magic. Una Alpha PSA 10 llegó a los 540.000$.',
          card_id: 'bd8fa327' },
        { name: 'Luffy Manga Alt Art', badge: 'One Piece', color: '#f97316',
          img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-119.png',
          price: '$2.100', trend: '+31%', up: true,
          desc: 'Arte directo del manga de Oda. La carta más buscada del One Piece TCG.',
          card_id: 'OP05-119' },
        { name: 'Shanks', badge: 'One Piece', color: '#f97316',
          img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png',
          price: '$120', trend: '+8%', up: true,
          desc: 'El personaje más querido del manga. Pull rate muy bajo en los boosters originales.',
          card_id: 'OP01-016' },
        { name: 'Underground Sea', badge: 'Magic', color: '#1d7b4e',
          img: 'https://cards.scryfall.io/large/front/2/3/2398892d-28e9-4009-81ec-0d544af79d2b.jpg',
          price: '$520', trend: '-2%', up: false,
          desc: 'La dual land más codiciada de Legacy. Cada copia en buenas condiciones lleva lista de espera.',
          card_id: '2398892d' },
    ];

    const todayKey = new Date().toISOString().slice(0, 10);
    const savedKey = localStorage.getItem('flip_date');
    let flipIndex  = parseInt(localStorage.getItem('flip_index') || '0');
    let alreadyFlipped = localStorage.getItem('flip_revealed') === todayKey;

    if (savedKey !== todayKey) {
        flipIndex = Math.floor(Math.random() * FLIP_POOL.length);
        localStorage.setItem('flip_date', todayKey);
        localStorage.setItem('flip_index', flipIndex);
        alreadyFlipped = false;
        localStorage.removeItem('flip_revealed');
    }

    const card = FLIP_POOL[flipIndex % FLIP_POOL.length];
    window._flipCard = card;

    if (alreadyFlipped) revealFlip(card, false);

    window.doFlip = function() {
        const scene = document.getElementById('flip-card-3d');
        if (!scene || scene.classList.contains('flipped')) return;
        scene.classList.add('flipped');
        localStorage.setItem('flip_revealed', todayKey);
        setTimeout(() => revealFlip(card, true), 400);
    };

    window.flipOpenModal = function() {
        if (!window._flipCard) return;
        const c = window._flipCard;
        openModal({
            card_id: c.card_id, id: c.card_id,
            name: c.name, img: c.img,
            badge: c.badge, color: c.color,
            price: parseFloat(c.price.replace(/[^0-9.]/g, '')) || null
        });
    };

    function revealFlip(c, animate) {
        const imgEl    = document.getElementById('flip-img');
        const badgeEl  = document.getElementById('flip-badge');
        const nameEl   = document.getElementById('flip-name');
        const priceEl  = document.getElementById('flip-price');
        const trendEl  = document.getElementById('flip-trend');
        const descEl   = document.getElementById('flip-desc');
        const hiddenEl = document.getElementById('flip-hidden-state');
        const revealEl = document.getElementById('flip-revealed-state');
        const scene    = document.getElementById('flip-card-3d');
        if (!imgEl) return;

        imgEl.src = c.img;
        badgeEl.textContent = c.badge;
        badgeEl.style.background = c.color;
        nameEl.textContent = c.name;
        priceEl.textContent = c.price;
        trendEl.textContent = (c.up ? '↑ ' : '↓ ') + c.trend + ' esta semana';
        trendEl.className = 'flip-trend ' + (c.up ? 'up' : 'down');
        descEl.textContent = c.desc;

        if (!animate && scene) scene.classList.add('flipped');
        setTimeout(() => {
            if (hiddenEl) hiddenEl.style.display = 'none';
            if (revealEl) revealEl.style.display = 'block';
        }, animate ? 500 : 0);
    }
})();

});