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
        const res = await fetch(`favorites.php?action=status&card_id=${encodeURIComponent(cardId)}`, {
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

        const res = await fetch('favorites.php', {
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

        const res = await fetch('cart.php', {
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
            <div class="card-price">${data.price ? '$' + data.price : 'Sin stock'}</div>
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
    document.getElementById('modal-price').textContent = data.price ? '$' + data.price : 'Sin stock';

    refreshFavoriteStatus(MODAL_CARD.card_id);

    const list = document.getElementById('market-list');
    list.innerHTML = '';

    let basePrice = parseFloat(data.price) || 25.00;

    const sellers = [
        { user: "TitanCards_Pro", cond: "Mint", tag: "cond-mint", mult: 1.15 },
        { user: "LootMaster99", cond: "Near Mint", tag: "cond-near", mult: 1.0 },
        { user: "BargainTCG", cond: "Played", tag: "cond-played", mult: 0.75 }
    ];

    sellers.forEach(s => {
        const finalPrice = (basePrice * s.mult).toFixed(2);
        list.innerHTML += `
            <tr>
                <td> ${s.user}</td>
                <td><span class="tag-condition ${s.tag}">${s.cond}</span></td>
                <td style="color: #16a34a;">$${finalPrice}</td>
                <td>
                    <button
                        class="btn-buy-small js-add-to-cart"
                        data-card-id="${encodeURIComponent(data.card_id || data.id || data.name)}"
                        data-card-name="${encodeURIComponent(data.name)}"
                        data-card-image="${encodeURIComponent(data.img)}"
                        data-card-price="${encodeURIComponent(finalPrice)}"
                        data-card-game="${encodeURIComponent(data.badge)}"
                        data-condition="${encodeURIComponent(s.cond)}"
                        data-seller="${encodeURIComponent(s.user)}"
                    >Comprar</button>
                </td>
            </tr>
        `;
    });

    document.getElementById('card-modal').style.display = 'flex';
    setTimeout(() => { document.getElementById('card-modal').classList.add('open'); }, 10);
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
            let realPrice = card.card_prices?.[0]?.tcgplayer_price;
            grid.appendChild(createCardHTML({
                badge: 'Yu-Gi-Oh!', color: '#a855f7', name: card.name,
                img: card.card_images?.[0]?.image_url, price: realPrice ? parseFloat(realPrice).toFixed(2) : null
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

async function loadOnePieceCards(gridId) {
    if (STATE.onepiece.loading) return;
    STATE.onepiece.loading = true;
    const grid = document.getElementById(gridId);

    try {
        if (!ONEPIECE_CACHE) {
            const res = await fetch('https://www.optcgapi.com/api/allSetCards/');
            if (!res.ok) throw new Error('OPTCG API error');
            const all = await res.json();
            ONEPIECE_CACHE = all.filter(c => c.card_image).sort((a, b) => (b.market_price || 0) - (a.market_price || 0));
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
                badge: 'One Piece', color: '#f97316', name: card.card_name,
                img: card.card_image, price: card.market_price ? parseFloat(card.market_price).toFixed(2) : null
            }));
        });
        STATE.onepiece.page++;
    } catch (err) {}
    STATE.onepiece.loading = false;
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
    const price = card.card_prices?.[0]?.tcgplayer_price;
    return { badge: 'Yu-Gi-Oh!', color: '#a855f7', name: card.name, img: card.card_images?.[0]?.image_url, price: price ? parseFloat(price).toFixed(2) : null };
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

async function fetchRandomOnePiece() {
    if (!ONEPIECE_CACHE) {
        const res = await fetch('https://www.optcgapi.com/api/allSetCards/');
        if (!res.ok) return null;
        const all = await res.json();
        ONEPIECE_CACHE = all.filter(c => c.card_image);
    }
    const pick = ONEPIECE_CACHE[Math.floor(Math.random() * ONEPIECE_CACHE.length)];
    return { badge: 'One Piece', color: '#f97316', name: pick.card_name, img: pick.card_image, price: pick.market_price ? parseFloat(pick.market_price).toFixed(2) : null };
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

    // Filtro de búsqueda en mercado
    const filterSearch = document.getElementById('filter-search');
    if (filterSearch) {
        filterSearch.addEventListener('input', () => {
            const query = filterSearch.value.toLowerCase().trim();
            document.querySelectorAll('#mercado-grid .tcg-item').forEach(card => {
                const name = card.querySelector('.card-name')?.textContent.toLowerCase() || '';
                card.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }
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
});