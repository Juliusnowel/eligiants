/**
 * View script for leaflet-map block
 * Implements Search, Region filter, and 3 Main Categories (Food, Nature, Culture)
 */

// ----------------------
// 1. CONFIG & DATA
// ----------------------

// Mapping the 3 main UI categories to the internal data segments
const CATEGORY_MAP = {
  food:    ['coffee','samgyup','noodles','chicken','street_food','trad_restaurants'],
  nature:  ['nature','beaches','trails','hidden_gems'],
  culture: ['attractions','culture','festivals','shopping','bookstores','kpop','hanbok','nightlife']
};

// Icon Configuration
const PIN_COLORS = {
  // Food
  coffee:'#8B4513', samgyup:'#795548', noodles:'#009688', chicken:'#F44336',
  street_food:'#ff6f00', trad_restaurants:'#5d4037',
  // Nature
  nature:'#2e7d32', beaches:'#00bcd4', trails:'#33691e', hidden_gems:'#673ab7',
  // Culture
  attractions:'#1a73e8', shopping:'#3f51b5', bookstores:'#607d8b', kpop:'#e91e63',
  hanbok:'#9c27b0', culture:'#4caf50', festivals:'#ff1744', nightlife:'#000000'
};

const ICONS = {
    // Food
    coffee: 'mug-hot', samgyup: 'bacon', noodles: 'bowl-food', chicken: 'drumstick-bite',
    street_food: 'hotdog', trad_restaurants: 'utensils',
    // Nature
    nature: 'tree', beaches: 'umbrella-beach', trails: 'person-hiking', hidden_gems: 'gem',
    // Culture
    attractions: 'camera-retro', shopping: 'bag-shopping', bookstores: 'book', kpop: 'music',
    hanbok: 'shirt', culture: 'palette', festivals: 'masks-theater', nightlife: 'martini-glass'
};

function faPin(faName, color) {
  return `<div class="fa-pin" style="--pin-bg:${color};"><i class="fa-solid fa-${faName}"></i></div>`;
}

function markerIcon(seg, active = false) {
  // If active, use a specific 'pinned' icon (thumbtack) to make it distinct
  const icon = active ? 'thumbtack' : (ICONS[seg] || 'map-pin');
  const color = PIN_COLORS[seg] || '#333';

  const size   = active ? [42, 56] : [30, 42]; // Slightly larger active
  const anchor = active ? [21, 56] : [15, 42];
  const extraClass = active ? ' is-active' : '';

  return L.divIcon({
    className: `seg-pin pin-${seg}${extraClass}`,
    html: faPin(icon, color),
    iconSize: size,
    iconAnchor: anchor
  });
}

function setSelectedPlace(p) {
    if (!p) return;

    if (STATE.selectedId && STATE.selectedId !== p.id) {
        const oldMarker = STATE.markers.get(STATE.selectedId);
        const oldPlace  = STATE.places.find(x => x.id === STATE.selectedId);
        if (oldMarker && oldPlace) {
            oldMarker.setIcon(markerIcon(oldPlace.segment, false));
        }
    }

    const marker = STATE.markers.get(p.id);
    if (marker) {
        marker.setIcon(markerIcon(p.segment, true));
        STATE.selectedId = p.id;
    }
}

function stars(rating) {
  if (typeof rating !== 'number') return '';
  const full = Math.floor(rating);
  const half = (rating % 1) >= 0.5;
  const empty = 5 - full - (half ? 1 : 0);
  return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
}

// Global State
const rawPlaces = window.LEAFLET_PLACES || [];

const STATE = {
    places: rawPlaces.map((p, idx) => ({
        id: p.id ?? `place_${idx}`,
        ...p,
    })),
    filter: { category: null, region: '', search: '', budget: null },
    map: null,
    layerGroup: null,
    markers: new Map(),
    selectedId: null,
};


// ----------------------
// 2. CORE LOGIC
// ----------------------

function parsePriceRange(rangeStr) {
    if (!rangeStr) return null;
    // Formats: "100-200", "500", "1000+" (handle basic)
    const parts = rangeStr.split('-').map(s => parseInt(s.replace(/[^0-9]/g, ''), 10));
    if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
        return { min: parts[0], max: parts[1] };
    }
    if (parts.length === 1 && !isNaN(parts[0])) {
        return { min: parts[0], max: parts[0] }; // Exact price treated as range min=max
    }
    return null;
}

