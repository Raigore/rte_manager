<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'RTE Manager',
    'description' => 'Manage RTE styles, fonts, colors, sizes, icons and presets.',
    'category' => 'be',
    'author' => 'Konstantin Stolzenburg',
    'author_email' => 'konstantin.stolzenburg@gmail.com',
    'state' => 'stable',
    'version' => '1.0.0',
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
