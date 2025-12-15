<?php
/**
 * Banner Travel Savvy LP Block Template.
 *
 * @param   array    $attributes Block attributes.
 * @param   string   $content    Block content.
 * @param   WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title        = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
$text         = isset( $attributes['text'] )  ? (string) $attributes['text']  : '';
$footer_url   = isset( $attributes['footerUrl'] ) ? (string) $attributes['footerUrl'] : '';
$footer_label = isset( $attributes['footerLabel'] ) ? (string) $attributes['footerLabel'] : '';

// New: heading tag control.
$heading_tag_raw = isset( $attributes['headingTag'] ) ? strtolower( (string) $attributes['headingTag'] ) : '';
$allowed_tags    = [ 'h1', 'h2', 'h3', 'p' ];

// Default to h1 when empty or invalid.
$heading_tag = in_array( $heading_tag_raw, $allowed_tags, true ) ? $heading_tag_raw : 'h1';

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'banner-travel-savvy-lp',
	]
);
?>
<div <?php echo $wrapper_attributes; ?>>
	<div class="banner-travel-savvy-lp__content">
		<?php if ( $title ) : ?>
			<<?php echo esc_attr( $heading_tag ); ?> class="banner-travel-savvy-lp__title">
				<?php echo esc_html( $title ); ?>
			</<?php echo esc_attr( $heading_tag ); ?>>
		<?php endif; ?>

		<hr class="banner-travel-savvy-lp__separator" />

		<?php if ( $text ) : ?>
			<?php
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

			$text_clean = wp_kses( $text, $allowed_text_tags );
			$text_html  = wpautop( $text_clean );
			?>
			<div class="banner-travel-savvy-lp__text">
				<?php echo $text_html; ?>
			</div>
		<?php endif; ?>

		<?php if ( $footer_url && $footer_label ) : ?>
			<div class="banner-travel-savvy-lp__footer">
				<a class="banner-travel-savvy-lp__footer-link" href="<?php echo esc_url( $footer_url ); ?>">
					<?php echo esc_html( $footer_label ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</div>
