<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

use TYPO3\CMS\Core\Localization\LanguageService;

/** Backend labels with an English fallback for CLI and uninitialized contexts. */
final class Localization
{
    public static function translate(string $key, string $fallback, array $arguments = []): string
    {
        $language = $GLOBALS['LANG'] ?? null;
        $label = $language instanceof LanguageService
            ? $language->sL('LLL:EXT:rte_manager/Resources/Private/Language/locallang.xlf:' . $key)
            : '';
        $label = $label !== '' ? $label : $fallback;
        return $arguments === [] ? $label : vsprintf($label, $arguments);
    }
}
