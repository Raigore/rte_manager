<?php

declare(strict_types=1);

use Kst3\RteManager\Controller\Backend\RteManagerController;
return [
    'rte_manager_theme' => [
        'position' => ['after' => 'media'],
        'access' => 'user',
        'iconIdentifier' => 'module-info',
        'labels' => [
            'title' => 'LLL:EXT:rte_manager/Resources/Private/Language/locallang.xlf:module.theme',
            'shortDescription' => 'LLL:EXT:rte_manager/Resources/Private/Language/locallang.xlf:module.theme',
            'description' => 'LLL:EXT:rte_manager/Resources/Private/Language/locallang.xlf:module.theme.settings.and.management.modules',
        ],
        'appearance' => ['dependsOnSubmodules' => true],
        'showSubmoduleOverview' => true,
    ],
    'rte_manager' => [
        'parent' => 'rte_manager_theme',
        'position' => ['after' => '*'],
        'access' => 'user',
        'path' => '/module/theme/rte-manager',
        'iconIdentifier' => 'module-rte-ckeditor',
        'labels' => [
            'title' => 'LLL:EXT:rte_manager/Resources/Private/Language/locallang.xlf:module.rte.manager',
            'shortDescription' => 'LLL:EXT:rte_manager/Resources/Private/Language/locallang.xlf:module.manage.rte.styles.and.formats',
            'description' => 'LLL:EXT:rte_manager/Resources/Private/Language/locallang.xlf:module.manage.the.default.rte.preset.custom.styles.and.heading.formats',
        ],
        'routes' => [
            '_default' => ['target' => RteManagerController::class . '::handleRequest'],
        ],
    ],
];
