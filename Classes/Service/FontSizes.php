<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

final class FontSizes
{
    public const ELEMENTS = ['html' => 'Default (html)'];

    public static function normalizeDefaults(mixed $input): array
    {
        if (!is_array($input)) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.default.font.sizes', 'Invalid default font sizes.'));
        }
        $values = [];
        foreach (self::ELEMENTS as $element => $label) {
            try {
                $rows = self::normalize([['value' => $input[$element] ?? '']]);
            } catch (\InvalidArgumentException) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.1.s.enter.a.positive.css.size.such.as.16px.or.1.25rem.or.leave.blank', '%1$s: enter a positive CSS size such as 16px or 1.25rem, or leave blank.', [$label]));
            }
            $values[$element] = $rows[0]['value'] ?? '';
        }
        return $values;
    }

    public static function normalize(array $rows): array
    {
        $sizes = [];
        $seen = [];
        $names = [];
        foreach (array_values($rows) as $index => $row) {
            if (!is_array($row) || !is_string($row['value'] ?? '') || !is_string($row['name'] ?? '')) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.font.size.row', 'Invalid font size row.'));
            }
            $value = strtolower(trim($row['value'] ?? ''));
            if ($value === '') {
                if (trim($row['name'] ?? '') !== '') {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.enter.a.font.size.for.each.named.class', 'Enter a font size for each named class.'));
                }
                continue;
            }
            $number = $index + 1;
            if (strlen($value) > 32 || !preg_match('/^(\d+(?:\.\d+)?|\.\d+)(px|rem|em|%|pt|vw|vh|vmin|vmax|ch|ex)?$/D', $value, $match)
                || (float)$match[1] <= 0) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.size.1.s.enter.a.positive.number.or.css.size.such.as.16.18px.1.25rem.or.120', 'Size %1$s: enter a positive number or CSS size such as 16, 18px, 1.25rem or 120%%.', [$number]));
            }
            // Canonical decimal strings avoid rounding and duplicate entries such as 16 and 16.0px.
            $parts = explode('.', $match[1], 2);
            $integer = ltrim($parts[0], '0');
            $fraction = rtrim($parts[1] ?? '', '0');
            $value = ($integer === '' ? '0' : $integer) . ($fraction !== '' ? '.' . $fraction : '') . ($match[2] ?? 'px');
            if (isset($seen[$value])) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.size.1.s.2.s.is.already.in.the.list', 'Size %1$s: %2$s is already in the list.', [$number, $value]));
            }
            $seen[$value] = true;
            $name = strtolower(trim($row['name'] ?? ''));
            if ($name === '') {
                $name = substr(FormattingClasses::className('size', str_replace('%', 'percent', $value)), 5);
            }
            if (strlen($name) > 80 || !preg_match('/^[a-z0-9_-]+$/D', $name) || isset($names[$name])) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.a.unique.size.class.name.with.letters.numbers.hyphens.or.underscores.without.font', 'Use a unique size class name with letters, numbers, hyphens or underscores (without font-size-).'));
            }
            $names[$name] = true;
            $sizes[] = ['value' => $value, 'name' => $name];
        }
        return $sizes;
    }

    public static function options(array $sizes): array
    {
        $options = ['default'];
        foreach ($sizes as $size) {
            $options[] = [
                'title' => $size['value'],
                'model' => $size['value'],
                'view' => ['name' => 'span', 'styles' => ['font-size' => $size['value']]],
            ];
        }
        return $options;
    }
}
