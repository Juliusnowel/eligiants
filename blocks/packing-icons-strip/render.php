<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$background   = isset( $attributes['backgroundColor'] ) ? (string) $attributes['backgroundColor'] : '';
$panel_bg     = isset( $attributes['panelBackgroundColor'] ) ? (string) $attributes['panelBackgroundColor'] : '';
$heading      = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$intro        = isset( $attributes['intro'] ) ? (string) $attributes['intro'] : '';
$footer       = isset( $attributes['footer'] ) ? (string) $attributes['footer'] : '';

$cols_raw     = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;
$columns      = max( 1, $cols_raw );

$items_raw    = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : [];
$items        = array_values( $items_raw );

// wrapper classes / styles
$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';

$wrapper_classes = implode(
	' ',
	array_filter(
		[
			'packing-strip',
			'child-block',
			$align_class,
		]
	)
);

$wrapper_style_parts = [
	'--cols:' . $columns . ';',
];

if ( $background ) {
	$bg = sanitize_hex_color( $background );
	if ( $bg ) {
		$wrapper_style_parts[] = 'background-color:' . $bg . ';';
	}
}

$wrapper_style      = implode( '', $wrapper_style_parts );
$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => $wrapper_classes,
		'style' => $wrapper_style,
	]
);


// inner panel style
$panel_style_attr = '';

if ( $panel_bg ) {
	$panel_bg_clean = sanitize_hex_color( $panel_bg );
	if ( $panel_bg_clean ) {
		$panel_style_attr = ' style="background-color:' . esc_attr( $panel_bg_clean ) . ';"';
	}
}
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="packing-strip__panel"<?php echo $panel_style_attr; ?>>
		<div class="packing-strip__inner">
			<?php if ( $heading ) : ?>
				<div class="packing-strip__heading">
					<h2 class="packing-strip__title">
						<?php echo esc_html( $heading ); ?>
					</h2>
					<span class="packing-strip__rule" aria-hidden="true"></span>
				</div>
			<?php endif; ?>

			<?php if ( $intro ) : ?>
				<p class="packing-strip__intro">
					<?php echo wp_kses_post( $intro ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $items ) ) : ?>
				<div class="packing-strip__grid" role="list" aria-label="<?php echo esc_attr( $heading ); ?>">
					<?php foreach ( $items as $item ) : ?>
						<?php
						$icon_url = isset( $item['iconUrl'] ) ? (string) $item['iconUrl'] : '';
						$icon_alt = isset( $item['iconAlt'] ) ? (string) $item['iconAlt'] : '';
						$text     = isset( $item['text'] ) ? (string) $item['text'] : '';

						if ( ! $icon_url && ! $text ) {
							continue;
						}
						?>
						<article class="packing-strip__item" role="listitem">
							<?php if ( $icon_url ) : ?>
								<div class="packing-strip__icon" aria-hidden="<?php echo $icon_alt === '' ? 'true' : 'false'; ?>">
									<img
										src="<?php echo esc_url( $icon_url ); ?>"
										alt="<?php echo esc_attr( $icon_alt ); ?>"
										loading="lazy"
										decoding="async"
									/>
								</div>
							<?php endif; ?>

							<?php if ( $text ) : ?>
								<p class="packing-strip__item-text">
									<?php echo esc_html( $text ); ?>
								</p>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $footer ) : ?>
				<p class="packing-strip__footer">
					<?php echo wp_kses_post( $footer ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>
