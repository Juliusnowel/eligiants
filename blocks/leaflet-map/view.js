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

async function loadCountryData(filename) {
    if (!window.LEAFLET_BASE_URL) return;
    
    // Clear current state options to avoid confusion
    STATE.filter.region = ''; 
    STATE.selectedId = null;
    
    try {
        const resp = await fetch(window.LEAFLET_BASE_URL + filename);
        if (!resp.ok) throw new Error('Failed to load');
        const data = await resp.json();
        
        // Update STATE
        STATE.places = data.map((p, idx) => ({
            id: p.id ?? `place_${idx}`,
            ...p,
        }));
        
        // Update City Dropdown
        updateCityDropdown(STATE.places);

        // Update Budget Slider range based on new data
        updateBudgetSlider(STATE.places);

        // Re-render
        renderMap();
        updateUIState();
        
        // Fit bounds to new set
        if (STATE.map && STATE.places.length > 0) {
            const bounds = L.latLngBounds(STATE.places.map(p => [p.lat, p.lng]));
            STATE.map.fitBounds(bounds, { padding: [50, 50] });
        }

    } catch (e) {
        console.error('Country load failed', e);
    }
}

function updateCityDropdown(places) {
    const regionSelect = document.getElementById('map-region');
    if (!regionSelect) return;
    
    const cities = new Set(places.map(p => p.city).filter(Boolean));
    const sorted = Array.from(cities).sort();
    
    regionSelect.innerHTML = '<option value="">All</option>';
    sorted.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        regionSelect.appendChild(opt);
    });
}

