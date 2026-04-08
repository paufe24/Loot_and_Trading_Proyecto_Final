// ===== SUPABASE CONFIG =====
const SUPABASE_URL = 'https://twnpxipxtmgdohjckbjn.supabase.co';
const SUPABASE_KEY = 'sb_publishable_lu1sQo0v_aKaXvNQCh6SOw_ENj5JTPu';
const TCG_IDS = {
    pokemon:  '21899972-d4ce-41e6-aeac-70d7a198d9ed',
    magic:    'b292bb55-9b18-4438-a134-1cc049e19867',
    yugioh:   '14d81db0-0e72-40da-a764-633d63b9a008',
    onepiece: '3e7c1156-bfdd-4583-98fa-6564a8ea5f35'
};

async function fetchSupabaseImages(names, game) {
    if (!names.length) return {};
    const tcgId = TCG_IDS[game];
    if (!tcgId) return {};
    try {
        const quoted = names.map(n => `"${n.replace(/"/g, '\\"')}"`).join(',');
        const url = `${SUPABASE_URL}/rest/v1/cards?select=name,image_large,image_small&tcg_id=eq.${tcgId}&name=in.(${encodeURIComponent(quoted)})`;
        const res = await fetch(url, {
            headers: {
                'apikey': SUPABASE_KEY,
                'Authorization': `Bearer ${SUPABASE_KEY}`
            }
        });
        if (!res.ok) return {};
        const data = await res.json();
        const map = {};
        data.forEach(c => { map[c.name] = c.image_large || c.image_small; });
        return map;
    } catch (e) {
        return {};
    }
}

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

let ONEPIECE_CACHE = null;
let priceChartInstance = null;

function badgeToGame(badge) {
    if (badge === 'Pokémon') return 'pokemon';
    if (badge === 'Yu-Gi-Oh!') return 'yugioh';
    if (badge === 'Magic') return 'magic';
    if (badge === 'One Piece') return 'onepiece';
    return null;
}

