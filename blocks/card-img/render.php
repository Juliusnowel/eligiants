<?php
/**
 * Card Image — server render
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

// 1) Start with incoming attributes
$A = (isset($attributes) && is_array($attributes)) ? $attributes : [];
// ensure default for new layout variant even without JSON hydration
if (!isset($A['design'])) { $A['design'] = 'left'; }

// 2) Decide if we need data hydration
$hasItems   = !empty($A['items']) && is_array($A['items']);
$hasHeaders = (trim($A['title'] ?? '') !== '') || (trim($A['intro'] ?? '') !== '');
$needsData  = !($hasItems || $hasHeaders);

// 3) Load region JSON if needed (supports "card-img" single or "card-imgs" array)
if ($needsData) {
  if (!function_exists('child_load_region_data')) {
    require_once trailingslashit(get_stylesheet_directory()) . 'inc/region-data.php';
  }
  $data = child_load_region_data();

  $key   = isset($A['dataKey'])   ? (string)$A['dataKey']   : 'card-img';
  $index = isset($A['dataIndex']) ? (int)$A['dataIndex']    : 0;

  $loaded = null;

  // Primary: whatever key was requested
  if (!empty($data[$key]) && is_array($data[$key])) {
    if (array_is_list($data[$key]) && isset($data[$key][$index]) && is_array($data[$key][$index])) {
      $loaded = $data[$key][$index];
    } elseif (!array_is_list($data[$key])) {
      $loaded = $data[$key]; // single object at key
    }
  }

  // Back-compat: also allow "card-imgs"
  if (!$loaded && !empty($data['card-imgs']) && is_array($data['card-imgs'])) {
    if (array_is_list($data['card-imgs']) && isset($data['card-imgs'][$index])) {
      $loaded = $data['card-imgs'][$index];
    } elseif (!array_is_list($data['card-imgs'])) {
      $loaded = $data['card-imgs'];
    }
  }

  if ($loaded) {
    // Seed with defaults, then keep editor attrs, then fill ONLY empty editor fields from JSON
    $A = array_merge([
      'title'        => '',
      'intro'        => '',
      'items'        => [],
      'showReadLink' => false,
      'readLinkText' => 'Read more',
      'ctaText'      => '',
      'ctaUrl'       => '',
      'data_count'   => '',
      'animation'    => 'Off',
      'showIndicators'=> false,
      'showControls'  => false,
      // New: layout design variant for desktop
      // - left: image left, text right (current/default)
      // - top:  image top, text below (like screenshot)
      'design'       => 'left',
      'align'        => null,
    ], $A);

    foreach (['title','intro','ctaText','ctaUrl','readLinkText','data_count','animation'] as $k) {
      $cur = (isset($A[$k]) ? (is_string($A[$k]) ? trim($A[$k]) : $A[$k]) : '');
      if (($cur === '' || $cur === null || $cur === false) && isset($loaded[$k])) {
        $A[$k] = $loaded[$k];
      }
    }

    /* NEW — always honor JSON design if provided */
    if (isset($loaded['design']) && is_string($loaded['design']) && $loaded['design'] !== '') {
      $A['design'] = strtolower($loaded['design']);  // 'top' | 'left'
    }

    /* Optional: also carry these over if present in JSON */
    if (array_key_exists('showReadLink',$loaded)) $A['showReadLink'] = (bool)$loaded['showReadLink'];
    if (array_key_exists('showIndicators',$loaded)) $A['showIndicators'] = (bool)$loaded['showIndicators'];
    if (array_key_exists('showControls',$loaded))  $A['showControls']  = (bool)$loaded['showControls'];

    if (empty($A['items']) && !empty($loaded['items']) && is_array($loaded['items'])) {
      $A['items'] = $loaded['items'];
    }
  }
}

// 4) Normalize + limit items
$limit = isset($A['data_count']) ? (int)$A['data_count'] : 0;
$items = (isset($A['items']) && is_array($A['items'])) ? $A['items'] : [];

