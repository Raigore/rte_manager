<?php

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default']
    = \Kst3\RteManager\Service\RteSettings::PRESET;

// TYPO3 compiles ext_localconf.php into its cache, so __DIR__ would point there.
\Kst3\RteManager\Service\RteSettings::ensurePreset();
$rtePreset = \Symfony\Component\Yaml\Yaml::parseFile(
    \Kst3\RteManager\Service\RteSettings::presetPath()
);
$rteFonts = \Kst3\RteManager\Service\HostedFonts::all($rtePreset['rteManager'] ?? []);
if ($rteFonts !== []) {
    $rteFontCss = \Kst3\RteManager\Service\HostedFonts::stylesheetPath($rteFonts);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('page.includeCSS.rte_manager_fonts = ' . $rteFontCss);
}
foreach ($rtePreset['editor']['config']['contentsCss'] ?? [] as $rteClassesCss) {
    if (is_string($rteClassesCss)
        && preg_match('/^fileadmin\/rte_manager\/styles\/classes(?:-[a-f0-9]{64})?\.css$/D', $rteClassesCss)
        && is_file(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . $rteClassesCss)) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('page.includeCSS.rte_manager_classes = ' . $rteClassesCss);
    }
}
unset($rtePreset, $rteFonts, $rteFontCss, $rteClassesCss);
