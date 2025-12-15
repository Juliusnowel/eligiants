<?php
/**
 * Responsive Card Grid – server render.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cards = isset( $attributes['cards'] ) && is_array( $attributes['cards'] )
	? $attributes['cards']
	: [];

$default_footer_align = isset( $attributes['footerAlign'] ) ? $attributes['footerAlign'] : 'center';

// Normalize default alignment.
$valid_alignments = [ 'left', 'center', 'right' ];
if ( ! in_array( $default_footer_align, $valid_alignments, true ) ) {
	$default_footer_align = 'center';
}

if ( empty( $cards ) ) {
	return '';
}

// Build inline style for background color if provided.
$style = '';

if ( ! empty( $attributes['backgroundColor'] ) ) {
	$bg = sanitize_hex_color( $attributes['backgroundColor'] );
	if ( $bg ) {
		$style .= 'background-color:' . $bg . ';';
	}
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'responsive-card-grid',
		'style' => $style,
	]
);

// Rich-text tags we allow inside `text`.
$allowed_text_tags = [
	'b'      => [],
	'strong' => [],
	'em'     => [],
	'i'      => [],
	'br'     => [],
	'a'      => [
		'href'   => [],
		'target' => [],
		'rel'    => [],
	],
];

?>
<div <?php echo $wrapper_attributes; ?>>
	<?php foreach ( $cards as $card ) : ?>
		<?php
		$variant   = isset( $card['variant'] ) ? $card['variant'] : 'text';
		$is_image  = ( 'image' === $variant );

		$title     = isset( $card['title'] ) ? $card['title'] : '';
		$raw_text  = isset( $card['text'] ) ? $card['text'] : '';

		$footer_text  = isset( $card['footerText'] ) ? $card['footerText'] : '';
		$footer_url   = isset( $card['footerUrl'] ) ? $card['footerUrl'] : '';
		$footer_align = isset( $card['footerAlign'] ) ? $card['footerAlign'] : $default_footer_align;

		// Normalize per-card alignment.
		if ( ! in_array( $footer_align, $valid_alignments, true ) ) {
			$footer_align = 'center';
		}

		$image_url = isset( $card['imageUrl'] ) ? $card['imageUrl'] : '';
		$image_alt = isset( $card['imageAlt'] ) ? $card['imageAlt'] : '';

		// Existing list groups (list1/list2/list3 pattern).
		$list_data = isset( $card['list'] ) && is_array( $card['list'] )
			? $card['list']
			: [];

		// NEW: checktext (icon + text rows).
		$checktext_items = isset( $card['checktext'] ) && is_array( $card['checktext'] )
			? $card['checktext']
			: [];

		// NEW: title-text (small title + body text pairs).
		$title_text_groups = [];
		if ( isset( $card['titleText'] ) && is_array( $card['titleText'] ) ) {
			$title_text_groups = $card['titleText'];
		} elseif ( isset( $card['title-text'] ) && is_array( $card['title-text'] ) ) {
			// Support hyphenated key if used.
			$title_text_groups = $card['title-text'];
		}

		// Skip completely empty cards.
		if ( $is_image && ! $image_url ) {
			continue;
		}
		if (
			! $is_image
			&& ! $title
			&& ! $raw_text
			&& empty( $list_data )
			&& empty( $checktext_items )
			&& empty( $title_text_groups )
		) {
			continue;
		}

		// Build a sequence of "segments": text chunks + inline list placeholders.
		$segments = [];
		if ( $raw_text ) {
			$pattern      = '/(\[list1\]|\[list2\]|\[list3\])/';
			$placeholders = [
				'[list1]' => 0,
				'[list2]' => 1,
				'[list3]' => 2,
			];

			$parts = preg_split(
				$pattern,
				$raw_text,
				-1,
				PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
			);

			foreach ( $parts as $part ) {
				if ( isset( $placeholders[ $part ] ) ) {
					$segments[] = [
						'type'  => 'list',
						'index' => $placeholders[ $part ],
					];
				} else {
					$segments[] = [
						'type' => 'text',
						'text' => $part,
					];
				}
			}
		}

		$has_content = ! empty( $segments ) || ! empty( $list_data ) || ! empty( $checktext_items ) || ! empty( $title_text_groups );
		?>
		<article class="responsive-card responsive-card--<?php echo $is_image ? 'image' : 'text'; ?>">
			<?php if ( $is_image ) : ?>
				<figure class="responsive-card__figure">
					<img
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $image_alt ); ?>"
						loading="lazy"
					/>
				</figure>
			<?php else : ?>
				<div class="responsive-card__body">
					<?php if ( $title ) : ?>
						<h2 class="responsive-card__title">
							<?php echo esc_html( $title ); ?>
						</h2>
					<?php endif; ?>

					<?php if ( $has_content ) : ?>
						<hr class="responsive-card__separator" />
						<div class="responsive-card__content">
							<?php
							// 1) Existing text + [listX] behaviour.
							if ( ! empty( $segments ) ) :
								foreach ( $segments as $segment ) :
									if ( 'text' === $segment['type'] ) :
										$segment_text = trim( $segment['text'] );
										if ( '' === $segment_text ) {
											continue;
										}
										$segment_text = wp_kses( $segment_text, $allowed_text_tags );
										$segment_html = wpautop( $segment_text );
										?>
										<div class="responsive-card__text">
											<?php
											echo $segment_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</div>
										<?php
									elseif ( 'list' === $segment['type'] ) :
										$idx = $segment['index'];
										if ( ! isset( $list_data[ $idx ] ) ) {
											continue;
										}

										$group       = $list_data[ $idx ];
										$group_title = isset( $group['title'] ) ? $group['title'] : '';
										$items       = isset( $group['list'] ) && is_array( $group['list'] )
											? $group['list']
											: [];

										if ( ! $group_title && empty( $items ) ) {
											continue;
										}
										?>
										<section class="responsive-card__list-group">
											<?php if ( $group_title ) : ?>
												<h3 class="responsive-card__list-title">
													<?php echo esc_html( $group_title ); ?>
												</h3>
											<?php endif; ?>

											<?php if ( ! empty( $items ) ) : ?>
												<ul class="responsive-card__list">
													<?php foreach ( $items as $item ) : ?>
														<?php
														$item_text = isset( $item['text'] )
															? $item['text']
															: ( isset( $item['title'] ) ? $item['title'] : '' );

														if ( ! $item_text ) {
															continue;
														}
														?>
														<li class="responsive-card__list-item">
															<?php
															$item_text_clean = wp_kses( $item_text, $allowed_text_tags );
															echo $item_text_clean; 
															?>
														</li>
													<?php endforeach; ?>
												</ul>
											<?php endif; ?>
										</section>
										<?php
									endif;
								endforeach;

							// 2) Fallback: list groups without placeholders.
							elseif ( ! empty( $list_data ) ) :
								foreach ( $list_data as $group ) :
									$group_title = isset( $group['title'] ) ? $group['title'] : '';
									$items       = isset( $group['list'] ) && is_array( $group['list'] )
										? $group['list']
										: [];

									if ( ! $group_title && empty( $items ) ) {
										continue;
									}
									?>
									<section class="responsive-card__list-group">
										<?php if ( $group_title ) : ?>
											<h3 class="responsive-card__list-title">
												<?php echo esc_html( $group_title ); ?>
											</h3>
										<?php endif; ?>

										<?php if ( ! empty( $items ) ) : ?>
											<ul class="responsive-card__list">
												<?php foreach ( $items as $item ) : ?>
													<?php
													$item_text = isset( $item['text'] )
														? $item['text']
														: ( isset( $item['title'] ) ? $item['title'] : '' );

													if ( ! $item_text ) {
														continue;
													}
													?>
													<li class="responsive-card__list-item">
														<?php
														$item_text_clean = wp_kses( $item_text, $allowed_text_tags );
														echo $item_text_clean;
														?>
													</li>

												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</section>
									<?php
								endforeach;
							endif;
							?>

							<?php if ( ! empty( $checktext_items ) ) : ?>
								<ul class="responsive-card__checktext">
									<?php foreach ( $checktext_items as $item ) : ?>
										<?php
										$item_text_raw = isset( $item['text'] ) ? $item['text'] : '';
										if ( ! $item_text_raw ) {
											continue;
										}
										$item_text_clean = wp_kses( $item_text_raw, $allowed_text_tags );
										$item_text_html  = wpautop( $item_text_clean );
										?>
										<li class="responsive-card__checktext-item">
											<span class="responsive-card__checktext-icon" aria-hidden="true">✓</span>
											<div class="responsive-card__checktext-text">
												<?php
												echo $item_text_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
											</div>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<?php if ( ! empty( $title_text_groups ) ) : ?>
								<div class="responsive-card__title-text-groups">
									<?php foreach ( $title_text_groups as $group ) : ?>
										<?php
										$tt_title_raw = isset( $group['title'] ) ? $group['title'] : '';
										$tt_text_raw  = isset( $group['text'] ) ? $group['text'] : '';

										if ( ! $tt_title_raw && ! $tt_text_raw ) {
											continue;
										}

										$tt_text_html = '';
										if ( $tt_text_raw ) {
											$tt_text_clean = wp_kses( $tt_text_raw, $allowed_text_tags );
											$tt_text_html  = wpautop( $tt_text_clean );
										}
										?>
										<section class="responsive-card__title-text-group">
											<?php if ( $tt_title_raw ) : ?>
												<h3 class="responsive-card__title-text-title">
													<?php echo esc_html( $tt_title_raw ); ?>
												</h3>
											<?php endif; ?>

											<?php if ( $tt_text_html ) : ?>
												<div class="responsive-card__title-text-body">
													<?php
													echo $tt_text_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													?>
												</div>
											<?php endif; ?>
										</section>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

						</div>
					<?php endif; ?>

					<?php if ( $footer_text ) : ?>
						<div class="responsive-card__footer responsive-card__footer--<?php echo esc_attr( $footer_align ); ?>">
							<?php if ( $footer_url ) : ?>
								<a class="responsive-card__footer-link" href="<?php echo esc_url( $footer_url ); ?>">
									<?php echo esc_html( $footer_text ); ?>
								</a>
							<?php else : ?>
								<span class="responsive-card__footer-text">
									<?php echo esc_html( $footer_text ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>
