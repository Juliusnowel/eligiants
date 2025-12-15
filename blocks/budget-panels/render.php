<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bg        = isset( $attributes['backgroundColor'] ) ? (string) $attributes['backgroundColor'] : '';
$cols_raw  = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 2;
$columns   = max( 1, min( 2, $cols_raw ) ); // 1 or 2 only

$panels_raw = isset( $attributes['panels'] ) && is_array( $attributes['panels'] ) ? $attributes['panels'] : [];
$panels     = array_values( $panels_raw );

// Heading icon (global)
$heading_icon_url_raw   = $attributes['headingIconUrl']   ?? '';
$heading_icon_alt_raw   = $attributes['headingIconAlt']   ?? '';
$heading_icon_align_raw = $attributes['headingIconAlign'] ?? 'none';

$heading_icon_url   = trim( (string) $heading_icon_url_raw );
$heading_icon_alt   = trim( (string) $heading_icon_alt_raw );
$heading_icon_align = in_array( $heading_icon_align_raw, [ 'none', 'left' ], true ) ? $heading_icon_align_raw : 'none';

// Side decor
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
			$footer    = isset( $panel['footer'] ) ? (string) $panel['footer'] : '';
			$panel_bg  = isset( $panel['backgroundColor'] ) ? (string) $panel['backgroundColor'] : '';

			// Early skip if panel is totally empty.
			if ( ! $title && ! $text_raw && ! $footer
				&& empty( $panel['subTitle'] ) && empty( $panel['list'] )
				&& empty( $panel['subTitle2'] ) && empty( $panel['list2'] )
				&& empty( $panel['groupText'] )
			) {
				continue;
			}

			// Panel background
			$panel_style = '';
			if ( $panel_bg ) {
				$panel_bg_clean = sanitize_hex_color( $panel_bg );
				if ( $panel_bg_clean ) {
					$panel_style .= 'background-color:' . $panel_bg_clean . ';';
				}
			}

			// Main text with paragraphs
			$text_html = '';
			if ( $text_raw ) {
				$text_html = wpautop( wp_kses_post( $text_raw ) );
			}

			$has_icon_left = ( $heading_icon_align === 'left' && $heading_icon_url !== '' );

			// Inline HTML allowed in subtitles / list items / groupText
			$allowed_inline = [
				'b'      => [ 'class' => [], 'style' => [] ],
				'strong' => [ 'class' => [], 'style' => [] ],
				'em'     => [ 'class' => [], 'style' => [] ],
				'i'      => [ 'class' => [], 'style' => [] ],
				'span'   => [ 'class' => [], 'style' => [] ],
				'br'     => [],
			];

			/**
			 * Collect ALL subTitle + list pairs into $sections.
			 */
			$sections = [];

			$pairs = [
				[ 'subTitle',  'list'  ],
				[ 'subTitle2', 'list2' ],
				[ 'subTitle3', 'list3' ],
				[ 'subTitle4', 'list4' ],
			];

			foreach ( $pairs as $pair ) {
				list( $sub_key, $list_key ) = $pair;

				$sub = isset( $panel[ $sub_key ] ) ? trim( (string) $panel[ $sub_key ] ) : '';

				$list_raw = ( isset( $panel[ $list_key ] ) && is_array( $panel[ $list_key ] ) )
					? $panel[ $list_key ]
					: [];

				// Normalize list items to simple strings
				$items = [];
				foreach ( $list_raw as $li ) {
					if ( is_array( $li ) ) {
						$item_text = isset( $li['text'] ) ? trim( (string) $li['text'] ) : '';
					} else {
						$item_text = trim( (string) $li );
					}
					if ( $item_text !== '' ) {
						$items[] = $item_text;
					}
				}

				if ( $sub === '' && empty( $items ) ) {
					continue;
				}

				$sections[] = [
					'title' => $sub,
					'items' => $items,
				];
			}

			/**
			 * groupText: multiple titled paragraphs, no bullets.
			 * Example shape:
			 * "groupText": [
			 *   { "title": "Chopsticks rules", "text": "..." },
			 *   { "title": "Slurping is okay", "text": "..." }
			 * ]
			 */
			$group_text_raw   = isset( $panel['groupText'] ) && is_array( $panel['groupText'] ) ? $panel['groupText'] : [];
			$group_text_items = [];

			foreach ( $group_text_raw as $g ) {
				if ( ! is_array( $g ) ) {
					continue;
				}
				$g_title = isset( $g['title'] ) ? trim( (string) $g['title'] ) : '';
				$g_text  = isset( $g['text'] ) ? trim( (string) $g['text'] ) : '';

				if ( $g_title === '' && $g_text === '' ) {
					continue;
				}

				$group_text_items[] = [
					'title' => $g_title,
					'text'  => $g_text,
				];
			}
			?>
			<article class="budget-panel" <?php echo $panel_style ? 'style="' . esc_attr( $panel_style ) . '"' : ''; ?>>
				<div class="budget-panel__inner">

					<?php if ( $title ) : ?>
						<div class="budget-panel__title-wrap<?php echo $has_icon_left ? ' budget-panel__title-wrap--has-icon' : ''; ?>">
							<?php if ( $has_icon_left ) : ?>
								<span class="budget-panel__title-icon">
									<img
										src="<?php echo esc_url( $heading_icon_url ); ?>"
										alt="<?php echo esc_attr( $heading_icon_alt ); ?>"
										loading="lazy"
										decoding="async"
									/>
								</span>
							<?php endif; ?>

							<h3 class="budget-panel__title">
								<?php echo esc_html( $title ); ?>
							</h3>
						</div>
					<?php endif; ?>

					<?php if ( $text_html ) : ?>
						<div class="budget-panel__text">
							<?php echo $text_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $sections ) || ! empty( $group_text_items ) ) : ?>
						<div class="budget-panel__sections">

							<?php foreach ( $sections as $section ) : ?>
								<div class="budget-panel__section">
									<?php if ( $section['title'] !== '' ) : ?>
										<h4 class="budget-panel__subtitle">
											<?php echo wp_kses( $section['title'], $allowed_inline ); ?>
										</h4>
									<?php endif; ?>

									<?php if ( ! empty( $section['items'] ) ) : ?>
										<ul class="budget-panel__list">
											<?php foreach ( $section['items'] as $item_text ) : ?>
												<li class="budget-panel__list-item">
													<?php echo wp_kses( $item_text, $allowed_inline ); ?>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>

							<?php if ( ! empty( $group_text_items ) ) : ?>
								<?php foreach ( $group_text_items as $group ) : ?>
									<div class="budget-panel__group">
										<?php if ( $group['title'] !== '' ) : ?>
											<h4 class="budget-panel__subtitle budget-panel__group-title">
												<?php echo wp_kses( $group['title'], $allowed_inline ); ?>
											</h4>
										<?php endif; ?>

										<?php if ( $group['text'] !== '' ) : ?>
											<p class="budget-panel__group-text">
												<?php echo wp_kses_post( $group['text'] ); ?>
											</p>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>

						</div>
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
