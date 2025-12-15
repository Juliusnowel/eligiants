<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$defaults = [
  'title'        => 'Travel Smarter, Wander Freely',
  'subtitle'     => 'Smart Prep, Stress-Free Adventures',
  'tagline'      => 'Whether travelers are setting out on their first getaway or a long-awaited escape, the right strategies make every trip smoother.',
  'ctaText'      => "Let's Go!",
  'ctaUrl'       => '#',
  'overlay'      => 0.45,
  'minHeight'    => 520,
  'accent'       => 'tomato',
  'titleShadow'  => true,
  'images'       => [],
  'interval'     => 5000,
  'fullHeight'   => true,
  'headerHeight' => 0,
  'underHeader'  => true,
  'animation'    => true,
];

$post_id = $context['postId'] ?? get_the_ID();
$attrs   = wp_parse_args( $attributes ?? [], $defaults );

/* Background seed */
$bg_url = '';
if ( empty( $attrs['images'] ) ) {
  if ( $post_id && has_post_thumbnail( $post_id ) ) {
    $img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
    if ( ! empty( $img[0] ) ) { $bg_url = $img[0]; }
  }
  if ( ! $bg_url ) {
    $bg_url = 'data:image/svg+xml;utf8,' . rawurlencode(
      '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f7e0c3"/><stop offset="1" stop-color="#b7c6d9"/></linearGradient></defs><rect width="100%" height="100%" fill="url(#g)"/></svg>'
    );
  }
}

/* Unique ID */
$uid_func = function_exists('wp_unique_id') ? 'wp_unique_id' : 'uniqid';
$uid = 'hero-' . $uid_func();

/* Height tokens: 100vh background; content is padded under header */
$min_h_css = $attrs['fullHeight'] ? '100vh' : ((int)$attrs['minHeight'] . 'px');
$classes   = 'hero-banner'
           . ( $attrs['fullHeight']   ? ' is-fullheight'   : '' )
           . ( $attrs['underHeader']  ? ' is-under-header' : '' );

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

$title        = $attrs['title'];
$subtitle     = $attrs['subtitle'] ?? '';
$tagline      = $attrs['tagline'];
$cta_text   = $attrs['ctaText'] ?? '';
$cta_url    = $attrs['ctaUrl']  ?? '#';
$cta2_text  = $attrs['cta2Text'] ?? '';
$cta2_url   = $attrs['cta2Url']  ?? '#';
$cta2_accent= $attrs['cta2Accent'] ?: ($attrs['accent'] ?? '#FD593C');
$cta_layout = (isset($attrs['ctaLayout']) && $attrs['ctaLayout'] === 'stack') ? 'stack' : 'row';
$title_shadow = ! empty( $attrs['titleShadow'] ) ? ' has-title-shadow' : '';

$images   = array_values( array_filter( (array) $attrs['images'] ) );
$interval = max( 1000, (int) $attrs['interval'] );
$animate  = $attrs['animation'] ? 'true' : 'false';
?>
<div <?php echo $wrapper; ?> id="<?php echo esc_attr( $uid ); ?>"
     data-images="<?php echo esc_attr( wp_json_encode( $images ) ); ?>"
     data-interval="<?php echo esc_attr( $interval ); ?>"
     data-animate="<?php echo esc_attr( $animate ); ?>">
  <figure class="hero-banner__media" style="background-image:url('<?php echo esc_url( $images[0] ?? $bg_url ); ?>');" aria-hidden="true"></figure>

  <div class="hero-banner__inner">
    <div class="hero-banner__scrim">
      <?php if ( $title ) : ?>
        <h1 class="hero-banner__title<?php echo esc_attr( $title_shadow ); ?>">
          <span class="hero-banner__title-text"><?php echo wp_kses_post( $title ); ?></span>
        </h1>
        <div class="hero-banner__rule" aria-hidden="true"></div>
      <?php endif; ?>

      <?php if ( $subtitle ) : ?>                             
        <h3 class="hero-banner__subtitle"><?php echo wp_kses_post( $subtitle ); ?></h3>
      <?php endif; ?>

      <?php if ( $tagline ) : ?>
        <p class="hero-banner__tagline"><?php echo wp_kses_post( $tagline ); ?></p>
      <?php endif; ?>

      <?php if ( $cta_text || $cta2_text ) : ?>
        <div class="hero-banner__ctas hero-banner__ctas--<?php echo esc_attr($cta_layout); ?>">
          <?php if ( $cta_text ) : ?>
            <?= render_block([
              'blockName'  => 'ilegiants/cta-bounce',
              'attrs'      => [ 'text' => $cta_text, 'url' => ($cta_url ?: '#'), 'accent' => ($attrs['accent'] ?? '#FD593C') ],
              'innerBlocks'=> [], 'innerHTML' => '', 'innerContent' => []
            ]); ?>
          <?php endif; ?>

          <?php if ( $cta2_text ) : ?>
            <?= render_block([
              'blockName'  => 'ilegiants/cta-bounce',
              'attrs'      => [ 'text' => $cta2_text, 'url' => ($cta2_url ?: '#'), 'accent' => $cta2_accent ],
              'innerBlocks'=> [], 'innerHTML' => '', 'innerContent' => []
            ]); ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  var root = document.getElementById('<?php echo esc_js( $uid ); ?>');
  if(!root) return;

  var media = root.querySelector('.hero-banner__media');
  var imgs = [];
  try { imgs = JSON.parse(root.getAttribute('data-images') || '[]'); } catch(e){}
  var interval = Math.max(1000, parseInt(root.getAttribute('data-interval') || '5000', 10));
  var shouldAnimate = root.getAttribute('data-animate') !== 'false';

  function restartZoom(){
    media.style.setProperty('--cycle', interval + 'ms');      // keep CSS in sync
    media.style.animation = 'none';
    media.style.transform = 'scale(1)';                       // snap back to start
    void media.offsetWidth;                                   // reflow to restart
    if (shouldAnimate) {
      media.style.animation = 'heroZoomIn ' + interval + 'ms linear forwards';
    }
  }

  // initial run
  restartZoom();

  var i = 0;
  setInterval(function(){
    if (imgs.length > 1){
      i = (i + 1) % imgs.length;
      media.style.backgroundImage = 'url(' + imgs[i] + ')';
    }
    restartZoom();  // one-way zoom every cycle
  }, interval);
})();
</script>


