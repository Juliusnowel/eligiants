<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bg        = isset( $attributes['backgroundColor'] ) ? (string) $attributes['backgroundColor'] : '';
$cols_raw  = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 2;
$columns   = max( 1, min( 2, $cols_raw ) ); // 1 or 2 only

$panels_raw = isset( $attributes['panels'] ) && is_array( $attributes['panels'] ) ? $attributes['panels'] : [];
$panels     = array_values( $panels_raw );

$decor_raw = $attributes['decor'] ?? [];
$decor_items = array_values(
	array_filter(
		is_array( $decor_raw ) ? $decor_raw : [],
		function( $d ) {
			$side = isset( $d['side'] ) ? strtolower( (string) $d['side'] ) : '';
			$pos  = isset( $d['position'] ) ? strtolower( (string) $d['position'] ) : 'center';
			return ! empty( $d['url'] ) && in_array( $side, [ 'left', 'right' ], true ) && in_array( $pos, [ 'top', 'center', 'bottom' ], true );
		}
	)
);

$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';

$classes = trim(
	implode(
		' ',
		array_filter(
			[
				'budget-panels',
				'child-block',
				$align_class,
			]
		)
	)
);

$style_parts   = [ '--cols:' . $columns . ';' ];
if ( $bg ) {
	$bg_clean = sanitize_hex_color( $bg );
	if ( $bg_clean ) {
		$style_parts[] = 'background-color:' . $bg_clean . ';';
	}
}
$wrapper_style = implode( '', $style_parts );

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => $classes,
		'style' => $wrapper_style,
	]
);
?>

<div <?php echo $wrapper_attributes; ?>>

	<?php foreach ( $decor_items as $d ) : ?>
		<?php
		$side = strtolower( $d['side'] );
		$pos  = strtolower( $d['position'] ?? 'center' );
		$alt  = isset( $d['alt'] ) ? (string) $d['alt'] : '';
		?>
		<img
			class="budget-panels__decor budget-panels__decor--<?php echo esc_attr( $side ); ?> budget-panels__decor--pos-<?php echo esc_attr( $pos ); ?>"
			src="<?php echo esc_url( $d['url'] ); ?>"
			alt="<?php echo esc_attr( $alt ); ?>"
			loading="lazy"
			decoding="async"
		/>
	<?php endforeach; ?>

	<div class="budget-panels__grid">
		<?php foreach ( $panels as $panel ) : ?>
			<?php
			$title     = isset( $panel['title'] ) ? (string) $panel['title'] : '';
			$text_raw  = isset( $panel['text'] ) ? (string) $panel['text'] : '';
			$subtitle  = isset( $panel['subTitle'] ) ? (string) $panel['subTitle'] : '';
			$list_raw  = isset( $panel['list'] ) && is_array( $panel['list'] ) ? $panel['list'] : [];
			$footer    = isset( $panel['footer'] ) ? (string) $panel['footer'] : '';
			$panel_bg  = isset( $panel['backgroundColor'] ) ? (string) $panel['backgroundColor'] : '';

			if ( ! $title && ! $text_raw && ! $subtitle && empty( $list_raw ) && ! $footer ) {
				continue;
			}

			$panel_style = '';
			if ( $panel_bg ) {
				$panel_bg_clean = sanitize_hex_color( $panel_bg );
				if ( $panel_bg_clean ) {
					$panel_style .= 'background-color:' . $panel_bg_clean . ';';
				}
			}

			// handle newlines in text
			$text_html = '';
			if ( $text_raw ) {
				$text_html = wpautop( wp_kses_post( $text_raw ) );
			}
			?>
			<article class="budget-panel" <?php echo $panel_style ? 'style="' . esc_attr( $panel_style ) . '"' : ''; ?>>
				<div class="budget-panel__inner">
					<?php if ( $title ) : ?>
						<h3 class="budget-panel__title"><?php echo esc_html( $title ); ?></h3>
						<span class="budget-panel__rule" aria-hidden="true"></span>
					<?php endif; ?>

					<?php if ( $text_html ) : ?>
						<div class="budget-panel__text">
							<?php echo $text_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>

					<?php if ( $subtitle ) : ?>
						<h4 class="budget-panel__subtitle"><?php echo esc_html( $subtitle ); ?></h4>
					<?php endif; ?>

					<?php if ( ! empty( $list_raw ) ) : ?>
						<ul class="budget-panel__list">
							<?php foreach ( $list_raw as $item ) : ?>
								<?php
								$item_text = isset( $item['text'] ) ? (string) $item['text'] : '';
								if ( ! $item_text ) {
									continue;
								}
								?>
								<li class="budget-panel__list-item">
									<?php echo esc_html( $item_text ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( $footer ) : ?>
						<p class="budget-panel__footer">
							<?php echo wp_kses_post( $footer ); ?>
						</p>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>