function getFilteredPlaces() {
    let items = STATE.places;

    // 1. Category Filter
    if (STATE.filter.category) {
        const allowedSegments = CATEGORY_MAP[STATE.filter.category] || [];
        items = items.filter(p => allowedSegments.includes(p.segment));
    }

    // 2. Region Filter
    if (STATE.filter.region) {
        items = items.filter(p => p.city === STATE.filter.region);
    }

    // 3. Search Filter
    if (STATE.filter.search) {
        const q = STATE.filter.search.toLowerCase();
        items = items.filter(p => 
            (p.name && p.name.toLowerCase().includes(q)) ||
            (p.city && p.city.toLowerCase().includes(q)) ||
            (p.segment && p.segment.toLowerCase().includes(q))
        );
    }

    // 4. Budget Filter
    if (STATE.filter.budget) {
        const { min: userMin, max: userMax } = STATE.filter.budget;
        items = items.filter(p => {
             const placeRange = parsePriceRange(p.price_range);
             if (!placeRange) return false; // Hide items with no price info? Or show? User said "within what i set", implying check against known.
             
             // Check overlap: start <= end && end >= start
             // Max(p.min, u.min) <= Min(p.max, u.max)
             const overlapStart = Math.max(placeRange.min, userMin);
             const overlapEnd = Math.min(placeRange.max, userMax);
             
             return overlapStart <= overlapEnd;
        });
    }

    return items;
}

// ... existing renderMap, updateUIState, modals ...

function initBudgetSlider() {
    const minInput = document.getElementById('budget-min');
    const maxInput = document.getElementById('budget-max');
    const minDisp = document.getElementById('budget-min-disp');
    const maxDisp = document.getElementById('budget-max-disp');
    
    if(!minInput || !maxInput) return;

    const updateDisplay = () => {
        const minVal = parseInt(minInput.value, 10);
        const maxVal = parseInt(maxInput.value, 10);
        
        // Enforce Min <= Max visual logic
        // If they cross, we can swap or clamp. Standard is usually clamping.
        if (minVal > maxVal) {
             // temporarily visually misleading but inputs allow crossing. 
             // We'll handle the effective range in state.
        }
        
        minDisp.textContent = `P${Math.min(minVal, maxVal)}`;
        maxDisp.textContent = `P${Math.max(minVal, maxVal)}`;
    };

    minInput.addEventListener('input', updateDisplay);
    maxInput.addEventListener('input', updateDisplay);
    
    // Init display
    updateDisplay();
}

function toggleBudgetModal(show) {
    const modal = document.getElementById('budget-modal');
    if (!modal) return;
    modal.hidden = !show;
}

function renderMap() {
    if (!STATE.layerGroup) return;

    STATE.layerGroup.clearLayers();
    STATE.markers.clear();

    const items = getFilteredPlaces();
    if (items.length === 0) {
        STATE.selectedId = null;
        return;
    }

    const visibleIds = new Set();

    items.forEach(p => {
        if (typeof p.lat !== 'number' || typeof p.lng !== 'number') return;

        const isActive = (p.id && p.id === STATE.selectedId);

        const m = L.marker([p.lat, p.lng], { 
                icon: markerIcon(p.segment, isActive),
                placeId: p.id,
                segment: p.segment
            })
            .addTo(STATE.layerGroup)
            .bindPopup(`<div class="map-popup-simple"><strong>${p.name}</strong><br>${p.city || ''}</div>`) 
            .on('click', () => {
                setSelectedPlace(p);   // highlight pin
                openModal(p);          // show details panel
            });

        STATE.markers.set(p.id, m);
        visibleIds.add(p.id);
    });

    // If selected place is now filtered out, clear selection
    if (STATE.selectedId && !visibleIds.has(STATE.selectedId)) {
        STATE.selectedId = null;
    }
}

// ----------------------
// 3. UI & SIDE PANEL LOGIC
// ----------------------

function toggleSidePanel(show) {
    const panel = document.getElementById('map-side-panel');
    if (!panel) return;
    
    if (typeof show !== 'boolean') {
        const isHidden = panel.getAttribute('aria-hidden') === 'true';
        show = isHidden;
    }
    
    panel.setAttribute('aria-hidden', !show);
    if (show) {
        renderPanelList();
    }
}

