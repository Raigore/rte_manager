<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RteSettings
{
    public const PRESET = 'fileadmin/rte_manager/Configuration/Generated.yaml';
    public const FALLBACK_PRESET = 'EXT:rte_ckeditor/Configuration/RTE/Default.yaml';
    private static ?string $loadError = null;

    public static function loadError(): ?string
    {
        return self::$loadError;
    }

    /** A broken managed preset must never prevent TYPO3 from booting. */
    public static function loadPreset(): array
    {
        self::$loadError = null;
        try {
            self::ensurePreset();
            $preset = Yaml::parseFile(self::presetPath());
            if (!is_array($preset) || !is_array($preset['editor']['config'] ?? null)
                || !is_array($preset['rteManager'] ?? [])
                || !is_array($preset['editor']['config']['contentsCss'] ?? [])) {
                throw new \UnexpectedValueException('The preset must contain an editor configuration mapping.');
            }
            // Validate stored form values before either the module or startup consumes them.
            $settings = $preset['rteManager'] ?? [];
            $service = new self();
            $service->normalize($settings);
            foreach (['fonts', 'defaultFont'] as $key) {
                if (!is_array($settings[$key] ?? [])) {
                    throw new \UnexpectedValueException('Invalid stored font settings.');
                }
            }
            foreach (HostedFonts::all($settings) as $font) {
                if (!is_array($font)) throw new \UnexpectedValueException('Invalid stored font.');
                foreach (['family', 'file', 'weight', 'style'] as $key) {
                    if (!is_string($font[$key] ?? null)) throw new \UnexpectedValueException('Invalid stored font field.');
                }
            }
            ElementDefaults::rows($settings);
            return $preset;
        } catch (\Throwable $exception) {
            self::$loadError = $exception->getMessage();
            return [];
        }
    }

    public static function presetPath(): string
    {
        return Environment::getPublicPath() . '/' . self::PRESET;
    }

    public static function ensurePreset(): void
    {
        $target = self::presetPath();
        if (is_file($target)) return;
        if (LegacyMigration::migrate(Environment::getPublicPath())) return;
        // One-time migration: never replace persisted settings with extension defaults.
        $legacy = ExtensionManagementUtility::extPath('rte_manager', 'Configuration/RTE/Generated.yaml');
        $source = is_file($legacy) ? $legacy : GeneralUtility::getFileAbsFileName('EXT:rte_ckeditor/Configuration/RTE/Default.yaml');
        (new Filesystem())->dumpFile($target, file_get_contents($source));
    }
    public const HEADINGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    public const ELEMENTS = ['span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a'];

    public function getElementOptions(string|array $selected = []): array
    {
        if (is_string($selected)) {
            $selected = preg_split('/[\s,]+/', $selected, -1, PREG_SPLIT_NO_EMPTY);
        }
        return array_map(static fn(string $element): array => [
            'name' => $element,
            'label' => match ($element) {
                'headings' => Localization::translate('label.headings.1.6', 'Headings 1–6'),
                'p' => Localization::translate('label.element.paragraph', 'Paragraph (p)'),
                'a' => Localization::translate('label.element.link', 'Link (a)'),
                default => Localization::translate('label.element.inline', 'Inline text (span)'),
            },
            'selected' => in_array($element, $selected, true)
                || ($element === 'headings' && array_intersect(self::HEADINGS, $selected) !== []),
        ], ['span', 'p', 'headings', 'a']);
    }

    public function getAll(): array
    {
        $configuration = self::loadPreset();
        $settings = array_replace(['overwriteDefaults' => false, 'styles' => [], 'enableColors' => false, 'colors' => [], 'enableFonts' => false, 'fonts' => [], 'enableSizes' => false, 'sizes' => [], 'defaultFontSizes' => []], $configuration['rteManager'] ?? []);
        unset($settings['overwriteFontSizes']);
        $settings += ['enableIcons' => false, 'icons' => []];
        return $settings;
    }

    public function normalize(array $input): array
    {
        $settings = [
            'overwriteDefaults' => filter_var($input['overwriteDefaults'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'styles' => [],
            'enableColors' => filter_var($input['enableColors'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'colors' => [],
            'enableFonts' => filter_var($input['enableFonts'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'enableSizes' => filter_var($input['enableSizes'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sizes' => [],
        ];
        if (!is_array($input['styles'] ?? [])) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.rte.styles', 'Invalid RTE styles.'));
        }
        $names = [];
        foreach (array_values($input['styles'] ?? []) as $index => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.rte.style.row', 'Invalid RTE style row.'));
            }
            $style = [];
            if (is_array($row['elements'] ?? null)) {
                $expanded = [];
                foreach ($row['elements'] as $element) {
                    if ($element === 'headings') {
                        array_push($expanded, ...self::HEADINGS);
                        continue;
                    }
                    if (!is_string($element) || !in_array($element, self::ELEMENTS, true)) {
                        throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.select.supported.html.elements', 'Select supported HTML elements.'));
                    }
                    $expanded[] = $element;
                }
                $row['elements'] = implode(', ', $expanded);
            }
            foreach (['title', 'elements', 'classes'] as $field) {
                if (!is_string($row[$field] ?? '')) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.rte.style.field', 'Invalid RTE style field.'));
                }
                $style[$field] = trim($row[$field] ?? '');
            }
            if (implode('', $style) === '') {
                continue;
            }
            $number = $index + 1;
            if (in_array('', $style, true)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.rte.style.1.s.enter.a.display.name.elements.and.css.class', 'RTE style %1$s: enter a display name, elements and CSS class.', [$number]));
            }
            // Percent signs are reserved for TYPO3 YAML placeholders.
            if (strlen($style['title']) > 120 || str_contains($style['title'], '%')) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.rte.style.1.s.use.a.name.of.at.most.120.characters.without.percent.signs', 'RTE style %1$s: use a name of at most 120 characters without percent signs.', [$number]));
            }
            if (isset($names[$style['title']])) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.rte.style.1.s.use.a.unique.display.name', 'RTE style %1$s: use a unique display name.', [$number]));
            }
            $names[$style['title']] = true;
            $elements = array_values(array_unique(preg_split('/[\s,]+/', strtolower($style['elements']), -1, PREG_SPLIT_NO_EMPTY)));
            if ($elements === [] || array_diff($elements, self::ELEMENTS) !== []) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.rte.style.1.s.enter.supported.html.elements.for.example.p.h2.span', 'RTE style %1$s: enter supported HTML elements, for example p, h2, span.', [$number]));
            }
            $classes = array_values(array_unique(preg_split('/\s+/', $style['classes'], -1, PREG_SPLIT_NO_EMPTY)));
            foreach ($classes as $class) {
                if (!preg_match('/^-?[_a-zA-Z][_a-zA-Z0-9-]*$/D', $class)) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.rte.style.1.s.enter.css.class.names.without.dots.separated.by.spaces', 'RTE style %1$s: enter CSS class names without dots, separated by spaces.', [$number]));
                }
            }
            $style['elements'] = implode(', ', $elements);
            $style['classes'] = implode(' ', $classes);
            $style = array_merge($style, ElementDefaults::normalize(['p' => $row])['p']);
            $settings['styles'][] = $style;
        }
        if (!is_array($input['colors'] ?? [])) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.rte.colors', 'Invalid RTE colors.'));
        }
        foreach (array_values($input['colors'] ?? []) as $index => $row) {
            if (!is_array($row) || !is_string($row['label'] ?? '') || !is_string($row['color'] ?? '')) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.rte.color.row', 'Invalid RTE color row.'));
            }
            $label = trim($row['label'] ?? '');
            $color = trim($row['color'] ?? '');
            if ($label === '' && $color === '') {
                continue;
            }
            $number = $index + 1;
            if ($label === '' || strlen($label) > 120 || str_contains($label, '%')) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.color.1.s.enter.a.name.of.at.most.120.characters.without.percent.signs', 'Color %1$s: enter a name of at most 120 characters without percent signs.', [$number]));
            }
            if (!preg_match('/^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/D', $color)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.color.1.s.enter.a.hex.color.such.as.336699', 'Color %1$s: enter a hex color such as #336699.', [$number]));
            }
            if (strlen($color) === 4) {
                $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
            }
            $settings['colors'][] = ['label' => $label, 'color' => strtolower($color)];
        }
        if (!is_array($input['sizes'] ?? [])) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.rte.font.sizes', 'Invalid RTE font sizes.'));
        }
        $settings['sizes'] = FontSizes::normalize($input['sizes'] ?? []);
        $settings['defaultFontSizes'] = FontSizes::normalizeDefaults($input['defaultFontSizes'] ?? []);
        $settings['elementDefaults'] = ElementDefaults::normalize($input['elementDefaults'] ?? []);
        $settings['icons'] = Icons::normalize($input['icons'] ?? []);
        $settings['enableIcons'] = filter_var($input['enableIcons'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $defaultColor = $input['defaultColor'] ?? '';
        if (!is_string($defaultColor) || ($defaultColor !== '' && !preg_match('/^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/D', $defaultColor))) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.default.color.enter.a.hex.color.or.leave.blank', 'Default color: enter a hex color or leave blank.'));
        }
        if (strlen($defaultColor) === 4) {
            $defaultColor = '#' . $defaultColor[1] . $defaultColor[1] . $defaultColor[2] . $defaultColor[2] . $defaultColor[3] . $defaultColor[3];
        }
        $settings['defaultColor'] = strtolower($defaultColor);
        return $settings;
    }

    public function buildPreset(array $settings): array
    {
        // Copy the core preset's own configuration, keeping its base/plugin imports.
        // Importing Default.yaml itself would append lists and retain unwanted defaults.
        $preset = Yaml::parseFile(GeneralUtility::getFileAbsFileName('EXT:rte_ckeditor/Configuration/RTE/Default.yaml'));
        $config = &$preset['editor']['config'];
        if ($settings['overwriteDefaults']) {
            $config['style']['definitions'] = [];
            $config['heading']['options'] = [['model' => 'paragraph', 'title' => 'Paragraph']];
            for ($level = 1; $level <= 6; $level++) {
                $config['heading']['options'][] = ['model' => 'heading' . $level, 'view' => 'h' . $level, 'title' => 'Heading ' . $level];
            }
        }
        foreach ($settings['styles'] as $style) {
            $elements = explode(', ', $style['elements']);
            foreach ($elements as $element) {
                $config['style']['definitions'][] = [
                    'name' => $style['title'] . (count($elements) > 1 ? ' (' . $element . ')' : ''),
                    'element' => $element,
                    'classes' => explode(' ', $style['classes']),
                ];
            }
        }
        if (($settings['enableColors'] ?? false) || ($settings['colors'] ?? []) !== []) {
            $config['importModules'][] = ['module' => '@ckeditor/ckeditor5-font', 'exports' => ['FontColor']];
            if ($settings['enableColors'] ?? false) {
                $config['toolbar']['items'][] = 'fontColor';
            }
            $config['fontColor']['colors'] = FormattingClasses::palette($settings);
            $config['fontColor']['colorPicker'] = false;
            $config['fontColor']['documentColors'] = 0;
        }
        if (($settings['enableFonts'] ?? false) || HostedFonts::all($settings) !== []) {
            // Keep the converter and font faces when the selector is disabled so existing content retains its font.
            $config['importModules'][] = ['module' => '@ckeditor/ckeditor5-font', 'exports' => ['FontFamily']];
            $config['fontFamily']['options'] = ['default'];
            foreach (array_unique(array_column($settings['fonts'] ?? [], 'family')) as $family) {
                $config['fontFamily']['options'][] = $family;
            }
            if ($settings['enableFonts'] ?? false) {
                $config['toolbar']['items'][] = 'fontFamily';
            }
            if (HostedFonts::all($settings) !== []) {
                $config['contentsCss'][] = HostedFonts::stylesheetPath(HostedFonts::all($settings));
            }
        }
        if (($settings['enableSizes'] ?? false) || ($settings['sizes'] ?? []) !== []) {
            $config['importModules'][] = ['module' => '@ckeditor/ckeditor5-font', 'exports' => ['FontSize']];
            $config['fontSize']['options'] = FontSizes::options($settings['sizes'] ?? []);
            if ($settings['enableSizes'] ?? false) {
                $config['toolbar']['items'][] = 'fontSize';
            }
        }
        $config['importModules'][] = ['module' => '@kst3/rte-manager/formatting-classes.js', 'exports' => ['FormattingClasses']];
        $config['rteManagerClasses'] = FormattingClasses::mappings($settings);
        $config['rteManagerRootSize'] = $settings['defaultFontSizes']['html'] ?? '';
        $config['importModules'][] = ['module' => '@kst3/rte-manager/rte-icon-plugin.js', 'exports' => ['RteIcons']];
        $config['rteManagerIcons'] = $settings['icons'] ?? [];
        if ($settings['enableIcons'] ?? false) $config['toolbar']['items'][] = 'rteIcon';
        $processing = Yaml::parseFile(GeneralUtility::getFileAbsFileName('EXT:rte_ckeditor/Configuration/RTE/Processing.yaml'))['processing'];
        $preset['processing']['allowTags'] = array_values(array_unique(array_merge($processing['allowTags'], Icons::TAGS)));
        $preset['processing']['HTMLparser_db']['tags']['span']['allowedAttribs'] = 'id,class,style,title,lang,xml:lang,dir,itemscope,itemtype,itemprop,data-rte-icon,aria-label';
        foreach (Icons::TAGS as $tag) {
            $preset['processing']['HTMLparser_db']['tags'][$tag]['allowedAttribs'] = implode(',', array_merge(Icons::ATTRIBUTES, ['viewbox', 'aria-hidden', 'focusable']));
        }
        $config['contentsCss'][] = FormattingClasses::stylesheetPath($settings);
        $preset['rteManager'] = $settings;
        return $preset;
    }

    public function save(array $settings): void
    {
        $preset = $this->buildPreset($settings);
        $yaml = "# Generated by Theme > RTE Manager. Edit through the settings form.\n"
            . Yaml::dump($preset, 8, 2);
        $fonts = HostedFonts::all($settings);
        // Generate everything before touching live files; publish YAML last.
        (new PresetWriter())->write([
            Environment::getPublicPath() . '/' . HostedFonts::stylesheetPath($fonts) => HostedFonts::buildCss($fonts),
            Environment::getPublicPath() . '/' . FormattingClasses::stylesheetPath($settings) => FormattingClasses::buildCss($settings),
            self::presetPath() => $yaml,
        ], self::presetPath() . '.lock');
    }
}
