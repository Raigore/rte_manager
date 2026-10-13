<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Filesystem;
use TYPO3\CMS\Core\Core\Environment;

final class HostedFonts
{
    public const DIRECTORY = 'fileadmin/rte_manager/fonts/';
    public const MAX_BYTES = 10485760;
    public static function all(array $settings): array
    {
        $fonts = $settings['fonts'] ?? [];
        if (!empty($settings['defaultFont']['file'])) $fonts[] = $settings['defaultFont'];
        return $fonts;
    }
    private const SIGNATURES = ['woff2' => 'wOF2', 'woff' => 'wOFF', 'ttf' => "\x00\x01\x00\x00", 'otf' => 'OTTO'];

    public function prepare(array $rows, array $uploads): array
    {
        $fonts = [];
        $pending = [];
        $faces = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.font.row', 'Invalid font row.'));
            }
            foreach (['family', 'file', 'weight', 'style'] as $key) {
                if (isset($row[$key]) && !is_string($row[$key])) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.font.field', 'Invalid font field.'));
                }
            }
            $family = trim($row['family'] ?? '');
            $file = $row['file'] ?? '';
            $upload = $uploads[$index] ?? null;
            if ($upload !== null && !$upload instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.invalid.font.upload', 'Invalid font upload.'));
            }
            $hasUpload = $upload !== null && $upload->getError() !== UPLOAD_ERR_NO_FILE;
            if ($family === '' && $file === '' && !$hasUpload) {
                continue;
            }
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9 _-]{0,79}$/D', $family)
                || in_array(strtolower($family), ['default', 'inherit', 'initial', 'unset', 'revert', 'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui'], true)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.enter.a.font.family.name.starting.with.a.letter.using.letters.numbers.spaces.hyphens', 'Enter a font family name starting with a letter, using letters, numbers, spaces, hyphens or underscores (up to 80 characters).'));
            }
            $weight = $row['weight'] ?? '400';
            $style = $row['style'] ?? 'normal';
            if (!in_array($weight, ['100', '200', '300', '400', '500', '600', '700', '800', '900', '100 900'], true)
                || !in_array($style, ['normal', 'italic'], true)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.select.a.supported.font.weight.and.style', 'Select a supported font weight and style.'));
            }
            $face = strtolower($family) . ':' . $weight . ':' . $style;
            if (isset($faces[$face])) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.each.font.family.weight.and.style.combination.must.be.unique', 'Each font family, weight and style combination must be unique.'));
            }
            $faces[$face] = true;
            if ($hasUpload) {
                if ($upload->getError() !== UPLOAD_ERR_OK) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.font.upload.failed.check.the.file.size.and.try.selecting.the.file.again', 'Font upload failed. Check the file size and try selecting the file again.'));
                }
                $extension = strtolower(pathinfo($upload->getClientFilename() ?? '', PATHINFO_EXTENSION));
                if (!isset(self::SIGNATURES[$extension]) || ($upload->getSize() ?? 0) > self::MAX_BYTES) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.upload.a.woff2.woff.ttf.or.otf.font.of.at.most.10.mb', 'Upload a WOFF2, WOFF, TTF or OTF font of at most 10 MB.'));
                }
                $stream = $upload->getStream();
                $stream->rewind();
                $contents = $stream->read(self::MAX_BYTES + 1);
                if (strlen($contents) < 12 || strlen($contents) > self::MAX_BYTES || substr($contents, 0, 4) !== self::SIGNATURES[$extension]) {
                    throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.the.uploaded.file.does.not.have.a.valid.font.header.or.exceeds.10.mb', 'The uploaded file does not have a valid font header or exceeds 10 MB.'));
                }
                $file = hash('sha256', $contents) . '.' . $extension;
                $pending[$file] = $contents;
            } elseif (!preg_match('/^[a-f0-9]{64}\.(woff2|woff|ttf|otf)$/D', $file)
                || !is_file(Environment::getPublicPath() . '/' . self::DIRECTORY . $file)) {
                throw new \InvalidArgumentException(\Kst3\RteManager\Service\Localization::translate('validation.choose.a.local.font.file.for.every.new.font.row', 'Choose a local font file for every new font row.'));
            }
            $fonts[] = ['family' => $family, 'weight' => $weight, 'style' => $style, 'file' => $file];
        }
        // Validate the entire batch before writing files. Only generated font filenames are used.
        $filesystem = new Filesystem();
        foreach ($pending as $file => $contents) {
            $filesystem->dumpFile(Environment::getPublicPath() . '/' . self::DIRECTORY . $file, $contents);
        }
        return $fonts;
    }

    public static function stylesheetPath(array $fonts): string
    {
        return self::DIRECTORY . 'fonts.css';
    }

    public static function buildCss(array $fonts): string
    {
        $css = "/* Locally hosted fonts managed by RTE Manager. */\n";
        $formats = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'];
        foreach ($fonts as $font) {
            $format = $formats[pathinfo($font['file'], PATHINFO_EXTENSION)];
            $css .= '@font-face { font-family: "' . $font['family'] . '"; src: url("' . $font['file']
                . '") format("' . $format . '"); font-weight: ' . $font['weight']
                . '; font-style: ' . $font['style'] . "; font-display: swap; }\n";
        }
        return $css;
    }

    public function writeStylesheet(array $fonts): void
    {
        (new Filesystem())->dumpFile(Environment::getPublicPath() . '/' . self::stylesheetPath($fonts), self::buildCss($fonts));
    }
}
