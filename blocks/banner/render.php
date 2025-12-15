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
  'overlay'      => 0.65,
  'accent'       => 'tomato',
  'titleShadow'  => true,
  'bgColor'         => '',
  'bgImageUrl'      => '',
  'bgImagePosition' => 'center center',
  'minHeight'       => 0,
  'footerUrl'       => '',
  'footerLabel'  => '',
];

$attrs = wp_parse_args( $attributes ?? [], $defaults );

/* wrapper classes + custom-prop style */
$classes    = 'banner-strip child-block';
$cta_layout = ($attrs['ctaLayout'] === 'stack') ? 'stack' : 'row';

$style = sprintf(
  '--accent:%1$s;--overlay:%2$s;',
  esc_attr( $attrs['accent'] ),
  esc_attr( $attrs['overlay'] )
);

/* background + min-height inline styles */
$bgColor = trim( (string) $attrs['bgColor'] );
$bgImg   = trim( (string) $attrs['bgImageUrl'] );
$bgPos   = trim( (string) $attrs['bgImagePosition'] ?: 'center center' );

if ( $bgColor !== '' ) {
  $style .= 'background-color:' . esc_attr( $bgColor ) . ';';
}

if ( $bgImg !== '' ) {
  $style .= "background-image:url('" . esc_url( $bgImg ) . "');";
  $style .= 'background-size:cover;';
  $style .= 'background-position:' . esc_attr( $bgPos ) . ';';
  $style .= 'background-repeat:no-repeat;';
}

$min_h = (int) $attrs['minHeight'];
if ( $min_h > 0 ) {
  $style .= 'min-height:' . $min_h . 'px;';
}

$wrapper = get_block_wrapper_attributes([
  'class' => $classes,
  'style' => $style,
]);

$title        = $attrs['title'] ?? '';
$subtitle     = $attrs['subtitle'] ?? '';
$tagline      = $attrs['tagline'] ?? '';
$text         = $attrs['text'] ?? ''; 
$cta_text     = $attrs['ctaText'] ?? '';
$cta_url      = $attrs['ctaUrl']  ?? '#';
$cta2_text    = $attrs['cta2Text'] ?? '';
$cta2_url     = $attrs['cta2Url']  ?? '#';
$cta2_accent  = $attrs['cta2Accent'] ?: '#ffffff';
$title_shadow = ! empty( $attrs['titleShadow'] ) ? ' has-title-shadow' : '';
?>
<div <?php echo $wrapper; ?>>
  <div class="banner-strip__outer">
    <div class="banner-strip__inner">
      <?php if ( $title ) : ?>
        <h2 class="banner-strip__title<?php echo esc_attr( $title_shadow ); ?>">
          <p class="banner-strip__title-text">
            <?php echo wp_kses_post( $title ); ?>
          </p>
        </h2>
        <div class="banner-strip__rule" aria-hidden="true"></div>
      <?php endif; ?>

      <?php if ( $subtitle ) : ?>
        <h3 class="banner-strip__subtitle">
          <?php echo wp_kses_post( $subtitle ); ?>
        </h3>
      <?php endif; ?>

      <?php if ( $tagline ) : ?>
        <p class="banner-strip__tagline">
          <?php echo wp_kses_post( $tagline ); ?>
        </p>
      <?php endif; ?>

      <?php if ( $cta_text || $cta2_text ) : ?>
        <div class="banner-strip__ctas banner-strip__ctas--<?php echo esc_attr( $cta_layout ); ?>">
          <?php if ( $cta_text ) : ?>
            <?php
            echo render_block([
              'blockName'   => 'ilegiants/cta-bounce',
              'attrs'       => [
                'text'        => $cta_text,
                'url'         => ( $cta_url ?: '#' ),
                'accent'      => ( $attrs['accent'] ?? '#FD593C' ),
                'textColor'   => '#FFFFFF',
                'borderColor' => 'transparent',
              ],
              'innerBlocks' => [],
              'innerHTML'   => '',
              'innerContent'=> [],
            ]);
            ?>
          <?php endif; ?>

          <?php if ( $cta2_text ) : ?>
            <?php
            echo render_block([
              'blockName'   => 'ilegiants/cta-bounce',
              'attrs'       => [
                'text'        => $cta2_text,
                'url'         => ( $cta2_url ?: '#' ),
                'accent'      => $cta2_accent,
                'textColor'   => '#FD593C',
                'borderColor' => '#FFFFFF',
              ],
              'innerBlocks' => [],
              'innerHTML'   => '',
              'innerContent'=> [],
            ]);
            ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
    </div>
  </div>
</div>
