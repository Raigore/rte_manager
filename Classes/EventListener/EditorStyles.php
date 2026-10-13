<?php

declare(strict_types=1);

namespace Kst3\RteManager\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterPrepareConfigurationForEditorEvent;

#[AsEventListener(identifier: 'rte-manager/editor-styles')]
final class EditorStyles
{
    public function __invoke(AfterPrepareConfigurationForEditorEvent $event): void
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class)->addInlineLanguageLabelFile('EXT:rte_manager/Resources/Private/Language/locallang.xlf', 'js.rteManager.');
        $config = $event->getConfiguration();
        foreach ($config['contentsCss'] ?? [] as $index => $path) {
            if (is_string($path) && str_starts_with($path, 'fileadmin/rte_manager/')) {
                $config['contentsCss'][$index] = '/' . ltrim(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/' . $path), '/');
            }
        }
        $event->setConfiguration($config);
    }
}