function renderPanelList() {
    const listEl = document.getElementById('panel-list');
    const titleEl = document.getElementById('panel-title');
    const iconEl = document.getElementById('panel-icon');
    
    if (!listEl) return;
    
    // 1. Header Logic
    let category = STATE.filter.category;
    let title = 'All Places';
    let iconClass = 'fa-map'; // Default
    let color = '#333';

    if (category) {
        title = category.charAt(0).toUpperCase() + category.slice(1); // Capitalize
        // Get specific icon for category representation
        if (category === 'food') { iconClass = 'fa-utensils'; color = '#ff5e3a'; }
        if (category === 'nature') { iconClass = 'fa-leaf'; color = '#4CAF50'; }
        if (category === 'culture') { iconClass = 'fa-yin-yang'; color = '#E91E63'; }
    }

    if (titleEl) {
        titleEl.textContent = title;
        titleEl.style.color = color;
    }
    if (iconEl) {
        iconEl.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
        iconEl.style.color = color;
    }

    // 2. List Logic
    listEl.innerHTML = ''; // clear
    const items = getFilteredPlaces();

    if (items.length === 0) {
        listEl.innerHTML = '<div style="padding:20px;text-align:center;color:#999;">No places found.</div>';
        return;
    }

    const frag = document.createDocumentFragment();
    items.forEach(p => {
        const item = document.createElement('div');
        item.className = 'panel-item';

        // Icon based on segment
        const seg       = p.segment;
        const iconName  = ICONS[seg] || 'map-pin';
        const iconColor = PIN_COLORS[seg] || '#ff5e3a';

        const priceHtml  = p.price_range ? `<span class="panel-price"><i class="fa-solid fa-wallet"></i> ${p.price_range}</span>` : '';
        const ratingHtml = typeof p.rating === 'number' ? `<span class="panel-rating">${stars(p.rating)}</span>` : '';

        item.innerHTML = `
            <div class="panel-item-header">
                <span class="panel-item-icon" style="color:${iconColor};background:${iconColor}14;">
                    <i class="fa-solid fa-${iconName}"></i>
                </span>
                <h4>${p.name}</h4>
            </div>
            <p>${p.city || ''} ${p.address ? ' · ' + p.address : ''}</p>
            <div class="panel-item-meta">
                ${priceHtml}
                ${ratingHtml}
            </div>
        `;

        item.addEventListener('click', () => {
            if (STATE.map && typeof p.lat === 'number') {
                STATE.map.flyTo([p.lat, p.lng], 14, { duration: 1.0 });
            }
            setSelectedPlace(p);
            openModal(p);
        });

        frag.appendChild(item);
    });

    listEl.appendChild(frag);
}

function updateUIState() {
    // Update Category Chips
    document.querySelectorAll('.cat-pill').forEach(btn => {
        const cat = btn.dataset.cat;
        if (cat === STATE.filter.category) {
            btn.classList.add('is-active');
        } else {
            btn.classList.remove('is-active');
        }
    });

    // Update Region Select
    const regionSelect = document.getElementById('map-region');
    if (regionSelect) regionSelect.value = STATE.filter.region;
    
    // If panel is open, re-render list to match new filters
    const panel = document.getElementById('map-side-panel');
    if (panel && panel.getAttribute('aria-hidden') === 'false') {
        renderPanelList();
    }
}

// ----------------------
// 3. MODAL LOGIC
// ----------------------
function openModal(p) {
    const panel = document.getElementById('map-detail-panel');
    if (!panel) return;

    const G = id => document.getElementById(id);
    const showRow = (rowId, cond) => {
        const el = G(rowId);
        if (el) el.style.display = cond ? '' : 'none';
    };

    // Basic fields
    G('dp-title').textContent = p.name || '';
    G('dp-desc').textContent  = p.description || p.city || '';

    // Image
    const imgWrap = G('dp-image-wrap');
    const img = G('dp-image');
    if (p.image) {
        img.src = p.image;
        imgWrap.style.display = '';
    } else {
        img.removeAttribute('src');
        imgWrap.style.display = 'none';
    }

    // Rating
    if (typeof p.rating === 'number') {
        G('dp-rating').textContent = `${stars(p.rating)} (${p.rating.toFixed(1)})`;
        G('dp-rating').style.display = '';
    } else {
        G('dp-rating').style.display = 'none';
    }

    // Detail rows
    G('dp-addr').textContent = p.address || '';
    showRow('dp-addr-row', !!p.address);

    G('dp-hours').textContent = p.hours || '';
    showRow('dp-hours-row', !!p.hours);

    if (p.phone) {
        const phoneEl = G('dp-phone');
        phoneEl.textContent = p.phone;
        phoneEl.href = `tel:${p.phone.replace(/[^0-9+]/g, '')}`;
        showRow('dp-phone-row', true);
    } else {
        showRow('dp-phone-row', false);
    }

    if (p.website) {
        const webEl = G('dp-web');
        webEl.textContent = p.website;
        webEl.href = p.website;
        showRow('dp-web-row', true);
    } else {
        showRow('dp-web-row', false);
    }

    if (p.email) {
        const emailEl = G('dp-email');
        emailEl.textContent = p.email;
        emailEl.href = `mailto:${p.email}`;
        showRow('dp-email-row', true);
    } else {
        showRow('dp-email-row', false);
    }

    // Show panel (no full-screen overlay, no scrolling lock)
    panel.setAttribute('aria-hidden', 'false');
}

