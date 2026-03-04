function toggleDarkMode() {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
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
    if (list) {
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
    }

    const modal = document.getElementById('card-modal');
    if(modal) {
        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('open'); }, 10);
    }
}

function closeModal() {
    const modal = document.getElementById('card-modal');
    if(modal) {
        modal.classList.remove('open');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    }
}

// --- APIS ---
async function loadPokemonCards(gridId) {
    if (STATE.pokemon.loading) return;
    STATE.pokemon.loading = true;
    const grid = document.getElementById(gridId);
    if(!grid) return;

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
                img: card.image + '/high.png', price: realPrice ? parseFloat(realPrice).toFixed(2) : null
            }));
        });
        STATE.pokemon.page++;
    } catch (err) { console.error(err); }
    STATE.pokemon.loading = false;
}

async function loadYugiohCards(gridId) {
    if (STATE.yugioh.loading) return;
    STATE.yugioh.loading = true;
    const grid = document.getElementById(gridId);
    if(!grid) return;

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
    } catch (err) { console.error(err); }
    STATE.yugioh.loading = false;
}

async function loadMagicCards(gridId) {
    if (STATE.magic.loading) return;
    STATE.magic.loading = true;
    const grid = document.getElementById(gridId);
    if(!grid) return;

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
    } catch (err) { console.error(err); }
    STATE.magic.loading = false;
}

async function loadOnePieceCards(gridId) {
    if (STATE.onepiece.loading) return;
    STATE.onepiece.loading = true;
    const grid = document.getElementById(gridId);
    if(!grid) return;

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
    } catch (err) { console.error(err); }
    STATE.onepiece.loading = false;
}

// ==========================================================
// 4. TCG PREDICTOR (SISTEMA DE APUESTAS CON TEMPORIZADOR)
// ==========================================================
let currentUserBalance = parseInt(localStorage.getItem('lootcoins_balance')) || 1000;
let currentBets = JSON.parse(localStorage.getItem('lootcoins_bets')) || [];
let activeBetCard = null;
let activeBetType = null;
let activePredictorCards = [];

// Base de datos de 10 cartas Premium
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

    // Seleccionar 4 cartas iniciales al azar y asignarles un temporizador entre 15 y 60 segundos
    let shuffled = [...PREDICTOR_POOL].sort(() => 0.5 - Math.random());
    activePredictorCards = shuffled.slice(0, 4).map(card => ({
        ...card,
        expiresAt: Date.now() + (Math.floor(Math.random() * 45) + 15) * 1000
    }));

    renderArenaGrid();
    
    // Iniciar el bucle del temporizador (cada 1 segundo)
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
            // El temporizador se acabó -> Buscar una carta que NO esté en pantalla actualmente
            let available = PREDICTOR_POOL.filter(c => !activePredictorCards.find(ac => ac.id === c.id));
            if (available.length === 0) available = PREDICTOR_POOL; // Por seguridad
            
            let newCard = available[Math.floor(Math.random() * available.length)];
            
            // Asignar a esa ranura la nueva carta con otro temporizador
            activePredictorCards[index] = {
                ...newCard,
                expiresAt: now + (Math.floor(Math.random() * 45) + 30) * 1000
            };
            needsRender = true;
        } else {
            // Actualizar solo el texto del tiempo (más eficiente)
            let mins = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            let secs = (timeLeft % 60).toString().padStart(2, '0');
            let timerEl = document.getElementById(`timer-${index}`);
            if (timerEl) {
                timerEl.innerText = `⏳ ${mins}:${secs}`;
                if(timeLeft <= 5) {
                    timerEl.style.color = '#ef4444'; // Se pone rojo los últimos 5 segs
                }
            }
        }
    });

    if (needsRender) {
        renderArenaGrid();
    }
}

function openBetModal(cardId, type) {
    activeBetCard = PREDICTOR_POOL.find(c => c.id === cardId);
    activeBetType = type;

    const typeText = type === 'bull' ? 'subirá 📈' : 'bajará 📉';
    const typeColor = type === 'bull' ? '#10b981' : '#ef4444';

    document.getElementById('bet-modal-title').innerHTML = `Apostar a <span style="color:${typeColor}">${activeBetType === 'bull' ? 'ALZA' : 'BAJA'}</span>`;
    document.getElementById('bet-modal-desc').innerHTML = `¿Cuántos Lujanitos apuestas a que <b>${activeBetCard.name}</b> ${typeText}?`;
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

// ==========================================================
// 5. INICIALIZADOR GENERAL DE LA PÁGINA
// ==========================================================
document.addEventListener('DOMContentLoaded', () => {
    
    const urlParams = new URLSearchParams(window.location.search);
    const gameParam = urlParams.get('game');
    
    // Mercado
    if (document.getElementById('mercado-grid')) {
        const loadBtn = document.getElementById('mercado-load-more');
        const title = document.getElementById('mercado-title');
        
        if (gameParam === 'pokemon') { title.innerText = 'Mercado: Pokémon TCG'; loadPokemonCards('mercado-grid'); if(loadBtn) loadBtn.onclick = () => loadPokemonCards('mercado-grid'); } 
        else if (gameParam === 'yugioh') { title.innerText = 'Mercado: Yu-Gi-Oh!'; loadYugiohCards('mercado-grid'); if(loadBtn) loadBtn.onclick = () => loadYugiohCards('mercado-grid'); } 
        else if (gameParam === 'magic') { title.innerText = 'Mercado: Magic The Gathering'; loadMagicCards('mercado-grid'); if(loadBtn) loadBtn.onclick = () => loadMagicCards('mercado-grid'); } 
        else if (gameParam === 'onepiece') { title.innerText = 'Mercado: One Piece'; loadOnePieceCards('mercado-grid'); if(loadBtn) loadBtn.onclick = () => loadOnePieceCards('mercado-grid'); }
    } 
    // Index (Solo si NO está la Arena cargada)
    else if (document.getElementById('main-content') && !document.getElementById('arena-grid')) {
        loadPokemonCards('pokemon-grid');
        loadYugiohCards('yugioh-grid');
        loadMagicCards('magic-grid');
        loadOnePieceCards('onepiece-grid');
    }

    // TCG Predictor (Arena)
    if (document.getElementById('arena-grid')) {
        initArena();
        
        const btnConfirm = document.getElementById('btn-confirm-bet');
        if (btnConfirm) {
            btnConfirm.addEventListener('click', () => {
                const amountInput = document.getElementById('bet-amount');
                const amount = parseInt(amountInput.value);

                if (isNaN(amount) || amount <= 0) { alert('Introduce una cantidad válida.'); return; }
                if (amount > currentUserBalance) { alert('No tienes suficientes LootCoins.'); return; }

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
            });
        }
    }

    // Modales generales
    const modal = document.getElementById('card-modal');
    if (modal) { modal.addEventListener('click', function(e) { if (e.target === this) closeModal(); }); }
    
    const closeBtn = document.getElementById('close-modal-btn');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
});