<?php
/**
 * Server-rendered "Card: 2-Column Text + Image"
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$headline      = trim($attributes['headline']      ?? '');
$paragraphOne  = trim($attributes['paragraphOne']  ?? '');
$paragraphTwo  = array_key_exists('paragraphTwo', $attributes) ? trim($attributes['paragraphTwo']) : '';
$buttonText    = array_key_exists('buttonText',   $attributes) ? trim($attributes['buttonText'])   : '';
$buttonUrl     = $attributes['buttonUrl'] ?? ''; 
$imageUrl      = $attributes['imageUrl'] ?? 'https://via.placeholder.com/960x540?text=Image';
$imageAlt      = $attributes['imageAlt'] ?? 'Scenic travel image';
$imgRadius     = isset($attributes['imageBorderRadius']) ? (int)$attributes['imageBorderRadius'] : 24;

$imagePosition  = in_array( ($attributes['imagePosition'] ?? 'right'), ['left','right'], true )
                  ? $attributes['imagePosition'] : 'right';

$background     = trim( (string) ( $attributes['backgroundColor'] ?? '' ) );
$decor          = is_array( $attributes['decor'] ?? null ) ? $attributes['decor'] : [];

$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';
$pos_class   = $imagePosition === 'left' ? 'is-img-left' : 'is-img-right';
$bg_style    = $background !== '' ? '--card-bg:' . esc_attr( $background ) . ';' : '';

$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'card-2-text-img ' . $align_class . ' child-block ' . $pos_class . ( $background !== '' ? ' has-bg' : '' ),
  'style' => $bg_style,
] );

ob_start();
?>
<div <?= $wrapper_attributes; ?>>

  <?php
  // Decorative side images (absolute, non-interactive)
  if ( ! empty( $decor ) ) :
    foreach ( $decor as $d ) :
      $url  = isset( $d['url'] ) ? esc_url( $d['url'] ) : '';
      if ( $url === '' ) { continue; }
      $alt  = isset( $d['alt'] ) ? esc_attr( $d['alt'] ) : '';
      $side = ( isset( $d['side'] ) && in_array( $d['side'], ['left','right'], true ) ) ? $d['side'] : 'right';
      $side_class = 'card-2-text-img__decor--' . $side;
      ?>
      <img class="card-2-text-img__decor <?= esc_attr( $side_class ); ?>" src="<?= $url; ?>" alt="<?= $alt; ?>" loading="lazy" decoding="async" />
    <?php
    endforeach;
  endif;
  ?>

	<div class="card-2-text-img__inner">
		<div class="card-2-text-img__col card-2-text-img__text">
			<?php if ($headline !== ''): ?>
				<h2 class="card-2-text-img__headline"><?= esc_html($headline); ?></h2>
			<?php endif; ?>

			<?php if ($paragraphOne !== ''): ?>
				<p class="card-2-text-img__copy"><?= wp_kses_post($paragraphOne); ?></p>
			<?php endif; ?>

			<?php if ($paragraphTwo !== ''): ?>
				<p class="card-2-text-img__copy"><?= wp_kses_post($paragraphTwo); ?></p>
			<?php endif; ?>

			<?php if ($buttonText !== ''): ?>
				<?= render_block([
					'blockName'  => 'ilegiants/cta-bounce',
					'attrs'      => ['text' => $buttonText, 'url' => ($buttonUrl ?: '#'), 'accent' => '#FD593C'],
					'innerBlocks'=> [], 'innerHTML' => '', 'innerContent' => []
				]); ?>
			<?php endif; ?>
		</div>

		<div class="card-2-text-img__col card-2-text-img__media">
			<?php if ( $imageUrl ) : ?>
				<figure class="card-2-text-img__figure" style="--card-img-radius: <?= esc_attr( $imgRadius ); ?>px;">
					<img class="card-2-text-img__image"
						src="<?= esc_url( $imageUrl ); ?>"
						alt="<?= esc_attr( $imageAlt ); ?>"
						loading="lazy" decoding="async" />
				</figure>
			<?php endif; ?>
		</div>
	</div>
</div>
