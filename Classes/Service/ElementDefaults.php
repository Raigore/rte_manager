<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

final class ElementDefaults
{
    public const ELEMENTS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a'];
    public const FIELDS = ['font' => 'Font name', 'color' => 'Color name', 'size' => 'Size name', 'weight' => 'Font weight', 'fontStyle' => 'Font style', 'lineHeight' => 'Line height', 'letterSpacing' => 'Letter spacing', 'transform' => 'Text transform', 'decoration' => 'Text decoration'];
    private const OPTIONS = [
        'weight' => ['100', '200', '300', '400', '500', '600', '700', '800', '900'],
        'fontStyle' => ['normal', 'italic'],
        'transform' => ['none', 'uppercase', 'lowercase', 'capitalize'],
        'decoration' => ['none', 'underline', 'line-through'],
    ];

    public static function normalize(mixed $input): array
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.element.defaults', 'Invalid element defaults.'));
        }
        $result = [];
        foreach (self::ELEMENTS as $element) {
            $row = $input[$element] ?? [];
            if (!is_array($row)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.element.defaults', 'Invalid element defaults.'));
            }
            foreach (self::FIELDS as $field => $label) {
                $value = $row[$field] ?? '';
                if (!is_string($value)) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.default.selection', 'Invalid default selection.'));
                }
                $value = trim($value);
                if ($value !== '' && isset(self::OPTIONS[$field]) && !in_array($value, self::OPTIONS[$field], true)) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.1.s.select.a.valid.2.s', '%1$s: select a valid %2$s.', [$element, $label]));
                }
                if ($value !== '' && in_array($field, ['lineHeight', 'letterSpacing'], true)) {
                    $value = strtolower($value);
                    $pattern = $field === 'lineHeight'
                        ? '/^(?:normal|(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%|pt|vw|vh|ch|ex)?)$/D'
                        : '/^(?:normal|0|-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|pt|vw|vh|ch|ex))$/D';
                    if (strlen($value) > 32 || !preg_match($pattern, $value)) {
                        throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.1.s.enter.a.valid.2.s.or.leave.blank', '%1$s: enter a valid %2$s, or leave blank.', [$element, $label]));
                    }
                }
                $result[$element][$field] = $value;
            }
            $legacyProperties = ['weight' => 'font-weight', 'fontStyle' => 'font-style', 'lineHeight' => 'line-height', 'letterSpacing' => 'letter-spacing', 'transform' => 'text-transform', 'decoration' => 'text-decoration'];
            $css = $row['css'] ?? null;
            if ($css === null) {
                $css = '';
                foreach ($legacyProperties as $field => $property) {
                    if ($result[$element][$field] !== '') {
                        $css .= $property . ': ' . $result[$element][$field] . ";\n";
                    }
                }
            }
            $result[$element]['css'] = self::declarations($css);
            foreach ($legacyProperties as $field => $property) unset($result[$element][$field]);
        }
        return $result;
    }

    public static function declarations(mixed $css): string
    {
        if (!is_string($css) || strlen($css) > 8000) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.enter.css.declarations.of.at.most.8000.characters', 'Enter CSS declarations of at most 8000 characters.'));
        }
        AdditionalCss::rules($css);
        return trim($css);
    }

    public static function choices(array $settings): array
    {
        $choices = ['font' => [], 'color' => [], 'size' => []];
        foreach (self::OPTIONS as $field => $values) {
            $choices[$field] = array_combine($values, $values);
        }
        foreach ($settings['fonts'] ?? [] as $font) {
            $choices['font'][$font['family']] = $font['family'];
        }
        foreach ($settings['colors'] ?? [] as $color) {
            $choices['color'][$color['label']] = $color['color'];
        }
        foreach (FontSizes::normalize($settings['sizes'] ?? []) as $size) {
            $choices['size'][$size['name']] = $size['value'];
        }
        return $choices;
    }

    public static function rows(array $settings): array
    {
        $choices = self::choices($settings);
        $rows = [];
        foreach (self::normalize($settings['elementDefaults'] ?? []) as $element => $values) {
            $fields = [];
            foreach (self::FIELDS as $field => $label) {
                if (!in_array($field, ['font', 'color', 'size'], true)) continue;
                $options = [['value' => '', 'label' => \Kst3\RteManager\Service\Localization::translate('label.default', 'Default'), 'selected' => $values[$field] === '']];
                foreach ($choices[$field] as $name => $value) {
                    $options[] = ['value' => $name, 'label' => $name, 'selected' => $values[$field] === (string)$name];
                }
                $fields[] = ['name' => $field, 'label' => \Kst3\RteManager\Service\Localization::translate('field.' . $field, $label), 'options' => $options];
            }
            $rows[] = ['element' => $element, 'fields' => $fields, 'css' => $values['css']];
        }
        return $rows;
    }

    public static function css(array $settings, array $selectors = []): string
    {
        $choices = self::choices($settings);
        $properties = ['font' => 'font-family', 'color' => 'color', 'size' => 'font-size', 'weight' => 'font-weight', 'fontStyle' => 'font-style', 'lineHeight' => 'line-height', 'letterSpacing' => 'letter-spacing', 'transform' => 'text-transform', 'decoration' => 'text-decoration'];
        $css = '';
        foreach (self::normalize($settings['elementDefaults'] ?? []) as $element => $values) {
            $element = $selectors[$element] ?? $element;
            foreach ($values as $field => $name) {
                if ($field === 'css') continue;
                if ($name === '') continue;
                $direct = in_array($field, ['lineHeight', 'letterSpacing'], true);
                if (!$direct && !isset($choices[$field][$name])) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.default.for.1.s.select.an.existing.2.s.or.default.save.new.entries.before.selecting.t', 'Default for %1$s: select an existing %2$s, or Default. Save new entries before selecting them.', [$element, $field]));
                }
                $value = $direct ? $name : $choices[$field][$name];
                if ($field === 'font') $value = '"' . $value . '"';
                if (in_array($field, ['size', 'lineHeight', 'letterSpacing'], true) && str_ends_with($value, 'rem')) {
                    $value = 'calc(' . substr($value, 0, -3) . ' * var(--rte-managed-rem, 1rem))';
                }
                $css .= $element . ' { ' . $properties[$field] . ': ' . $value . " !important; }\n";
            }
            $css .= AdditionalCss::compile($values['css'], $element);
        }
        return $css;
    }
}
