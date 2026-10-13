<?php
declare(strict_types=1);

namespace Kst3\RteManager\Service;

final class AdditionalCss
{
    public static function rules(string $css): array
    {
        $css = preg_replace('~/\*.*?\*/~s', '', $css);
        if (preg_match('/[<>@\\\\]|url\s*\(|expression\s*\(/i', $css)) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.declarations.and.hover.style.blocks.without.imports.or.urls', 'Use declarations and &:hover-style blocks, without imports or URLs.'));
        }
        $rules = [];
        $base = '';
        $offset = 0;
        while ($offset < strlen($css)) {
            $open = strpos($css, '{', $offset);
            if ($open === false) {
                $base .= substr($css, $offset);
                break;
            }
            $prefix = substr($css, $offset, $open - $offset);
            $amp = strpos($prefix, '&');
            if ($amp === false) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.nested.selectors.must.start.with.for.example.hover', 'Nested selectors must start with &, for example &:hover.'));
            $base .= substr($prefix, 0, $amp);
            $selector = trim(substr($prefix, $amp));
            foreach (explode(',', $selector) as $state) {
                if (!preg_match('/^&(?:::{0,1}[a-zA-Z-]+(?:\([a-zA-Z0-9:+. #_-]+\))?)+$/D', trim($state))) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.state.selectors.such.as.hover.focus.visible.or.before', 'Use state selectors such as &:hover, &:focus-visible or &::before.'));
                }
            }
            $close = strpos($css, '}', $open);
            if ($close === false) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.close.each.css.state.block.with', 'Close each CSS state block with }.'));
            $body = substr($css, $open + 1, $close - $open - 1);
            self::validateBody($body);
            $rules[] = [$selector, $body];
            $offset = $close + 1;
        }
        self::validateBody($base);
        array_unshift($rules, ['&', $base]);
        return $rules;
    }

    private static function validateBody(string $body): void
    {
        foreach (explode(';', $body) as $declaration) {
            if (trim($declaration) === '') continue;
            if (!preg_match('/^\s*(?:--[a-zA-Z0-9_-]+|[a-zA-Z][a-zA-Z-]*)\s*:\s*[^:{};]+\s*$/D', $declaration)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.css.declarations.such.as.line.height.1.4.and.one.level.of.hover.style.blocks', 'Use CSS declarations such as line-height: 1.4; and one level of &:hover-style blocks.'));
            }
        }
    }

    public static function compile(string $css, string $selector): string
    {
        $result = '';
        foreach (self::rules($css) as [$state, $body]) {
            $targets = [];
            foreach (explode(',', $selector) as $parent) {
                foreach (explode(',', $state) as $child) {
                    $targets[] = str_replace('&', trim($parent), trim($child));
                }
            }
            foreach (explode(';', $body) as $declaration) {
                if (trim($declaration) === '') continue;
                $declaration = preg_replace('/\s*!important\s*$/i', '', trim($declaration));
                $declaration = preg_replace('/(?<![\w.-])(-?(?:\d*\.)?\d+)rem\b/', 'calc($1 * var(--rte-managed-rem, 1rem))', $declaration);
                $result .= implode(', ', $targets) . ' { ' . $declaration . " !important; }\n";
            }
        }
        return $result;
    }
}
