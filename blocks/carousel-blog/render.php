<?php
if ( ! defined('ABSPATH') ) { exit; }

/** Helpers */
if (!function_exists('cb_map_terms_to_ids')) {
function cb_map_terms_to_ids($maybe_ids_or_slugs, $taxonomy){
  if (!is_array($maybe_ids_or_slugs) || empty($maybe_ids_or_slugs)) return [];
  $ids = [];
  foreach ($maybe_ids_or_slugs as $t) {
    if (is_numeric($t)) { $ids[] = intval($t); continue; }
    $term = get_term_by('slug', sanitize_title($t), $taxonomy);
    if ($term && ! is_wp_error($term)) $ids[] = intval($term->term_id);
  }
  return array_values(array_unique(array_filter($ids)));
}
}
if (!function_exists('cb_render_dotlist')) {
  function cb_render_dotlist($uid, $ids, $per_view, $suffix){
    $n = count($ids);
    $per = max(1, intval($per_view));
    $pages = (int) ceil($n / $per);
    if ($pages < 1) return;
    echo '<ol class="carousel-blog__dots carousel-blog__dots--' . esc_attr($suffix) . '" role="tablist" aria-label="Select slide">';
  for ($p = 0; $p < $pages; $p++) {
    $center_index = min($n - 1, $p * $per + (int) floor(($per - 1) / 2));
    $target_id = $ids[$center_index];
    echo '<li><a class="carousel-blog__dot" role="tab" href="#' . esc_attr($target_id) . '" aria-label="' . esc_attr(sprintf(__('Go to page %d','childtheme'), $p+1)) . '"></a></li>';
  }
  echo '</ol>';
}
}

/** Attributes */
$uid         = 'cb-' . wp_unique_id();
$heading     = (string)($attributes['heading'] ?? '');
$subheading  = (string)($attributes['subheading'] ?? '');
$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';

$source      = $attributes['source']      ?? 'query';
$postsToShow = intval($attributes['postsToShow'] ?? 6);
$order       = in_array(($attributes['order'] ?? 'DESC'), ['ASC','DESC'], true) ? $attributes['order'] : 'DESC';
$orderBy     = in_array(($attributes['orderBy'] ?? 'date'), ['date','modified','title','rand'], true) ? $attributes['orderBy'] : 'date';
$imageSize   = (string)($attributes['imageSize'] ?? 'large');

$per_m = max(1, intval($attributes['perViewMobile']  ?? 1));
$per_t = max(1, intval($attributes['perViewTablet']  ?? 2));
$per_d = max(1, intval($attributes['perViewDesktop'] ?? 3));

$imageIcon         = esc_url((string)($attributes['imageIcon'] ?? ''));
$cardBackgroundColor = sanitize_hex_color_no_hash($attributes['cardBackgroundColor'] ?? ''); // Use sanitize_hex_color for safety, assuming it's a hex code
$background_style  = $cardBackgroundColor ? 'style="--card-bg: #' . $cardBackgroundColor . ';"' : '';

/** Build slides */
$slides = [];
if ($source === 'manual') {
  $items = is_array($attributes['items'] ?? null) ? $attributes['items'] : [];
  foreach ($items as $it) {
    $slides[] = [
      'title'   => (string)($it['title'] ?? ''),
      'excerpt' => (string)($it['excerpt'] ?? ''),
      'image'   => esc_url_raw((string)($it['imageUrl'] ?? '')),
      'url'     => esc_url_raw((string)($it['linkUrl'] ?? '#')),
    ];
  }
} else {
  $cat_ids = cb_map_terms_to_ids($attributes['categories'] ?? [], 'category');
  $tag_ids = cb_map_terms_to_ids($attributes['tags'] ?? [], 'post_tag');
  $args = [
    'post_type'           => 'post',
    'posts_per_page'      => max(1, $postsToShow),
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
    'orderby'             => $orderBy,
    'order'               => $order,
  ];
  if ($cat_ids) $args['category__in'] = $cat_ids;
  if ($tag_ids) $args['tag__in']      = $tag_ids;

  $q = new WP_Query($args);
  if ($q->have_posts()) {
    foreach ($q->posts as $p) {
      $slides[] = [
        'title'   => get_the_title($p),
        'excerpt' => wp_strip_all_tags(get_the_excerpt($p)),
        'image'   => get_the_post_thumbnail_url($p, $imageSize) ?: '',
        'url'     => get_permalink($p),
      ];
    }
  }
  wp_reset_postdata();
}
$count = count($slides);
if ($count === 0) { return ''; }

