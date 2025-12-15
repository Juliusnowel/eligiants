<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$columns_raw = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 2;
$columns     = max( 1, min( 3, $columns_raw ) ); // clamp 1–3

$gap_raw   = isset( $attributes['gap'] ) ? (int) $attributes['gap'] : 32;
$gap       = max( 0, $gap_raw );

$max_width = isset( $attributes['maxWidth'] ) ? (int) $attributes['maxWidth'] : 1160;
$max_width = $max_width > 0 ? $max_width : 1160;

// cards[] – hard cap at 3
$cards_raw = is_array( $attributes['cards'] ?? null ) ? $attributes['cards'] : [];
$cards     = array_slice( $cards_raw, 0, 3 );

$decor_attr = is_array( $attributes['decor'] ?? null ) ? $attributes['decor'] : [];

$align_class = ! empty( $attributes['align'] ) ? 'align' . $attributes['align'] : '';

$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'card-rail ' . $align_class,
	'style' => sprintf(
		'--cr-cols:%1$d;--cr-gap:%2$dpx;--cr-max-width:%3$dpx;',
		$columns,
		$gap,
		$max_width
	),
] );

ob_start();
?>
<div <?php echo $wrapper_attributes; ?>>

	<?php
	// Side decor (left/right, top/center/bottom)
	if ( ! empty( $decor_attr ) ) :
		foreach ( $decor_attr as $d ) :
			$url = isset( $d['url'] ) ? esc_url( $d['url'] ) : '';
			if ( ! $url ) { continue; }

			$alt  = isset( $d['alt'] ) ? esc_attr( $d['alt'] ) : '';
			$side = isset( $d['side'] ) ? strtolower( (string) $d['side'] ) : 'left';
			$side = in_array( $side, [ 'left', 'right' ], true ) ? $side : 'left';

			$vpos = isset( $d['vPos'] ) ? strtolower( (string) $d['vPos'] ) : 'center';
			$vpos = in_array( $vpos, [ 'top', 'center', 'bottom' ], true ) ? $vpos : 'center';

			$classes  = 'card-rail__decor';
			$classes .= ' card-rail__decor--' . $side;
			$classes .= ' card-rail__decor--v-' . $vpos;
			?>
			<img
				class="<?php echo esc_attr( $classes ); ?>"
				src="<?php echo $url; ?>"
				alt="<?php echo $alt; ?>"
				loading="lazy"
				decoding="async"
			/>
		<?php
		endforeach;
	endif;
	?>

	<div class="card-rail__outer">
		<div class="card-rail__inner">
			<?php if ( ! empty( $cards ) ) : ?>
				<div class="card-rail__grid card-count-<?php echo count( $cards ); ?>">

					<?php foreach ( $cards as $card ) :

						$image_url  = isset( $card['imageUrl'] ) ? esc_url( $card['imageUrl'] ) : '';
						$image_alt  = isset( $card['imageAlt'] ) ? esc_attr( $card['imageAlt'] ) : '';

						$title      = isset( $card['title'] ) ? trim( (string) $card['title'] ) : '';
						$text       = isset( $card['text'] )  ? (string) $card['text'] : '';

						$cta_text   = isset( $card['ctaText'] ) ? trim( (string) $card['ctaText'] ) : '';
						$cta_url    = isset( $card['ctaUrl'] )  ? (string) $card['ctaUrl'] : '#';
						$ctaAccent  = isset( $card['ctaAccent'] ) ? (string) $card['ctaAccent'] : '#FD593C';
						$ctaTextCol = isset( $card['ctaTextColor'] ) ? (string) $card['ctaTextColor'] : '#FFFFFF';
						$ctaBorder  = isset( $card['ctaBorderColor'] ) ? (string) $card['ctaBorderColor'] : 'transparent';
						?>
						<article class="card-rail__item">

							<?php if ( $image_url ) : ?>
								<figure class="card-rail__media">
									<img
										src="<?php echo $image_url; ?>"
										alt="<?php echo $image_alt; ?>"
										class="card-rail__image"
										loading="lazy"
										decoding="async"
									/>
								</figure>
							<?php endif; ?>

							<div class="card-rail__body">
								<?php if ( $title ) : ?>
									<h3 class="card-rail__title">
										<?php echo esc_html( $title ); ?>
									</h3>
								<?php endif; ?>

								<?php if ( $text ) : ?>
									<p class="card-rail__text">
										<?php echo wp_kses_post( $text ); ?>
									</p>
								<?php endif; ?>

								<?php if ( $cta_text ) : ?>
									<div class="card-rail__cta">
										<?php
										echo render_block( [
											'blockName'   => 'ilegiants/cta-bounce',
											'attrs'       => [
												'text'        => $cta_text,
												'url'         => ( $cta_url ?: '#' ),
												'accent'      => $ctaAccent,
												'textColor'   => $ctaTextCol,
												'borderColor' => $ctaBorder,
											],
											'innerBlocks' => [],
											'innerHTML'   => '',
											'innerContent'=> [],
										] );
										?>
									</div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>

				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

