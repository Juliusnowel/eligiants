<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$headline       = trim( $attributes['headline']      ?? '' );
$paragraphOne   = trim( $attributes['paragraphOne']  ?? '' );
$paragraphTwo   = trim( $attributes['paragraphTwo']  ?? '' );

$buttonText     = trim( $attributes['buttonText']    ?? '' );
$buttonUrl      =        $attributes['buttonUrl']    ?? '#';
$buttonAccent   =        $attributes['buttonAccent'] ?? '#FD593C';
$ctaAlignRaw    =        $attributes['ctaAlign']     ?? 'right';
$ctaAlign       = in_array($ctaAlignRaw, ['left','right','hidden'], true) ? $ctaAlignRaw : 'right';

$imagePosition  = in_array(($attributes['imagePosition'] ?? 'right'), ['left','right'], true)
                  ? $attributes['imagePosition'] : 'right';

$sectionBg      = trim((string)($attributes['backgroundColor'] ?? ''));

// image: bg + fg, with fallback to legacy imageUrl/imageAlt
$imageBgUrl     = trim((string)($attributes['imageBgUrl'] ?? ''));
$imageBgAlt     = trim((string)($attributes['imageBgAlt'] ?? ''));
$imageFgUrl     = trim((string)($attributes['imageFgUrl'] ?? ''));
$imageFgAlt     = trim((string)($attributes['imageFgAlt'] ?? ''));

if ($imageFgUrl === '' && !empty($attributes['imageUrl'])) {
    $imageFgUrl = (string)$attributes['imageUrl'];
}
if ($imageFgAlt === '' && !empty($attributes['imageAlt'])) {
    $imageFgAlt = (string)$attributes['imageAlt'];
}

$imgRadius      = isset($attributes['imageBorderRadius']) ? (int)$attributes['imageBorderRadius'] : 24;

$textBg         = trim((string)($attributes['textBgColor'] ?? ''));
$textPadY       = is_numeric($attributes['textPadY'] ?? null) ? (int)$attributes['textPadY'] : 28;
$textRadius     = isset($attributes['textBorderRadius']) ? (int)$attributes['textBorderRadius'] : 16;

$decor          = is_array($attributes['decor']     ?? null) ? $attributes['decor']     : [];
$textDecor      = is_array($attributes['textDecor'] ?? null) ? $attributes['textDecor'] : [];

// animation
$animate        = $attributes['animate'] ?? 'none';
$animate        = in_array($animate, ['none','view','hover'], true) ? $animate : 'none';
$startScale     = isset($attributes['startScale'])     ? (float)$attributes['startScale']     : 1;
$animDurationMs = isset($attributes['animDurationMs']) ? (int)$attributes['animDurationMs']   : 1200;
$animDelayMs    = isset($attributes['animDelayMs'])    ? (int)$attributes['animDelayMs']      : 0;

$align_class  = isset($attributes['align']) ? 'align' . $attributes['align'] : '';
$pos_class    = $imagePosition === 'left' ? 'is-img-left' : 'is-img-right';
$hasSectionBg = $sectionBg !== '';

$wrapper_attributes = get_block_wrapper_attributes([
  'class' => 'card-2-text-img-resp child-block ' . $align_class . ' ' . $pos_class . ( $hasSectionBg ? ' has-section-bg' : '' ),
  'style' => ($hasSectionBg ? '--card-bg:' . esc_attr($sectionBg) . ';' : '')
]);