$items = array_map(function($it) {
  if (!is_array($it)) return [];

  // Accept "image" or "imageURL"
  if (empty($it['image']) && !empty($it['imageURL'])) {
    $it['image'] = $it['imageURL'];
  }

  // Normalize links:
  //  - "url": "/path"
  //  - "link": "/path"
  //  - "link": { "url": "...", "title": "..." }
  $url   = '';
  $title = '';
  if (isset($it['link']) && is_array($it['link'])) {
    $url   = (string)($it['link']['url']   ?? '');
    $title = (string)($it['link']['title'] ?? '');
  } elseif (!empty($it['link']) && is_string($it['link'])) {
    $url = (string)$it['link'];
  } elseif (!empty($it['url'])) {
    $url = (string)$it['url'];
  }
  $it['__url']   = $url;
  $it['__title'] = $title;

  // Ensure strings exist
  $it['image']   = (string)($it['image']   ?? '');
  $it['heading'] = (string)($it['heading'] ?? '');
  $it['text']    = (string)($it['text']    ?? '');

  return $it;
}, $items);

if ($limit > 0) {
  $items = array_slice($items, 0, $limit);
}

// 5) Read top-level props
$header_title = (string)($A['title'] ?? '');
$description  = (string)($A['intro'] ?? '');
$show_read    = !empty($A['showReadLink']);
$global_read  = (string)($A['readLinkText'] ?? 'Read more');
$cta_text     = (string)($A['ctaText'] ?? '');
$cta_url      = (string)($A['ctaUrl']  ?? '');
$animation_on = (string)($A['animation'] ?? 'Off');
$belt_class   = ($animation_on === 'On') ? ' belt' : '';
$belt_id_attr = ($animation_on === 'On') ? ' id="belt"' : '';
// Layout variant
$design       = isset($A['design']) ? strtolower(trim((string)$A['design'])) : 'left';
$design_class = ($design === 'top') ? ' food-card--top' : '';

