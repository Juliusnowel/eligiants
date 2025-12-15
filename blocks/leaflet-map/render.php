<?php
/**
 * Render template for childtheme/leaflet-map block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

// 1. Get attributes
$heading = $attributes['heading'] ?? 'Sed Do Eiusmod Tempor';
$desc    = $attributes['description'] ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
$dataFile = $attributes['data'] ?? 'places.json';

// 2. Resolve JSON path
$jsonPath = __DIR__ . '/' . $dataFile;
$placesData = [];

if ( file_exists( $jsonPath ) ) {
    $jsonContent = file_get_contents( $jsonPath );
    $placesData = json_decode( $jsonContent, true );
    if ( ! is_array( $placesData ) ) {
        $placesData = [];
    }
}


// Extract unique cities for the Region dropdown
// And calculate Max Budget
$cities = [];
$maxPriceFound = 0;

foreach ($placesData as $p) {
    if (!empty($p['city'])) {
        $cities[] = $p['city'];
    }
    
    // Parse Price Range
    if (!empty($p['price_range'])) {
        // Remove non-numeric except -
        $clean = preg_replace('/[^0-9\-]/', '', $p['price_range']);
        $parts = explode('-', $clean);
        foreach ($parts as $part) {
            $val = intval($part);
            if ($val > $maxPriceFound) {
                $maxPriceFound = $val;
            }
        }
    }
}
$cities = array_unique($cities);
sort($cities);

// Round up to nearest 100
// If 0 found (no data), default to 500
if ($maxPriceFound === 0) {
    $maxPriceFound = 500;
}
$maxPriceCeiling = ceil($maxPriceFound / 100) * 100;

?>
<div <?php echo get_block_wrapper_attributes( ['class' => 'map-page'] ); ?>>
    <script>
        window.LEAFLET_PLACES = <?php echo json_encode( $placesData ); ?>;
        window.LEAFLET_BASE_URL = "<?php echo esc_js(get_stylesheet_directory_uri() . '/blocks/leaflet-map/'); ?>";
    </script>

    <div class="map-header-section">
        <h2 class="map-title"><?php echo esc_html( $heading ); ?></h2>
        <hr class="map-divider" />
        <p class="map-desc"><?php echo esc_html( $desc ); ?></p>
    </div>

    <div class="map-layout full-width">
        <section class="map-stage">
            <!-- Map toolbar (Overlay) -->
            <div class="map-toolbar-overlay">
                <!-- Left: Search & Region -->
                <div class="toolbar-group left">
                    <div class="search-box">
                        <button class="search-btn" aria-label="Menu" title="Toggle List">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <input type="text" id="map-search" placeholder="Search Places (e.g. Busan, Jeju)" aria-label="Search Places" autocomplete="off">
                        <button class="search-icon" aria-label="Submit Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <!-- Suggestions Dropdown -->
                        <div id="search-suggestions" class="search-suggestions" hidden></div>
                    </div>

                    <div class="region-select-wrap">
                        <label for="map-country" class="sr-only">Country</label>
                        <span class="region-label">Country:</span>
                        <select id="map-country">
                            <option value="places.json">South Korea</option>
                            <option value="Japan.json">Japan</option>
                            <option value="Thailand.json">Thailand</option>
                        </select>
                    </div>

                    <div class="region-select-wrap">
                        <label for="map-region" class="sr-only">City</label>
                        <span class="region-label">City:</span>
                        <select id="map-region">
                            <option value="">All</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Right: Categories -->
                <div class="toolbar-group right">
                    <!-- Food -->
                    <button type="button" class="cat-pill" data-cat="food">
                        <span class="cat-icon food"><i class="fa-solid fa-utensils"></i></span>
                        <span class="cat-text">Food</span>
                    </button>
                    <!-- Nature -->
                    <button type="button" class="cat-pill" data-cat="nature">
                        <span class="cat-icon nature"><i class="fa-solid fa-leaf"></i></span>
                        <span class="cat-text">Nature</span>
                    </button>
                    <!-- Culture -->
                    <button type="button" class="cat-pill" data-cat="culture">
                        <span class="cat-icon culture"><i class="fa-solid fa-yin-yang"></i></span>
                        <span class="cat-text">Culture</span>
                    </button>
                </div>
            </div>

            <!-- The Map -->
            <!-- Side Panel (Hidden by default) -->
            <div id="map-side-panel" class="map-side-panel" aria-hidden="true">
                <div class="panel-header">
                    <div class="panel-header-content">
                        <span id="panel-icon" class="cat-icon food"><i class="fa-solid fa-utensils"></i></span> <!-- Dynamic -->
                        <h3 id="panel-title">Food</h3> <!-- Dynamic -->
                    </div>
                    <button id="panel-close" class="panel-close-btn" aria-label="Close List">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div id="panel-list" class="panel-list">
                    <!-- Dynamic Items -->
                </div>
            </div>

            <div id="map-detail-panel" class="map-detail-panel" aria-hidden="true">
                <div class="detail-header">
                    <h3 id="dp-title"></h3>
                    <button id="detail-close" class="detail-close-btn" aria-label="Close details">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="detail-body">
                    <div id="dp-image-wrap" class="detail-image-wrap">
                        <img id="dp-image" alt="" />
                    </div>

                    <p id="dp-rating" class="detail-rating"></p>

                    <p id="dp-desc" class="detail-desc"></p>

                    <p id="dp-price-row" class="detail-row">
                        <strong>Price:</strong> <span id="dp-price"></span>
                    </p>

                    <p id="dp-addr-row" class="detail-row">
                        <strong>Address:</strong> <span id="dp-addr"></span>
                    </p>
                    <p id="dp-hours-row" class="detail-row">
                        <strong>Hours:</strong> <span id="dp-hours"></span>
                    </p>
                    <p id="dp-phone-row" class="detail-row">
                        <strong>Phone:</strong> <a id="dp-phone"></a>
                    </p>
                    <p id="dp-web-row" class="detail-row">
                        <strong>Website:</strong> <a id="dp-web" target="_blank" rel="noopener"></a>
                    </p>
                    <p id="dp-email-row" class="detail-row">
                        <strong>Email:</strong> <a id="dp-email"></a>
                    </p>
                </div>
            </div>

            <div id="map"></div>
            
            <!-- Bottom Controls (Zoom/Budget placeholder from screenshot) -->
            <div class="map-bottom-controls">
                <button class="control-btn budget-btn"><i class="fa-solid fa-wallet"></i> Budget</button>
                <div class="zoom-controls">
                    <button id="custom-zoom-out" aria-label="Zoom Out">-</button>
                    <button id="custom-zoom-in" aria-label="Zoom In">+</button>
                </div>
            </div>

        </section>
    </div>

    <!-- Budget Modal -->
    <div id="budget-modal" class="place-modal" hidden>
        <div class="place-modal__dialog budget-dialog">
            <button class="place-modal__close budget-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 class="budget-title">Set Your Budget</h3>
            
            <div class="budget-range-display">
                <span id="budget-min-disp">0</span> - <span id="budget-max-disp"><?php echo esc_html($maxPriceCeiling); ?></span>
            </div>

            <div class="range-slider-container">
                <div class="range-track"></div>
                <!-- Dual inputs for min/max -->
                <input type="range" id="budget-min" min="0" max="<?php echo esc_attr($maxPriceCeiling); ?>" value="0" step="10">
                <input type="range" id="budget-max" min="0" max="<?php echo esc_attr($maxPriceCeiling); ?>" value="<?php echo esc_attr($maxPriceCeiling); ?>" step="10">
            </div>

            <div class="budget-actions">
                <button id="budget-clear" class="btn-text">Clear</button>
                <button id="budget-apply" class="btn-primary">Apply</button>
            </div>
        </div>
    </div>
</div>
