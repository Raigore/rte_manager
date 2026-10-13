<?php
declare(strict_types=1);

namespace Kst3\RteManager\Service;

use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;

final class Icons
{
    public const TAGS = ['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon'];
    public const ATTRIBUTES = ['xmlns', 'viewBox', 'width', 'height', 'fill', 'fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'opacity', 'fill-opacity', 'stroke-opacity', 'transform', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points'];

    public static function sanitize(string $svg): string
    {
        if (strlen($svg) > 50000 || preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
            throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.an.svg.of.at.most.50.kb.without.a.document.type', 'Use an SVG of at most 50 KB without a document type.'));
        }
        $clean = (new SvgSanitizer())->sanitizeContent($svg, true, true);
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if ($clean === '' || !$document->loadXML($clean, LIBXML_NONET) || $document->documentElement?->localName !== 'svg') {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.paste.a.complete.svg.starting.with.svg', 'Paste a complete SVG, starting with <svg>.'));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        foreach ($document->getElementsByTagName('*') as $node) {
            if (!in_array($node->localName, self::TAGS, true)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.a.self.contained.svg.made.from.paths.and.shapes.convert.text.references.and.effec', 'Use a self-contained SVG made from paths and shapes; convert text, references and effects to paths first.'));
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                if (!in_array($attribute->name, self::ATTRIBUTES, true) || preg_match('/url\s*\(/i', $attribute->value)) $node->removeAttributeNode($attribute);
            }
            if ($node->hasAttribute('stroke') && strtolower(trim($node->getAttribute('stroke'))) !== 'none') {
                $node->setAttribute('stroke', 'currentColor');
            }
        }
        $root = $document->documentElement;
        if (!$root->hasAttribute('viewBox')) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.svg.icons.need.a.viewbox.attribute.so.they.scale.with.text', 'SVG icons need a viewBox attribute so they scale with text.'));
        $root->setAttribute('width', '1em');
        if (!$root->hasAttribute('fill')) $root->setAttribute('fill', 'currentColor');
        $root->setAttribute('height', '1em');
        $root->setAttribute('aria-hidden', 'true');
        $root->setAttribute('focusable', 'false');
        return $document->saveXML($root);
    }

    public static function normalize(mixed $rows): array
    {
        if (!is_array($rows)) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.icons', 'Invalid icons.'));
        $icons = [];
        $seen = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['name'] ?? '') || !is_string($row['svg'] ?? '')) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.icon.row', 'Invalid icon row.'));
            $name = trim($row['name'] ?? '');
            $svg = trim($row['svg'] ?? '');
            if ($name === '' && $svg === '') continue;
            if ($name === '' || strlen($name) > 120 || str_contains($name, '%')) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.enter.an.icon.name.of.up.to.120.characters.without.percent.signs', 'Enter an icon name of up to 120 characters without percent signs.'));
            $id = FormattingClasses::className('icon', $name);
            if (isset($seen[$id])) throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.use.unique.icon.names', 'Use unique icon names.'));
            $seen[$id] = true;
            $icons[] = ['name' => $name, 'id' => $id, 'svg' => self::sanitize($svg)];
        }
        return $icons;
    }
}
