<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RTE Manager',
    'description' => 'Configure the default TYPO3 rich-text editor (CKEditor) through a dedicated backend module. Manage custom fonts, color palettes, font sizes, SVG icons, CSS styles and element defaults with live previews. Automatically generates the RTE preset and matching frontend CSS, with shared settings across all sites using the default preset.',
    'category' => 'be',
    'author' => 'Konstantin Stolzenburg',
    'author_email' => 'konstantin.stolzenburg@gmail.com',
    'state' => 'stable',
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.99.99',
            'typo3' => '14.3.0-14.99.99',
            'backend' => '14.3.0-14.99.99',
            'fluid' => '14.3.0-14.99.99',
            'rte_ckeditor' => '14.3.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
