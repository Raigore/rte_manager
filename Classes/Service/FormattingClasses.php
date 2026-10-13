<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

use Symfony\Component\Filesystem\Filesystem;
use TYPO3\CMS\Core\Core\Environment;

final class FormattingClasses
{
    public static function className(string $prefix, string $name): string
    {
        $slug = trim((string)preg_replace('/[^\p{L}\p{N}_-]+/u', '-', mb_strtolower($name)), '-');
        if ($slug === '') {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.font.and.color.names.must.contain.letters.or.numbers.to.create.a.css.class', 'Font and color names must contain letters or numbers to create a CSS class.'));
        }
        return $prefix . '-' . $slug;
    }

    public static function palette(array $settings): array
    {
        if (($settings['colors'] ?? []) !== []) {
            return $settings['colors'];
        }
        // Named equivalents of CKEditor's standard palette; also usable as CSS classes.
        $colors = [
            'Black' => 'hsl(0, 0%, 0%)', 'Dim grey' => 'hsl(0, 0%, 30%)',
            'Grey' => 'hsl(0, 0%, 60%)', 'Light grey' => 'hsl(0, 0%, 90%)',
            'White' => 'hsl(0, 0%, 100%)', 'Red' => 'hsl(0, 75%, 60%)',
            'Orange' => 'hsl(30, 75%, 60%)', 'Yellow' => 'hsl(60, 75%, 60%)',
            'Light green' => 'hsl(90, 75%, 60%)', 'Green' => 'hsl(120, 75%, 60%)',
            'Aquamarine' => 'hsl(150, 75%, 60%)', 'Turquoise' => 'hsl(180, 75%, 60%)',
            'Light blue' => 'hsl(210, 75%, 60%)', 'Blue' => 'hsl(240, 75%, 60%)',
            'Purple' => 'hsl(270, 75%, 60%)',
        ];
        $palette = [];
        foreach ($colors as $label => $color) {
            $palette[] = ['label' => $label, 'color' => $color];
        }
        return $palette;
    }

    public static function mappings(array $settings): array
    {
        $mappings = [];
        $classes = [];
        $colorValues = [];
        // Include the default palette even when its toolbar is disabled, for existing content.
        foreach (self::palette($settings) as $color) {
            $class = self::className('color', $color['label']);
            if (isset($classes[$class]) || isset($colorValues[$color['color']])) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.unique.color.names.and.color.values.each.color.needs.its.own.css.class', 'Use unique color names and color values; each color needs its own CSS class.'));
            }
            $classes[$class] = true;
            $colorValues[$color['color']] = true;
            $mappings[] = ['property' => 'color', 'value' => $color['color'], 'className' => $class];
        }
        foreach (array_unique(array_column($settings['fonts'] ?? [], 'family')) as $family) {
            $class = self::className('font', $family);
            if (isset($classes[$class])) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.font.family.names.must.produce.different.css.classes', 'Font family names must produce different CSS classes.'));
            }
            $classes[$class] = true;
            $mappings[] = ['property' => 'font-family', 'value' => $family, 'className' => $class];
        }
        foreach (FontSizes::normalize($settings['sizes'] ?? []) as $size) {
            $mappings[] = [
                'property' => 'font-size',
                'value' => $size['value'],
                'className' => 'font-size-' . $size['name'],
            ];
            // Keep previously saved size-* classes readable and styled.
            $mappings[] = [
                'property' => 'font-size',
                'value' => $size['value'],
                'className' => self::className('size', str_replace('%', 'percent', $size['value'])),
            ];
        }
        return $mappings;
    }

    public static function buildCss(array $settings): string
    {
        $css = "/* Named font and color classes managed by RTE Manager. */\n";
        $css .= ".rte-icon { display: inline-block; line-height: 1; vertical-align: -0.125em; }\n.rte-icon svg { width: 1em; height: 1em; display: block; }\n";
        // TYPO3 scopes :root to the editable content when loading this in the RTE.
        $css .= ":root {\n";
        foreach (self::palette($settings) as $color) {
            $css .= '  --' . self::className('color', $color['label']) . ': ' . $color['color'] . ";\n";
        }
        $css .= "}\n";
        if (!empty($settings['defaultFont']['file'])) {
            $css .= "body { font-family: \"RteDefaultFont\" !important; }\n";
            $css .= 'body { font-weight: ' . (str_contains($settings['defaultFont']['weight'], ' ') ? '400' : $settings['defaultFont']['weight']) . '; font-style: ' . $settings['defaultFont']['style'] . "; }\n";
        }
        if (($settings['defaultColor'] ?? '') !== '') {
            $css .= 'body { color: ' . $settings['defaultColor'] . " !important; }\n";
        }
        foreach (FontSizes::normalizeDefaults($settings['defaultFontSizes'] ?? []) as $element => $value) {
            if ($value !== '') {
                $css .= $element . ' { font-size: ' . $value . " !important; }\n";
            }
        }
        foreach (self::mappings($settings) as $mapping) {
            $value = $mapping['property'] === 'font-family' ? '"' . $mapping['value'] . '"' : $mapping['value'];
            $css .= '.' . $mapping['className'] . ' { ' . $mapping['property'] . ': ' . $value . "; }\n";
        }
        $css .= ElementDefaults::css($settings);
        foreach ($settings['styles'] ?? [] as $style) {
            $classes = '.' . implode('.', preg_split('/\s+/', trim($style['classes'])));
            $selectors = array_map(static fn(string $element): string => trim($element) . $classes, explode(',', $style['elements']));
            $styleSettings = array_replace($settings, ['elementDefaults' => ['p' => $style]]);
            $css .= ElementDefaults::css($styleSettings, ['p' => implode(', ', $selectors)]);
        }
        return $css;
    }

    public static function stylesheetPath(array $settings): string
    {
        return 'fileadmin/rte_manager/styles/classes.css';
    }

    public static function writeStylesheet(array $settings): void
    {
        (new Filesystem())->dumpFile(Environment::getPublicPath() . '/' . self::stylesheetPath($settings), self::buildCss($settings));
    }
}
