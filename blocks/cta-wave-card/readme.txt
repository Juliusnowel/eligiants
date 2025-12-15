<?= render_block([
    'blockName'   => 'ilegiants/cta-wave-card',
    'attrs'       => [
    'text'    => $btnTxt,
    'url'     => $btnUrl,
    'context' => 'on-image',
    'size'    => 'md',
    'accent'  => 'var(--accent, #FD593C)'
    ],
    'innerBlocks'  => [],
    'innerHTML'    => '',
    'innerContent' => []
]); ?>