function initModal() {
    const modal = document.getElementById('place-modal');
    const close = modal?.querySelector('.place-modal__close');
    const hide = () => { if(modal) modal.hidden = true; document.documentElement.style.overflow = ''; };

    close?.addEventListener('click', hide);
    modal?.addEventListener('click', e => { if(e.target === modal) hide(); });
    document.addEventListener('keydown', e => { if(e.key === 'Escape') hide(); });
}

function initDetailPanel() {
    const panel = document.getElementById('map-detail-panel');
    const closeBtn = document.getElementById('detail-close');
    if (!panel || !closeBtn) return;

    closeBtn.addEventListener('click', () => {
        panel.setAttribute('aria-hidden', 'true');
    });
}

// ----------------------
// 4. INITIALIZATION
// ----------------------
document.addEventListener('DOMContentLoaded', () => {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;
    
    // Check if already init
    if (window.__leafletMap) {
        // Just return or re-init? Safer to return if already there.
        // return; 
    }

    // 1. Init Map
    const map = L.map('map', { 
        zoomControl: false, // We use custom buttons
        attributionControl: true 
    }).setView([36.5, 127.8], 7); // Center Korea

    window.__leafletMap = map; // Global ref

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    STATE.map = map;
    STATE.layerGroup = L.layerGroup().addTo(map);

    // 2. Wire Up Controls

    // Category Chips
    document.querySelectorAll('.cat-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            const cat = btn.dataset.cat;
            // Toggle: if clicking active, turn off.
            if (STATE.filter.category === cat) {
                STATE.filter.category = null;
            } else {
                STATE.filter.category = cat;
            }
            updateUIState();
            renderMap();
        });
    });

    // Region Select
    const regionSelect = document.getElementById('map-region');
    if (regionSelect) {
        regionSelect.addEventListener('change', (e) => {
            STATE.filter.region = e.target.value;
            renderMap();
        });
    }

    // Search Box
    const searchInput = document.getElementById('map-search');
    const searchBtn = document.querySelector('.search-icon');
    
    // Panel Toggle (Hamburger)
    const hamburgerBtn = document.querySelector('.search-btn'); // Left button
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', () => {
            toggleSidePanel(true);
        });
    }

    // Panel Close
    const panelCloseBtn = document.getElementById('panel-close');
    if (panelCloseBtn) {
        panelCloseBtn.addEventListener('click', () => {
            toggleSidePanel(false);
        });
    }
    
    const doSearch = () => {
        STATE.filter.search = searchInput.value.trim();
        renderMap();
        updateUIState(); // updates list if open
    };

    searchInput?.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') doSearch();
        else {
             // Optional: live search
             STATE.filter.search = searchInput.value.trim();
             renderMap();
             updateUIState();
        }
    });

    searchBtn?.addEventListener('click', doSearch);

    // Zoom Controls
    document.getElementById('custom-zoom-in')?.addEventListener('click', () => map.zoomIn());
    document.getElementById('custom-zoom-out')?.addEventListener('click', () => map.zoomOut());
    
    // Budget Modal Controls
    initBudgetSlider();
    const budgetBtn = document.querySelector('.budget-btn');
    const budgetModal = document.getElementById('budget-modal');
    if (budgetBtn) {
        budgetBtn.addEventListener('click', () => toggleBudgetModal(true));
    }
    const budgetClose = budgetModal?.querySelector('.budget-close');
    budgetClose?.addEventListener('click', () => toggleBudgetModal(false));
    
    // Budget Apply/Clear
    document.getElementById('budget-apply')?.addEventListener('click', () => {
        const minVal = parseInt(document.getElementById('budget-min').value, 10);
        const maxVal = parseInt(document.getElementById('budget-max').value, 10);
        STATE.filter.budget = { min: Math.min(minVal, maxVal), max: Math.max(minVal, maxVal) };
        toggleBudgetModal(false);
        renderMap();
        updateUIState();
    });
    
    document.getElementById('budget-clear')?.addEventListener('click', () => {
        STATE.filter.budget = null;
        toggleBudgetModal(false);
        renderMap();
        updateUIState();
    });

    // 3. Initial Render
    initDetailPanel();
    initModal();
    renderMap();
});