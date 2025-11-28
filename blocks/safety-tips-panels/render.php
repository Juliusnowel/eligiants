<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$background   = isset( $attributes['backgroundColor'] ) ? (string) $attributes['backgroundColor'] : '';
$cols_raw     = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 2;
$columns      = max( 1, min( 2, $cols_raw ) ); // you only need 1 or 2

$panels_raw   = isset( $attributes['panels'] ) && is_array( $attributes['panels'] ) ? $attributes['panels'] : [];
$panels       = array_values( $panels_raw ); // reindex

// Wrapper class / style
$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';

$wrapper_classes = implode(
	' ',
	array_filter(
		[
			'safety-panels',
			'child-block',
			$align_class,
		]
	)
);

$style_parts = [ '--cols:' . $columns . ';' ];

if ( $background ) {
	$bg = sanitize_hex_color( $background );
	if ( $bg ) {
		$style_parts[] = 'background-color:' . $bg . ';';
	}
}

$wrapper_style       = implode( '', $style_parts );
$wrapper_attributes  = get_block_wrapper_attributes(
	[
		'class' => $wrapper_classes,
		'style' => $wrapper_style,
	]
);
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="safety-panels__grid">
		<?php foreach ( $panels as $panel ) : ?>
			<?php
			$title      = isset( $panel['title'] ) ? (string) $panel['title'] : '';
			$intro      = isset( $panel['intro'] ) ? (string) $panel['intro'] : '';
			$panel_bg   = isset( $panel['backgroundColor'] ) ? (string) $panel['backgroundColor'] : '';
			$items_raw  = isset( $panel['items'] ) && is_array( $panel['items'] ) ? $panel['items'] : [];

			$panel_style = '';

			if ( $panel_bg ) {
				$panel_bg_clean = sanitize_hex_color( $panel_bg );
				if ( $panel_bg_clean ) {
					$panel_style .= 'background-color:' . $panel_bg_clean . ';';
				}
			}

			// Skip completely empty panels
			if ( ! $title && ! $intro && empty( $items_raw ) ) {
				continue;
			}
			?>
			<article class="safety-panel" <?php echo $panel_style ? 'style="' . esc_attr( $panel_style ) . '"' : ''; ?>>
				<div class="safety-panel__inner">
					<?php if ( $title ) : ?>
						<h3 class="safety-panel__title">
							<?php echo esc_html( $title ); ?>
						</h3>
					<?php endif; ?>

					<?php if ( $intro ) : ?>
						<p class="safety-panel__intro">
							<?php echo wp_kses_post( $intro ); ?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $items_raw ) ) : ?>
						<div class="safety-panel__items">
							<?php foreach ( $items_raw as $item ) : ?>
								<?php
								$icon_url = isset( $item['iconUrl'] ) ? (string) $item['iconUrl'] : '';
								$icon_alt = isset( $item['iconAlt'] ) ? (string) $item['iconAlt'] : '';
								$item_title = isset( $item['title'] ) ? (string) $item['title'] : '';
								$item_body  = isset( $item['body'] ) ? (string) $item['body'] : '';

								if ( ! $icon_url && ! $item_title && ! $item_body ) {
									continue;
								}
								?>
								<article class="safety-panel__item">
									<?php if ( $icon_url ) : ?>
										<div class="safety-panel__item-icon" aria-hidden="<?php echo $icon_alt === '' ? 'true' : 'false'; ?>">
											<img
												src="<?php echo esc_url( $icon_url ); ?>"
												alt="<?php echo esc_attr( $icon_alt ); ?>"
												loading="lazy"
												decoding="async"
											/>
										</div>
									<?php endif; ?>

									<div class="safety-panel__item-text">
										<?php if ( $item_title ) : ?>
											<h3 class="safety-panel__item-title">
												<?php echo esc_html( $item_title ); ?>
											</h3>
										<?php endif; ?>

										<?php if ( $item_body ) : ?>
											<p class="safety-panel__item-body">
												<?php echo wp_kses_post( $item_body ); ?>
											</p>
										<?php endif; ?>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>
