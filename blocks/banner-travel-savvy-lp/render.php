<?php
/**
 * Banner Travel Savvy LP Block Template.
 *
 * @param   array $attributes - Block attributes.
 * @param   string $content - Block content.
 * @param   WP_Block $block - Block instance.
 */

$title = isset( $attributes['title'] ) ? $attributes['title'] : '';
$text  = isset( $attributes['text'] ) ? $attributes['text'] : '';

$wrapper_attributes = get_block_wrapper_attributes( [
    'class' => 'banner-travel-savvy-lp',
] );
?>
<div <?php echo $wrapper_attributes; ?>>
    <div class="banner-travel-savvy-lp__content">
        <?php if ( $title ) : ?>
            <h1 class="banner-travel-savvy-lp__title"><?php echo esc_html( $title ); ?></h1>
        <?php endif; ?>
        
        <hr class="banner-travel-savvy-lp__separator" />
        
        <?php if ( $text ) : ?>
            <?php
            // Allow basic inline tags if you ever need them.
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

            // 1) Sanitize any inline HTML
            $text_clean = wp_kses( $text, $allowed_text_tags );

            // 2) Convert blank lines (\n\n) into <p>…</p> and single \n into <br>
            $text_html = wpautop( $text_clean );
            ?>
            <div class="banner-travel-savvy-lp__text">
                <?php echo $text_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>

            </div>
</div>
