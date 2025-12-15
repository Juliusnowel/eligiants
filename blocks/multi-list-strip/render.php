<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$background = isset( $attributes['backgroundColor'] ) ? (string) $attributes['backgroundColor'] : '';
$panel_bg   = isset( $attributes['panelBackgroundColor'] ) ? (string) $attributes['panelBackgroundColor'] : '';
$heading    = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$intro      = isset( $attributes['intro'] ) ? (string) $attributes['intro'] : '';
$footer     = isset( $attributes['footer'] ) ? (string) $attributes['footer'] : '';

$cols_raw   = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 4;
$columns    = max( 1, min( 4, $cols_raw ) );

$items_raw  = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : [];
$items      = array_values( $items_raw );

// normalise items
$normalized_items = [];
foreach ( $items as $item ) {
	$title    = isset( $item['title'] ) ? (string) $item['title'] : '';
	$list_raw = isset( $item['list'] ) && is_array( $item['list'] ) ? $item['list'] : [];

	$list = [];
	foreach ( $list_raw as $li ) {
		if ( is_array( $li ) ) {
			$text = isset( $li['text'] ) ? (string) $li['text'] : '';
		} else {
			$text = (string) $li;
		}
		if ( '' !== trim( $text ) ) {
			$list[] = $text;
		}
	}

	if ( '' === $title && empty( $list ) ) {
		continue;
	}

	$normalized_items[] = [
		'title' => $title,
		'list'  => $list,
	];
}

// decor
$decor_raw = isset( $attributes['decor'] ) && is_array( $attributes['decor'] ) ? $attributes['decor'] : [];
$decor_items = array_values(
	array_filter(
		$decor_raw,
		function( $d ) {
			$side = isset( $d['side'] ) ? strtolower( (string) $d['side'] ) : '';
			$pos  = isset( $d['position'] ) ? strtolower( (string) $d['position'] ) : 'center';

			return ! empty( $d['url'] )
				&& in_array( $side, [ 'left', 'right' ], true )
				&& in_array( $pos, [ 'top', 'center', 'bottom' ], true );
		}
	)
);

// wrapper classes / styles
$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';
$cols_class  = 'multi-list-strip--cols-' . $columns;

$wrapper_classes = implode(
	' ',
	array_filter(
		[
			'multi-list-strip',
			'child-block',
			$align_class,
			$cols_class,
		]
	)
);

$wrapper_style_parts = [
	'--cols:' . $columns . ';',
];

$bg_clean = '';
if ( $background ) {
	$bg_clean = sanitize_hex_color( $background );
	if ( $bg_clean ) {
		$wrapper_style_parts[] = 'background-color:' . $bg_clean . ';';
	}
}

$wrapper_style = implode( '', $wrapper_style_parts );

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => $wrapper_classes,
		'style' => $wrapper_style,
	]
);

// inner panel style + box-shadow logic
$panel_bg_clean = '';
$panel_style_attr = '';

if ( $panel_bg ) {
	$panel_bg_clean = sanitize_hex_color( $panel_bg );
	if ( $panel_bg_clean ) {
		$panel_style_attr = ' style="background-color:' . esc_attr( $panel_bg_clean ) . ';"';
	}
}

// if outer and inner bg are equal, flatten (no shadow)
$panel_classes = [ 'multi-list-strip__panel' ];
if ( $bg_clean && $panel_bg_clean && $bg_clean === $panel_bg_clean ) {
	$panel_classes[] = 'multi-list-strip__panel--flat';
} else {
	$panel_classes[] = 'multi-list-strip__panel--raised';
}
$panel_classes_attr = esc_attr( implode( ' ', $panel_classes ) );
?>
<div <?php echo $wrapper_attributes; ?>>

	<?php foreach ( $decor_items as $d ) : ?>
        <?php
        $side = strtolower( $d['side'] );
        $pos  = strtolower( $d['position'] ?? 'center' );
        $alt  = isset( $d['alt'] ) ? (string) $d['alt'] : '';
        ?>
        <img
            class="multi-list-strip__decor multi-list-strip__decor--<?php echo esc_attr( $side ); ?> multi-list-strip__decor--pos-<?php echo esc_attr( $pos ); ?>"
            src="<?php echo esc_url( $d['url'] ); ?>"
            alt="<?php echo esc_attr( $alt ); ?>"
            loading="lazy"
            decoding="async"
        />
    <?php endforeach; ?>


	<div class="<?php echo $panel_classes_attr; ?>"<?php echo $panel_style_attr; ?>>
		<div class="multi-list-strip__inner">

			<?php if ( $heading ) : ?>
				<div class="multi-list-strip__heading">
					<h2 class="multi-list-strip__title">
						<?php echo esc_html( $heading ); ?>
					</h2>
					<span class="multi-list-strip__rule" aria-hidden="true"></span>
				</div>
			<?php endif; ?>

			<?php if ( $intro ) : ?>
				<p class="multi-list-strip__intro">
					<?php echo wp_kses_post( $intro ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $normalized_items ) ) : ?>
				<div class="multi-list-strip__columns" role="list">
					<?php foreach ( $normalized_items as $item ) : ?>
						<article class="multi-list-strip__col" role="listitem">
							<?php if ( $item['title'] ) : ?>
								<h3 class="multi-list-strip__col-title">
									<?php echo esc_html( $item['title'] ); ?>
								</h3>
							<?php endif; ?>

							<?php if ( ! empty( $item['list'] ) ) : ?>
								<ul class="multi-list-strip__list">
									<?php foreach ( $item['list'] as $text ) : ?>
										<li class="multi-list-strip__list-item">
											<?php echo esc_html( $text ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $footer ) : ?>
				<p class="multi-list-strip__footer">
					<?php echo wp_kses_post( $footer ); ?>
				</p>
			<?php endif; ?>

		</div>
	</div>
</div>