?>
<div class="container-fluid">
  <section class="food-section">
    <header class="section-header">
      <?php if ($header_title) : ?>
        <h2 class="section-title"><?php echo esc_html($header_title); ?></h2>
      <?php endif; ?>

      <?php if ($description) : ?>
        <p class="section-subtitle"><?php echo esc_html($description); ?></p>
      <?php endif; ?>
    </header>

    <div class="row g-4 justify-content-center mt-2"></div>
     
    <?php if (!empty($items)) : ?>
      <?php
        // columns similar to carousel block
        $cols = ['xs'=>1,'sm'=>2,'md'=>3,'lg'=>3,'xl'=>4];
        $bps  = ['xs','sm','md','lg','xl'];
        $rowColsPieces = [];
        foreach ($bps as $bp) {
          $n = (int)($cols[$bp] ?? 0);
          if ($n > 0) { $rowColsPieces[] = ($bp === 'xs') ? "row-cols-$n" : "row-cols-$bp-$n"; }
        }
        $rowColsClasses = 'row ' . implode(' ', $rowColsPieces) . ' g-3 g-md-4';
        $showIndicators = !empty($A['showIndicators']);
        $showControls   = !empty($A['showControls']);

        // Build slides and id
        $largest = 1; foreach ($bps as $bp) { if (!empty($cols[$bp])) $largest = max($largest, (int)$cols[$bp]); }
        $perSlide = max(1, $largest);
        $slides = array_chunk($items, $perSlide);
        $id = 'cardimg_car_' . uniqid();
      ?>

      <div id="<?php echo esc_attr($id); ?>" class="carousel slide generic-carousel cardimg-carousel" data-bs-ride="false">
        <?php $instance_id = 'cardimg_' . uniqid(); ?>
        <div class="food-grid-wrap" data-cardimg="<?php echo esc_attr($instance_id); ?>">
          <div class="food-grid<?php echo $belt_class; ?>"<?php echo $belt_id_attr; ?>>
          <?php foreach ($items as $card) :
            $img        = $card['image']   ?? '';
            $h          = $card['heading'] ?? '';
            $alt          = $card['alt']    ?? '';
            $t          = $card['text']    ?? '';
            $link       = $card['__url']   ?? '';
            $link_title = ($card['__title'] ?? '') ?: $global_read;
          ?>
            <?php
              $tag = $h ? 'article' : 'div';
            ?>
            <<?php echo $tag; ?> class="food-card<?php echo $design_class; ?>">
              <figure class="food-card__media">
                <?php if ($img) : ?>
                  <img
                    src="<?php echo esc_url($img); ?>"
                    alt="<?php echo esc_attr($alt ?: ''); ?>"
                    loading="lazy"
                    decoding="async"
                  />
                <?php else : ?>
                  <div class="food-card__media--empty" aria-hidden="true"></div>
                <?php endif; ?>
              </figure>

              <div class="food-card__content">
                <?php if ($h) : ?>
                  <h3 class="food-card__title"><?php echo esc_html($h); ?></h3>
                <?php endif; ?>

                <?php if ($t) : ?>
                  <p class="food-card__text"><?php echo esc_html($t); ?></p>
                <?php endif; ?>

                <?php if ($show_read && $link) : ?>
                  <a class="food-card__link" href="<?php echo esc_url($link); ?>">
                    <?php echo esc_html($link_title); ?>
                  </a>
                <?php endif; ?>
              </div>
            </<?php echo $tag; ?>>
          <?php endforeach; ?>
          </div>
          <button class="cardimg-control cardimg-prev" type="button" aria-label="Previous" tabindex="0">&#10094;</button>
          <button class="cardimg-control cardimg-next" type="button" aria-label="Next" tabindex="0">&#10095;</button>

          <?php 
          
          if ($showIndicators && count($items) > 1): ?>
            <div class="carousel-indicators">
              <?php foreach ($items as $i => $_): ?>
                <button type="button"
                  data-bs-target="#<?php echo esc_attr($id); ?>"
                  data-bs-slide-to="<?php echo esc_attr($i); ?>"
                  <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?>
                  aria-label="<?php echo esc_attr(sprintf('Slide %d', $i + 1)); ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <script>
        (function(){
          try {
            var wrap = document.querySelector('[data-cardimg="<?php echo esc_js($instance_id); ?>"]');
            if(!wrap) return;
            var grid = wrap.querySelector('.food-grid');
            if(!grid) return;
            var prevBtn = wrap.querySelector('.cardimg-prev');
            var nextBtn = wrap.querySelector('.cardimg-next');
            var carousel = wrap.closest('.cardimg-carousel');
            var dots = carousel ? carousel.querySelectorAll('.carousel-indicators button') : [];
            var perSlide = 1; // indicators map to items, not pages

            function getStep(){
              var first = grid.querySelector('.food-card');
              if(first){
                var r = first.getBoundingClientRect();
                // try to read gap from computed styles
                var gap = 16;
                try {
                  var cs = getComputedStyle(grid);
                  var g = parseFloat(cs.columnGap || cs.gap || '16');
                  if(!isNaN(g)) gap = g;
                } catch(e){}
                return Math.max(100, r.width + gap);
              }
              return Math.max(120, grid.clientWidth * 0.9);
            }

            function scrollLeft(){ grid.scrollBy({ left: -getStep(), behavior: 'smooth' }); }
            function scrollRight(){ grid.scrollBy({ left: getStep(), behavior: 'smooth' }); }

            if(prevBtn) prevBtn.addEventListener('click', scrollLeft);
            if(nextBtn) nextBtn.addEventListener('click', scrollRight);

            // Keyboard accessibility
            if(prevBtn) prevBtn.addEventListener('keydown', function(e){ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); scrollLeft(); }});
            if(nextBtn) nextBtn.addEventListener('keydown', function(e){ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); scrollRight(); }});

            function setActiveDot(idx){
              if(!dots || !dots.length) return;
              for (var i=0;i<dots.length;i++) {
                var d = dots[i]; if(!d) continue;
                if(i === idx){ d.classList.add('active'); d.setAttribute('aria-current','true'); }
                else { d.classList.remove('active'); d.setAttribute('aria-current','false'); }
              }
            }

            // Dot clicks -> scroll
            if(dots && dots.length){
              for (let i=0;i<dots.length;i++){
                const d = dots[i];
                d.addEventListener('click', function(){
                  var step = getStep();
                  grid.scrollTo({ left: i * step, behavior: 'smooth' });
                  setActiveDot(i);
                });
              }
            }

            // Update active dot on scroll
            var raf = null;
            grid.addEventListener('scroll', function(){
              if(!dots || !dots.length) return;
              if(raf) return;
              raf = requestAnimationFrame(function(){
                raf = null;
                var step = getStep();
                var idx = Math.round(grid.scrollLeft / step);
                idx = Math.max(0, Math.min(idx, dots.length - 1));
                setActiveDot(idx);
              });
            });
          } catch(e){}
        })();
        </script>
      </div>
    <?php endif; ?>
  </section>
</div>