<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$defaults = [
  'title'        => '',
  'subtitle'     => '',
  'tagline'      => '',
  'ctaText'      => '',
  'ctaUrl'       => '#',
  'cta2Text'     => '',
  'cta2Url'      => '#',
  'cta2Accent'   => '',
  'ctaLayout'    => 'row',

  'overlay'      => 0.55,
  'minHeight'    => 520,
  'accent'       => 'tomato',
  'titleShadow'  => true,
  'images'       => [],
  'interval'     => 5000,
  'fullHeight'   => true,
  'headerHeight' => 0,
  'underHeader'  => true,

  'contentSide'   => 'left',
  'mobilePop'     => true,
  'zoomEnabled'   => true,
  'motionEnabled' => true,
];

$post_id = $context['postId'] ?? get_the_ID();
$attrs   = wp_parse_args( $attributes ?? [], $defaults );

/** Background seed */
$bg_url = '';
if ( empty( $attrs['images'] ) ) {
  if ( $post_id && has_post_thumbnail( $post_id ) ) {
    $img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
    if ( ! empty( $img[0] ) ) {
      $bg_url = $img[0];
    }
  }
  if ( ! $bg_url ) {
    $bg_url = 'data:image/svg+xml;utf8,' . rawurlencode(
      '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f7e0c3"/><stop offset="1" stop-color="#b7c6d9"/></linearGradient></defs><rect width="100%" height="100%" fill="url(#g)"/></svg>'
    );
  }
}

/** Unique ID */
$uid_func = function_exists( 'wp_unique_id' ) ? 'wp_unique_id' : 'uniqid';
$uid      = 'lp-' . $uid_func();

/** Height / layout tokens */
$min_h_css = ! empty( $attrs['fullHeight'] )
  ? '100vh'
  : ( (int) $attrs['minHeight'] . 'px' );

$classes   = 'lp-banner'
           . ( ! empty( $attrs['fullHeight'] )   ? ' is-fullheight'   : '' )
           . ( ! empty( $attrs['underHeader'] )  ? ' is-under-header' : '' )
           . ( ! empty( $attrs['zoomEnabled'] )  ? ' has-zoom'        : '' );

$style = sprintf(
  '--accent:%1$s;--overlay:%2$s;--min-h:%3$s;',
  esc_attr( $attrs['accent'] ),
  esc_attr( $attrs['overlay'] ),
  esc_attr( $min_h_css )
);

if ( (int) $attrs['headerHeight'] > 0 ) {
  $style .= sprintf( '--header-h:%dpx;', (int) $attrs['headerHeight'] );
}

$wrapper = get_block_wrapper_attributes( [
  'class' => $classes,
  'style' => $style,
] );

/** Content vars */
$title        = $attrs['title'];
$subtitle     = $attrs['subtitle'] ?? '';
$tagline      = $attrs['tagline'];
$cta_text     = $attrs['ctaText'] ?? '';
$cta_url      = $attrs['ctaUrl']  ?? '#';
$cta2_text    = $attrs['cta2Text'] ?? '';
$cta2_url     = $attrs['cta2Url']  ?? '#';
$cta2_accent  = $attrs['cta2Accent'] ?: ( $attrs['accent'] ?? '#FD593C' );
$cta_layout   = ( isset( $attrs['ctaLayout'] ) && $attrs['ctaLayout'] === 'stack' ) ? 'stack' : 'row';
$title_shadow = ! empty( $attrs['titleShadow'] ) ? ' has-title-shadow' : '';

$images       = array_values( array_filter( (array) $attrs['images'] ) );
$interval     = max( 1000, (int) $attrs['interval'] );

$content_side = strtolower( (string) $attrs['contentSide'] ) === 'right' ? 'right' : 'left';
$mobile_pop   = ! empty( $attrs['mobilePop'] ) ? '1' : '0';
$zoom_enabled = ! empty( $attrs['zoomEnabled'] ) ? '1' : '0';
$motion_en    = ! empty( $attrs['motionEnabled'] ) ? '1' : '0';