async function loadPriceChart(cardName, game) {
    const canvas = document.getElementById('price-chart');
    const emptyMsg = document.getElementById('price-chart-empty');
    if (!canvas) return;

    if (priceChartInstance) { priceChartInstance.destroy(); priceChartInstance = null; }
    canvas.style.display = 'none';
    if (emptyMsg) emptyMsg.style.display = 'none';

    const tcgId = TCG_IDS[game];
    if (!tcgId) { if (emptyMsg) emptyMsg.style.display = 'block'; return; }

    try {
        // Find card_id in Supabase
        const quoted = `"${cardName.replace(/"/g, '\\"')}"`;
        const cardUrl = `${SUPABASE_URL}/rest/v1/cards?select=id&tcg_id=eq.${tcgId}&name=in.(${encodeURIComponent(quoted)})&limit=1`;
        const cardRes = await fetch(cardUrl, {
            headers: { 'apikey': SUPABASE_KEY, 'Authorization': `Bearer ${SUPABASE_KEY}` }
        });
        const cards = await cardRes.json();
        if (!cards.length) { if (emptyMsg) emptyMsg.style.display = 'block'; return; }

        const cardId = cards[0].id;
        const since = new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0];
        const histUrl = `${SUPABASE_URL}/rest/v1/price_history?select=avg_price,min_price,max_price,recorded_at&card_id=eq.${cardId}&recorded_at=gte.${since}&order=recorded_at.asc`;
        const histRes = await fetch(histUrl, {
            headers: { 'apikey': SUPABASE_KEY, 'Authorization': `Bearer ${SUPABASE_KEY}` }
        });
        const history = await histRes.json();

        if (!history.length) { if (emptyMsg) emptyMsg.style.display = 'block'; return; }

        canvas.style.display = 'block';
        const labels = history.map(h => {
            const d = new Date(h.recorded_at);
            return `${d.getDate()}/${d.getMonth() + 1}`;
        });
        const avgData = history.map(h => h.avg_price);
        const minData = history.map(h => h.min_price);
        const maxData = history.map(h => h.max_price);

        const isDark = document.body.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
        const textColor = isDark ? '#ccc' : '#666';

        priceChartInstance = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Precio medio',
                        data: avgData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                        borderWidth: 2
                    },
                    {
                        label: 'Mínimo',
                        data: minData,
                        borderColor: '#22c55e',
                        borderDash: [4, 4],
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 1
                    },
                    {
                        label: 'Máximo',
                        data: maxData,
                        borderColor: '#ef4444',
                        borderDash: [4, 4],
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: textColor, boxWidth: 12 } },
                    tooltip: {
                        callbacks: { label: ctx => `${ctx.dataset.label}: $${ctx.parsed.y.toFixed(2)}` }
                    }
                },
                scales: {
                    x: { ticks: { color: textColor, maxTicksLimit: 8 }, grid: { color: gridColor } },
                    y: {
                        ticks: { color: textColor, callback: v => '$' + v.toFixed(2) },
                        grid: { color: gridColor }
                    }
                }
            }
        });
    } catch (e) {
        if (emptyMsg) emptyMsg.style.display = 'block';
    }
}

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
    document.getElementById('modal-img').src = data.img;
    document.getElementById('modal-title').textContent = data.name;
    document.getElementById('modal-badge').textContent = data.badge;
    document.getElementById('modal-badge').style.backgroundColor = data.color;
    document.getElementById('modal-price').textContent = data.price ? '$' + data.price : 'Sin stock';

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

    const game = badgeToGame(data.badge);
    if (game) loadPriceChart(data.name, game);
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
        const names = details.map(c => c.name);
        const sbImages = await fetchSupabaseImages(names, 'pokemon');
        details.forEach(card => {
            const cmPrice = card.pricing?.cardmarket?.trend;
            const tcgPrice = card.pricing?.tcgplayer?.holofoil?.marketPrice;
            const realPrice = cmPrice || tcgPrice;
            grid.appendChild(createCardHTML({
                badge: 'Pokémon', color: '#eab308', name: card.name,
                img: sbImages[card.name] || (card.image + '/high.png'), price: realPrice ? parseFloat(realPrice).toFixed(2) : null
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
        const names = json.data.map(c => c.name);
        const sbImages = await fetchSupabaseImages(names, 'yugioh');
        json.data.forEach(card => {
            let realPrice = card.card_prices?.[0]?.tcgplayer_price;
            grid.appendChild(createCardHTML({
                badge: 'Yu-Gi-Oh!', color: '#a855f7', name: card.name,
                img: sbImages[card.name] || card.card_images?.[0]?.image_url, price: realPrice ? parseFloat(realPrice).toFixed(2) : null
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
        const names = items.map(c => c.name);
        const sbImages = await fetchSupabaseImages(names, 'magic');
        items.forEach(card => {
            let realPrice = card.prices?.usd || card.prices?.usd_foil;
            grid.appendChild(createCardHTML({
                badge: 'Magic', color: '#ef4444', name: card.name,
                img: sbImages[card.name] || card.image_uris.normal, price: realPrice ? parseFloat(realPrice).toFixed(2) : null
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

        const names = items.map(c => c.card_name);
        const sbImages = await fetchSupabaseImages(names, 'onepiece');
        items.forEach(card => {
            grid.appendChild(createCardHTML({
                badge: 'One Piece', color: '#f97316', name: card.card_name,
                img: sbImages[card.card_name] || card.card_image, price: card.market_price ? parseFloat(card.market_price).toFixed(2) : null
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
    const sbImages = await fetchSupabaseImages([detail.name], 'pokemon');
    return { badge: 'Pokémon', color: '#eab308', name: detail.name, img: sbImages[detail.name] || (detail.image + '/high.png'), price: price ? parseFloat(price).toFixed(2) : null };
}

async function fetchRandomYugioh() {
    const randomOffset = Math.floor(Math.random() * 5000);
    const res = await fetch(`https://db.ygoprodeck.com/api/v7/cardinfo.php?num=1&offset=${randomOffset}`);
    const json = await res.json();
    const card = json.data[0];
    const price = card.card_prices?.[0]?.tcgplayer_price;
    const sbImages = await fetchSupabaseImages([card.name], 'yugioh');
    return { badge: 'Yu-Gi-Oh!', color: '#a855f7', name: card.name, img: sbImages[card.name] || card.card_images?.[0]?.image_url, price: price ? parseFloat(price).toFixed(2) : null };
}

async function fetchRandomMagic() {
    const randomPage = Math.floor(Math.random() * 50) + 1;
    const res = await fetch(`https://api.scryfall.com/cards/random`);
    const card = await res.json();
    const img = card.image_uris?.normal || card.card_faces?.[0]?.image_uris?.normal;
    if (!img) return null;
    const price = card.prices?.usd || card.prices?.usd_foil;
    const sbImages = await fetchSupabaseImages([card.name], 'magic');
    return { badge: 'Magic', color: '#ef4444', name: card.name, img: sbImages[card.name] || img, price: price ? parseFloat(price).toFixed(2) : null };
}

async function fetchRandomOnePiece() {
    if (!ONEPIECE_CACHE) {
        const res = await fetch('https://www.optcgapi.com/api/allSetCards/');
        if (!res.ok) return null;
        const all = await res.json();
        ONEPIECE_CACHE = all.filter(c => c.card_image);
    }
    const pick = ONEPIECE_CACHE[Math.floor(Math.random() * ONEPIECE_CACHE.length)];
    const sbImages = await fetchSupabaseImages([pick.card_name], 'onepiece');
    return { badge: 'One Piece', color: '#f97316', name: pick.card_name, img: sbImages[pick.card_name] || pick.card_image, price: pick.market_price ? parseFloat(pick.market_price).toFixed(2) : null };
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