function updateBudgetSlider(places) {
    let maxFound = 0;
    places.forEach(p => {
        const range = parsePriceRange(p.price_range);
        if (range && range.max > maxFound) maxFound = range.max;
    });
    
    if (maxFound === 0) maxFound = 500;
    const ceiling = Math.ceil(maxFound / 100) * 100;

    const minInput = document.getElementById('budget-min');
    const maxInput = document.getElementById('budget-max');
    const maxDisp  = document.getElementById('budget-max-disp');
    
    if (minInput && maxInput) {
        // Reset values
        minInput.max = ceiling;
        maxInput.max = ceiling;
        minInput.value = 0;
        maxInput.value = ceiling;
        
        if (maxDisp) maxDisp.textContent = ceiling;
        
        STATE.filter.budget = null; // Reset filter
    }
}
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
        
        minDisp.textContent = `$${Math.min(minVal, maxVal)}`;
        maxDisp.textContent = `$${Math.max(minVal, maxVal)}`;
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
    // reset coord usage on each render
    STATE.coordUsage = {};

    items.forEach(p => {
        if (typeof p.lat !== 'number' || typeof p.lng !== 'number') return;

        // --- jitter logic START ---
        const coordKey = `${p.lat},${p.lng}`;
        const usedCount = STATE.coordUsage[coordKey] || 0;
        STATE.coordUsage[coordKey] = usedCount + 1;

        // small offset so pins don't overlap but stay near the city
        const JITTER_STEP = 0.003; // ~300m; adjust if needed
        const lat = p.lat + usedCount * JITTER_STEP;
        const lng = p.lng + usedCount * JITTER_STEP;
        // --- jitter logic END ---

        const isActive = (p.id && p.id === STATE.selectedId);

        const m = L.marker([lat, lng], {       // <- use jittered coords
                icon: markerIcon(p.segment, isActive),
                placeId: p.id,
                segment: p.segment
            })
            .addTo(STATE.layerGroup)
            .bindPopup(`
                <div class="map-popup-simple">
                    <strong>${p.name}</strong><br>
                    ${p.city || ''}${p.address ? ' · ' + p.address : ''}
                </div>
            `)
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

        const priceHtml  = p.price_range ? `<span class="panel-price"><i class="fa-solid fa-wallet"></i> $${p.price_range}</span>` : '';
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

    // Price
    if (p.price_range) {
        G('dp-price').textContent = `$${p.price_range}`;
        showRow('dp-price-row', true);
    } else {
        G('dp-price').textContent = '';
        showRow('dp-price-row', false);
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
    const suggestionsEl = document.getElementById('search-suggestions');
    
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
    
    // Search Autocomplete Logic

    const renderSuggestions = (query) => {
        if (!suggestionsEl) return;
        if (!query || query.length < 1) {
            suggestionsEl.hidden = true;
            return;
        }

        const q = query.toLowerCase();
        // Filter: match Name or City
        // We limit to 5-6 results to keep it clean
        const matches = STATE.places.filter(p => {
             const n = (p.name || '').toLowerCase();
             const c = (p.city || '').toLowerCase();
             return n.includes(q) || c.includes(q);
        }).slice(0, 6);

        if (matches.length === 0) {
            suggestionsEl.hidden = true;
            return;
        }

        // Build HTML
        let html = '<div class="suggestion-header">Suggested places:</div>';
        
        matches.forEach(p => {
            // Highlight logic
            const highlight = (txt) => {
                if(!txt) return '';
                const i = txt.toLowerCase().indexOf(q);
                if (i >= 0) {
                     return txt.substring(0, i) + '<b>' + txt.substring(i, i+q.length) + '</b>' + txt.substring(i+q.length);
                }
                return txt;
            };
            
            const nameHtml = highlight(p.name);
            const cityHtml = p.city ? `<span style="color:#888;font-size:0.85em;margin-left:6px;">${p.city}</span>` : '';
            // If match was in city, highlight city too? For now just simple name highlight is enough usually.
            
            html += `
                <div class="suggestion-item" data-id="${p.id}">
                    <div class="suggestion-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div>${nameHtml} ${cityHtml}</div>
                </div>
            `;
        });

        suggestionsEl.innerHTML = html;
        suggestionsEl.hidden = false;

        // Add Click Listeners
        suggestionsEl.querySelectorAll('.suggestion-item').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault(); // Stop prop?
                const pid = el.dataset.id;
                const p = STATE.places.find(x => x.id == pid);
                if (p) {
                    // Update input
                    searchInput.value = p.name; 
                    STATE.filter.search = p.name;
                    
                    // Fly & Select
                    if(typeof p.lat === 'number') {
                         STATE.map.flyTo([p.lat, p.lng], 15, { duration: 1.0 });
                    }
                    setSelectedPlace(p);
                    openModal(p);
                    renderMap(); // ensures filtering if we want only this result? 
                    // Actually, if we just selected one, the 'search' filter might hide others.
                    // User said "pinned location is gone one by one because like they depends on the search".
                    // If we set search input, renderMap WILL filter.
                    
                    suggestionsEl.hidden = true;
                }
            });
        });
    };

    // Events
    const doSearch = () => {
        STATE.filter.search = searchInput.value.trim();
        renderMap();
        updateUIState();
        suggestionsEl.hidden = true; // hide on submit
    };

    searchInput?.addEventListener('input', (e) => {
        // Live suggestion
        renderSuggestions(e.target.value.trim());
        
        // Live Filter Map? User said existing behavior (pinned gone one by one) is fine.
        // So we can also update map live if desired, or wait for enter/click. 
        // Standard "Google Maps" usually filters live OR suggests.
        // We'll filter live as per previous standard.
        STATE.filter.search = e.target.value.trim();
        renderMap(); 
    });

    searchInput?.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') doSearch();
        if (e.key === 'Escape') {
            suggestionsEl.hidden = true;
            searchInput.blur();
        }
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!suggestionsEl.hidden && !searchInput.contains(e.target) && !suggestionsEl.contains(e.target)) {
            suggestionsEl.hidden = true;
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

    // Country Select Logic
    const countrySelect = document.getElementById('map-country');
    if (countrySelect) {
        countrySelect.addEventListener('change', (e) => {
            loadCountryData(e.target.value);
        });
    }
});