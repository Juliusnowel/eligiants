<?php
/**
 * Card Image — server render with Top 5 Rating & Tabs
 */
if (!defined('ABSPATH')) { exit; }

/** Polyfill for PHP < 8.1 */
if (!function_exists('array_is_list')) {
  function array_is_list($array) {
    if (!is_array($array)) return false;
    $i = 0; foreach ($array as $k => $_) { if ($k !== $i++) return false; }
    return true;
  }
}

$A = (isset($attributes) && is_array($attributes)) ? $attributes : [];
if (!isset($A['design'])) { $A['design'] = 'left'; }

// --- 1. Data Fetching Logic ---
$dataSource = $A['dataSource'] ?? '';
$items = [];
$isDynamic = false;

// Broad Category Mapping
// Broad Category Mapping
$catMap = [
  'Food'    => ['coffee', 'samgyup', 'noodles', 'chicken', 'street_food', 'trad_restaurants', 'food'],
  'Nature'  => ['nature', 'beaches'],
  'Culture' => ['attractions', 'hidden_gems', 'shopping', 'bookstores', 'kpop', 'hanbok', 'culture'],
];

// Pre-defined icons for tabs
$tabIcons = [
  'Food'    => 'fa-solid fa-utensils',
  'Nature'  => 'fa-solid fa-seedling',
  'Culture' => 'fa-solid fa-yin-yang', 
];

$top5Lists = [];
$debugInfo = [];

if (!empty($dataSource) && substr($dataSource, -5) === '.json') {
    $isDynamic = true;
    // Relative path from blocks/card-img/render.php to blocks/leaflet-map/
    $jsonPath = dirname(__DIR__) . '/leaflet-map/' . basename($dataSource);
    
    if (file_exists($jsonPath)) {
        $jsonContent = file_get_contents($jsonPath);
        $rawData = json_decode($jsonContent, true);
        
        if ($rawData && is_array($rawData)) {
            
            // Bucketize
            $buckets = ['Food' => [], 'Nature' => [], 'Culture' => []];
            
            foreach ($rawData as $place) {
                $seg = $place['segment'] ?? '';
                $rating = isset($place['rating']) ? (float)$place['rating'] : 0;
                
                // Find which bucket this segment belongs to
                foreach ($catMap as $mainCat => $subCats) {
                    if (in_array($seg, $subCats)) {
                        $place['_rating'] = $rating; // store for sorting
                        $buckets[$mainCat][] = $place;
                        break;
                    }
                }
            }
            
            // Sort and Slice Top 5
            foreach ($buckets as $cat => $list) {
                usort($list, function($a, $b) {
                    return $b['_rating'] <=> $a['_rating']; // Descending
                });
                $top5Lists[$cat] = array_slice($list, 0, 5);
            }
        }
    }
} else {
    // Fallback: Manual items
    $items = isset($A['items']) ? $A['items'] : [];
}

// --- 2. Render Preparations ---
$header_title = (string)($A['title'] ?? '');
$description  = (string)($A['intro'] ?? '');
$design       = isset($A['design']) ? strtolower(trim((string)$A['design'])) : 'left';
$design_class = ($design === 'top') ? ' food-card--top' : '';

$uniqueId = 'card-tabs-' . uniqid();
?>