/** Wrapper */
$wrapper_attributes = get_block_wrapper_attributes([
  'class' => 'carousel-blog ' . $align_class . ' child-block',
  'id'    => $uid,
]);

// Precompute slide IDs
$ids = [];
for ($i=0; $i<$count; $i++) $ids[$i] = $uid . '-s' . ($i+1);
?>
<div <?php echo $wrapper_attributes; ?> <?php echo $background_style; ?>>

  <?php if ($heading || $subheading): ?>
    <div class="carousel-blog__header">
      <?php if ($heading): ?><h2 class="carousel-blog__title"><?php echo esc_html($heading); ?></h2><?php endif; ?>
      <?php if ($subheading): ?><p class="carousel-blog__sub"><?php echo wp_kses_post($subheading); ?></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php // --- NEW: Image Icon Display (placed inside the main wrapper) --- ?>
  <?php if ($imageIcon): ?>
    <img class="carousel-blog__icon" src="<?php echo $imageIcon; ?>" alt="" loading="lazy" decoding="async" aria-hidden="true" />
  <?php endif; ?>
  <?php // ----------------------------------------------------------------- ?>

  <div class="carousel-blog__viewport" role="region" aria-label="<?php esc_attr_e('Blog Carousel','childtheme'); ?>">
    <div class="carousel-blog__track">
      <?php foreach ($slides as $i => $s):
        $slide_id = $ids[$i];
        $prev_id  = $ids[ ($i - 1 + $count) % $count ];
        $next_id  = $ids[ ($i + 1) % $count ];
      ?>
        <article id="<?php echo esc_attr($slide_id); ?>" class="carousel-blog__slide" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr(($i+1) . ' of ' . $count); ?>">
          <?php if (!empty($s['image'])): ?>
            <img class="carousel-blog__img" src="<?php echo esc_url($s['image']); ?>" alt="" loading="lazy" decoding="async" />
          <?php else: ?>
            <div class="carousel-blog__img carousel-blog__img--placeholder" aria-hidden="true"></div>
          <?php endif; ?>

          <div class="carousel-blog__overlay">
            <?php if (!empty($s['title'])): ?><h3 class="carousel-blog__card-title"><?php echo esc_html($s['title']); ?></h3><?php endif; ?>
            <?php if (!empty($s['excerpt'])): ?><p class="carousel-blog__excerpt"><?php echo esc_html(wp_trim_words($s['excerpt'], 24)); ?></p><?php endif; ?>
            <p class="carousel-blog__cta">
              <?php
                echo render_block([
                  'blockName'   => 'ilegiants/cta-wave-card',
                  'attrs'       => [
                    'text'    => __('Read More','childtheme'),
                    'url'     => $s['url'] ?? '#',
                    'context' => 'on-image',
                    'size'    => 'md',
                    'accent'  => '#FD593C'
                  ],
                  'innerBlocks'  => [],
                  'innerHTML'    => '',
                  'innerContent' => []
                ]);
              ?>
            </p>
          </div>
        </article>

        <!-- Per-slide navset; intercepted by JS for precise 1-card moves -->
        <div class="carousel-blog__navset" aria-hidden="true">
          <a class="carousel-blog__arrow carousel-blog__arrow--prev" href="#<?php echo esc_attr($prev_id); ?>" aria-label="<?php esc_attr_e('Previous slide','childtheme'); ?>"></a>
          <a class="carousel-blog__arrow carousel-blog__arrow--next" href="#<?php echo esc_attr($next_id); ?>" aria-label="<?php esc_attr_e('Next slide','childtheme'); ?>"></a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php
    // Responsive page dots
    cb_render_dotlist($uid, $ids, $per_m, 'm'); // mobile
    cb_render_dotlist($uid, $ids, $per_t, 't'); // tablet
    cb_render_dotlist($uid, $ids, $per_d, 'd'); // desktop
  ?>

  <?php
  // Scoped active-dot styling using :has(); default = first when no :target
  ?>
  <style>
    /* default active dot = first when no target */
    #<?php echo esc_attr($uid); ?>:not(:has(.carousel-blog__slide:target))
      .carousel-blog__dot[href="#<?php echo esc_attr($ids[0]); ?>"]{
        background: var(--dot-active);
    }
    /* active dot mirrors targeted slide */
    <?php foreach ($ids as $sid): ?>
    #<?php echo esc_attr($uid); ?>:has(#<?php echo esc_attr($sid); ?>:target)
      .carousel-blog__dot[href="#<?php echo esc_attr($sid); ?>"]{
        background: var(--dot-active);
    }
    <?php endforeach; ?>
  </style>

  <script>
    (function(){
      const root  = document.getElementById(<?php echo wp_json_encode($uid); ?>);
      if(!root) return;

      const track  = root.querySelector('.carousel-blog__track');
      const slides = Array.from(root.querySelectorAll('.carousel-blog__slide'));
      if(!track || !slides.length) return;

      const vCenter = () => { const r = track.getBoundingClientRect(); return r.left + r.width/2; };
      const sCenter = el => { const r = el.getBoundingClientRect(); return r.left + r.width/2; };

      function visibleDots(){
        const lists = Array.from(root.querySelectorAll('.carousel-blog__dots'));
        const visibleList = lists.find(ol => getComputedStyle(ol).display !== 'none') || lists[0];
        return { list: visibleList, dots: Array.from(visibleList ? visibleList.querySelectorAll('.carousel-blog__dot') : []) };
      }
      function perView(){
        const vd = visibleDots().dots.length || 1;
        return Math.max(1, Math.ceil(slides.length / vd));
      }

      function nearestIndex(){
        const vc = vCenter(); let best = 0, dist = Infinity;
        slides.forEach((s,i) => { const d = Math.abs(sCenter(s) - vc); if (d < dist){ dist = d; best = i; }});
        return best;
      }

      function syncClasses(idx){
        const { dots } = visibleDots();
        const per  = perView();
        const page = Math.min(dots.length - 1, Math.floor(idx / per));
        slides.forEach(s => s.classList.remove('is-current'));
        slides[idx].classList.add('is-current');

        root.querySelectorAll('.carousel-blog__dot.is-active').forEach(d => d.classList.remove('is-active'));
        if (dots[page]) dots[page].classList.add('is-active');
      }

      // IMPORTANT: do NOT use location.hash (causes jump). Use History API or nothing.
      function centerIndex(idx){
        idx = Math.max(0, Math.min(slides.length - 1, idx));
        const el = slides[idx];
        const tr = track.getBoundingClientRect();
        const sl = el.getBoundingClientRect();
        const delta = (sl.left - tr.left) - (tr.width/2 - sl.width/2);
        track.scrollBy({ left: delta, behavior: 'smooth' });
        syncClasses(idx);
        // no history.replaceState here
      }

      // 2) Click handling – remove the `true` flag
      root.addEventListener('click', function(e){
        const isDot  = e.target.closest('.carousel-blog__dot');
        const isPrev = e.target.closest('.carousel-blog__arrow--prev');
        const isNext = e.target.closest('.carousel-blog__arrow--next');

        if (!isDot && !isPrev && !isNext) return;
        e.preventDefault();

        if (isDot){
          const id  = isDot.getAttribute('href');
          const idx = slides.findIndex(s => '#'+s.id === id);
          if (idx !== -1) centerIndex(idx);       // was centerIndex(idx, true)
          return;
        }
        const cur = nearestIndex();
        centerIndex(cur + (isNext ? 1 : -1));    // was centerIndex(..., true)
      });

      // 3) Keep deep-link-on-load (optional)
      const initIdx = slides.findIndex(s => '#'+s.id === location.hash);
      centerIndex(initIdx >= 0 ? initIdx : 0);

      let t;
      track.addEventListener('scroll', function(){
        clearTimeout(t);
        t = setTimeout(() => syncClasses(nearestIndex()), 80);
      }, { passive: true });
      window.addEventListener('resize', () => syncClasses(nearestIndex()));
    })();
  </script>
</div>