?>
<div
  <?php echo $wrapper; ?>
  id="<?php echo esc_attr( $uid ); ?>"
  data-position="<?php echo esc_attr( $content_side ); ?>"
  data-mobile-pop="<?php echo esc_attr( $mobile_pop ); ?>"
  data-zoom="<?php echo esc_attr( $zoom_enabled ); ?>"
  data-motion="<?php echo esc_attr( $motion_en ); ?>"
  data-images="<?php echo esc_attr( wp_json_encode( $images ) ); ?>"
  data-interval="<?php echo esc_attr( $interval ); ?>"
>
  <figure
    class="lp-banner__media"
    style="background-image:url('<?php echo esc_url( $images[0] ?? $bg_url ); ?>');"
    aria-hidden="true">
  </figure>

  <div class="lp-banner__inner">
    <div class="lp-banner__container">
      <div class="lp-banner__panel">
        <?php if ( $title ) : ?>
          <h1 class="lp-banner__title<?php echo esc_attr( $title_shadow ); ?>">
            <span class="lp-banner__title-text">
              <?php echo wp_kses_post( $title ); ?>
            </span>
          </h1>
          <div class="lp-banner__rule" aria-hidden="true"></div>
        <?php endif; ?>

        <?php if ( $subtitle ) : ?>
          <h3 class="lp-banner__subtitle">
            <?php echo wp_kses_post( $subtitle ); ?>
          </h3>
        <?php endif; ?>

        <?php if ( $tagline ) : ?>
          <p class="lp-banner__tagline">
            <?php echo wp_kses_post( $tagline ); ?>
          </p>
        <?php endif; ?>

        <?php if ( $cta_text || $cta2_text ) : ?>
          <div class="lp-banner__ctas lp-banner__ctas--<?php echo esc_attr( $cta_layout ); ?>">
            <?php if ( $cta_text ) : ?>
              <?php
              echo render_block( [
                'blockName'   => 'ilegiants/cta-bounce',
                'attrs'       => [
                  'text'   => $cta_text,
                  'url'    => ( $cta_url ?: '#' ),
                  'accent' => ( $attrs['accent'] ?? '#FD593C' ),
                ],
                'innerBlocks' => [],
                'innerHTML'   => '',
                'innerContent'=> [],
              ] );
              ?>
            <?php endif; ?>

            <?php if ( $cta2_text ) : ?>
              <?php
              echo render_block( [
                'blockName'   => 'ilegiants/cta-bounce',
                'attrs'       => [
                  'text'   => $cta2_text,
                  'url'    => ( $cta2_url ?: '#' ),
                  'accent' => $cta2_accent,
                ],
                'innerBlocks' => [],
                'innerHTML'   => '',
                'innerContent'=> [],
              ] );
              ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var root = document.getElementById('<?php echo esc_js( $uid ); ?>');
  if (!root) return;

  var media        = root.querySelector('.lp-banner__media');
  var imgs         = [];
  var interval     = Math.max(1000, parseInt(root.getAttribute('data-interval') || '5000', 10));
  var zoomEnabled  = root.getAttribute('data-zoom') === '1';
  var motionEnabled= root.getAttribute('data-motion') === '1';

  try {
    imgs = JSON.parse(root.getAttribute('data-images') || '[]');
  } catch(e) {}

  var prefersReduced =
    window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReduced) {
    zoomEnabled   = false;
    motionEnabled = false;
  }

  // BACKGROUND ZOOM
  if (zoomEnabled && media) {
    function restartZoom() {
      media.style.setProperty('--cycle', interval + 'ms');
      media.style.animation = 'none';
      media.style.transform = 'scale(1)';
      void media.offsetWidth;
      media.style.animation = 'lpZoomIn ' + interval + 'ms linear forwards';
    }

    restartZoom();

    var i = 0;
    setInterval(function(){
      if (imgs.length > 1) {
        i = (i + 1) % imgs.length;
        media.style.backgroundImage = 'url(' + imgs[i] + ')';
      }
      restartZoom();
    }, interval);
  } else if (media) {
    media.style.animation = 'none';
    media.style.transform = 'none';
  }

  // PANEL MOTION
  if (!motionEnabled) {
    root.classList.add('is-visible');
    return;
  }

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (entry.isIntersecting) {
          root.classList.add('is-visible');
          observer.disconnect();
        }
      });
    }, { threshold: 0.35 });

    observer.observe(root);
  } else {
    root.classList.add('is-visible');
  }
})();
</script>
