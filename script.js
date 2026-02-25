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

const ONEPIECE_DB = [
    { name: 'Monkey D. Luffy (Leader)', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP05-060.png', price: '150.00' },
    { name: 'Roronoa Zoro (SR)', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-025.png', price: '45.00' },
    { name: 'Nami (Alt Art)', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-016.png', price: '250.00' },
    { name: 'Shanks (SEC)', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-120.png', price: '1200.00' },
    { name: 'Yamato (SEC)', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-121.png', price: '300.00' },
    { name: 'Trafalgar Law', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-002.png', price: '150.00' },
    { name: 'Boa Hancock', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-078.png', price: '400.00' },
    { name: 'Sanji', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-013.png', price: '180.00' },
    { name: 'Dracule Mihawk', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-070.png', price: '95.00' },
    { name: 'Kaido', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-094.png', price: '85.00' },
    { name: 'Crocodile', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-062.png', price: '60.00' },
    { name: 'Eustass Captain Kid', img: 'https://asia-en.onepiece-cardgame.com/images/cardlist/card/OP01-051.png', price: '110.00' }
];

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
                <td>👤 ${s.user}</td>
                <td><span class="tag-condition ${s.tag}">${s.cond}</span></td>
                <td style="color: #16a34a;">$${finalPrice}</td>
                <td><button class="btn-buy-small">Comprar</button></td>
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

async function loadPokemonCards(gridId) {
    if (STATE.pokemon.loading) return;
    STATE.pokemon.loading = true;
    const grid = document.getElementById(gridId);
    
    try {
        const res = await fetch(`https://api.pokemontcg.io/v2/cards?page=${STATE.pokemon.page}&pageSize=12&orderBy=-cardmarket.prices.trendPrice`);
        const json = await res.json();
        json.data.forEach(card => {
            let realPrice = card.cardmarket?.prices?.trendPrice || card.tcgplayer?.prices?.holofoil?.market;
            grid.appendChild(createCardHTML({ 
                badge: 'Pokémon', color: '#eab308', name: card.name,
                img: card.images.large, price: realPrice ? parseFloat(realPrice).toFixed(2) : null
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

function loadOnePieceCards(gridId) {
    const grid = document.getElementById(gridId);
    const start = STATE.onepiece.page * 12;
    const items = ONEPIECE_DB.slice(start, start + 12);
    
    if (items.length === 0) {
        const btn = document.getElementById('mercado-load-more');
        if(btn) btn.style.display = 'none';
        return;
    }
    
    items.forEach(card => {
        grid.appendChild(createCardHTML({ badge: 'One Piece', color: '#f97316', ...card }));
    });
    STATE.onepiece.page++;
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
});