<div class="container-fluid ">
  <section class="food-section child-block">
    <!-- Header -->
    <header class="section-header">
      <?php if ($header_title) : ?>
        <h2 class="section-title"><?php echo esc_html($header_title); ?></h2>
        <div class="section-line"></div>
      <?php endif; ?>

      <?php if ($description) : ?>
        <p class="section-subtitle"><?php echo esc_html($description); ?></p>
      <?php endif; ?>
    </header>

    <?php if ($isDynamic && !empty($top5Lists)) : ?>
        <!-- Tab Navigation -->
        <div class="d-flex justify-content-end gap-3 my-4 flex-wrap filters-container" id="<?php echo esc_attr($uniqueId); ?>-controls">
            <?php 
            $first = true;
            foreach ($top5Lists as $cat => $list) : 
                if (empty($list)) continue; 
                $activeClass = $first ? 'active' : '';
                $icon = $tabIcons[$cat] ?? '';
                ?>
                <button class="btn btn-pill-filter <?php echo $activeClass; ?>" 
                        type="button"
                        data-target-cat="<?php echo esc_attr($cat); ?>">
                    <?php if($icon): ?><i class="<?php echo $icon; ?> me-2"></i><?php endif; ?>
                    <?php echo esc_html($cat); ?>
                </button>
                <?php $first = false; 
            endforeach; ?>
        </div>

        <!-- Tab Content Wrapper -->
        <div id="<?php echo esc_attr($uniqueId); ?>" class="card-tabs-wrapper">
            <?php 
            $firstPane = true;
            foreach ($top5Lists as $cat => $list) : 
                if (empty($list)) continue; 
                $paneClass = $firstPane ? 'active-pane' : '';
                $firstPane = false;
                ?>
                
                <div class="card-tab-pane <?php echo $paneClass; ?>" data-cat="<?php echo esc_attr($cat); ?>">
                    <div class="row g-4 justify-content-center">
                        <?php foreach ($list as $place) : 
                            $name = $place['name'] ?? 'Untitled';
                            $desc = $place['description'] ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.';
                            if (!empty($place['text'])) $desc = $place['text'];
                            
                            $rating = $place['rating'] ?? 0;
                            $img = $place['image'] ?? $place['imageURL'] ?? ''; 
                            $hasImg = !empty($img);
                            
                            $city = $place['city'] ?? '';
                            $address = $place['address'] ?? '';
                            $locationLabel = $city ? $city : 'Unknown Location';
                            ?>
                            
                            <div class="col-12 col-md-6 col-lg-4">
                                <article class="food-card <?php echo $design_class; ?> h-100">
                                    <figure class="food-card__media">
                                        <?php if ($hasImg) : ?>
                                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy" />
                                        <?php else : ?>
                                            <div class="food-card__media--empty">
                                                <div class="coming-soon-badge">
                                                    <span>COMING</span><br><span>SOON</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </figure>

                                    <div class="food-card__content">
                                        <h3 class="food-card__title"><?php echo esc_html($name); ?></h3>
                                        <div class="card-title-line"></div>
                                        
                                        <!-- Rating -->
                                        <div class="food-card__rating text-warning mb-2">
                                            <?php 
                                            // Render stars
                                            $fullStars = floor($rating);
                                            $halfStar = ($rating - $fullStars) >= 0.5;
                                            for ($i=1; $i<=5; $i++) {
                                                if ($i <= $fullStars) echo '<i class="fa-solid fa-star"></i>';
                                                elseif ($i == $fullStars + 1 && $halfStar) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                                else echo '<i class="fa-regular fa-star"></i>';
                                            }
                                            ?>
                                        </div>

                                        <p class="food-card__text mb-3"><?php echo esc_html($desc); ?></p>
                                        
                                        <div class="food-card__footer mt-auto d-flex align-items-center text-muted">
                                            <i class="fa-solid fa-location-dot me-2"></i>
                                            <small><?php echo esc_html($locationLabel); ?></small>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        (function() {
            var wrapperId = '<?php echo $uniqueId; ?>';
            var controlsId = wrapperId + '-controls';
            var controls = document.getElementById(controlsId);
            var wrapper = document.getElementById(wrapperId);
            
            if (controls && wrapper) {
                var buttons = controls.querySelectorAll('.btn-pill-filter');
                var panes = wrapper.querySelectorAll('.card-tab-pane');
                
                buttons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var target = this.getAttribute('data-target-cat');
                        
                        // Update buttons
                        buttons.forEach(function(b) { b.classList.remove('active'); });
                        this.classList.add('active');
                        
                        // Update panes
                        panes.forEach(function(p) { 
                            if(p.getAttribute('data-cat') === target) {
                                p.style.display = 'block';
                                setTimeout(function(){ p.classList.add('active-pane'); }, 10);
                            } else {
                                p.classList.remove('active-pane');
                                p.style.display = 'none';
                            }
                        });
                    });
                });
            }
        })();
        </script>
        
    <?php else : ?>
        <!-- Manual Items Fallback -->
        <div class="row g-4 justify-content-center mt-2">
             <?php foreach ($items as $card) : 
                $img = $card['image'] ?? '';
                $h = $card['heading'] ?? '';
                $t = $card['text'] ?? '';
             ?>
             <div class="col-md-4">
                 <div class="food-card">
                     <?php if($img) echo "<img src='$img' class='img-fluid'/>"; ?>
                     <h3><?php echo $h; ?></h3>
                     <p><?php echo $t; ?></p>
                 </div>
             </div>
             <?php endforeach; ?>
        </div>
    <?php endif; ?>
  </section>
</div>