ob_start(); ?>
<div <?= $wrapper_attributes; ?>>

  <?php if ( ! empty($decor) ) :
    foreach ( $decor as $d ) :
      $url  = isset($d['url'])  ? esc_url($d['url'])  : '';
      if ($url === '') { continue; }
      $alt  = isset($d['alt'])  ? esc_attr($d['alt']) : '';
      $side = (isset($d['side']) && in_array($d['side'], ['left','right'], true)) ? $d['side'] : 'right';
      $side_class = 'card-2-text-img-resp__decor--' . $side; ?>
      <img class="card-2-text-img-resp__decor <?= esc_attr($side_class); ?>" src="<?= $url; ?>" alt="<?= $alt; ?>" loading="lazy" decoding="async" />
  <?php endforeach; endif; ?>

  <div class="card-2-text-img-resp__inner">

    <div class="card-2-text-img-resp__col card-2-text-img-resp__text"
         style="--panel-bg: <?= esc_attr($textBg ?: 'transparent'); ?>;
                --panel-radius: <?= esc_attr($textRadius); ?>px;
                --panel-pad-y: <?= esc_attr($textPadY); ?>px;">
      <?php
        $panel_classes = 'card-2-text-img-resp__panel';
        $panel_classes .= ($textBg === '' ? ' is-transparent' : ' has-bg');
      ?>
      <div class="<?= esc_attr($panel_classes); ?>">
        <?php if ( ! empty($textDecor) ) :
          foreach ( $textDecor as $td ) :
            $turl = isset($td['url']) ? esc_url($td['url']) : '';
            if ($turl === '') { continue; }
            $talt = isset($td['alt']) ? esc_attr($td['alt']) : '';
            $pos  = isset($td['pos']) ? strtolower($td['pos']) : 'tl';
            $pos  = in_array($pos, ['tl','tr','bl','br'], true) ? $pos : 'tl'; ?>
            <img class="card-2-text-img-resp__panel-decor card-2-text-img-resp__panel-decor--<?= esc_attr($pos); ?>"
                 src="<?= $turl; ?>" alt="<?= $talt; ?>" loading="lazy" decoding="async" />
        <?php endforeach; endif; ?>

        <?php if ($headline !== ''): ?>
          <h2 class="card-2-text-img-resp__headline"><?= esc_html($headline); ?></h2>
        <?php endif; ?>

        <?php if ($paragraphOne !== ''): ?>
          <p class="card-2-text-img-resp__copy"><?= wp_kses_post($paragraphOne); ?></p>
        <?php endif; ?>

        <?php if ($paragraphTwo !== ''): ?>
          <p class="card-2-text-img-resp__copy"><?= wp_kses_post($paragraphTwo); ?></p>
        <?php endif; ?>

        <?php if ($buttonText !== '' && $ctaAlign !== 'hidden'): ?>
          <div class="card-2-text-img-resp__cta-wrap card-2-text-img-resp__cta-wrap--<?= esc_attr($ctaAlign); ?>">
            <?= render_block([
              'blockName'   => 'ilegiants/cta-bounce',
              'attrs'       => [ 'text' => $buttonText, 'url' => ($buttonUrl ?: '#'), 'accent' => $buttonAccent ],
              'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []
            ]); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-2-text-img-resp__col card-2-text-img-resp__media">
      <?php if ($imageBgUrl || $imageFgUrl): ?>
        <?php
          $figure_classes = 'card-2-text-img-resp__figure';
          if ($animate === 'view') {
            $figure_classes .= ' is-anim-view';
          } elseif ($animate === 'hover') {
            $figure_classes .= ' is-anim-hover';
          }
        ?>
        <figure
          class="<?= esc_attr($figure_classes); ?>"
          style="--img-radius: <?= esc_attr($imgRadius); ?>px;
                --fg-start-scale: <?= esc_attr($startScale); ?>;
                --anim-duration: <?= esc_attr($animDurationMs); ?>ms;
                --anim-delay: <?= esc_attr($animDelayMs); ?>ms;">

          <?php if ($imageBgUrl): ?>
            <img class="card-2-text-img-resp__image-bg"
                 src="<?= esc_url($imageBgUrl); ?>"
                 alt="<?= esc_attr($imageBgAlt); ?>"
                 loading="lazy" decoding="async" />
          <?php endif; ?>

          <?php if ($imageFgUrl): ?>
            <img class="card-2-text-img-resp__image-fg"
                 src="<?= esc_url($imageFgUrl); ?>"
                 alt="<?= esc_attr($imageFgAlt); ?>"
                 loading="lazy" decoding="async" />
          <?php endif; ?>
        </figure>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php
// Attach a small IntersectionObserver once per page for "view" animation
?>
<script>
(function(){
  if (window.card2TextImgAnimInit) return;
  window.card2TextImgAnimInit = true;

  var figures = document.querySelectorAll('.card-2-text-img-resp__figure.is-anim-view');
  if (!('IntersectionObserver' in window) || !figures.length) {
    figures.forEach(function(el){ el.classList.add('is-in-view'); });
    return;
  }

  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if (entry.isIntersecting) {
        entry.target.classList.add('is-in-view');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.25 });

  figures.forEach(function(el){ io.observe(el); });
})();
</script>
