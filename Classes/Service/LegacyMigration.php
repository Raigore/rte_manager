<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/** Preserve installations made with the unreleased rte_management key. */
final class LegacyMigration
{
    public static function migrate(string $publicPath): bool
    {
        $oldDirectory = $publicPath . '/fileadmin/rte_management';
        $newDirectory = $publicPath . '/fileadmin/rte_manager';
        $oldPreset = $oldDirectory . '/Configuration/Generated.yaml';
        $newPreset = $newDirectory . '/Configuration/Generated.yaml';
        if (is_file($newPreset)) return true;
        if (!is_file($oldPreset)) return false;

        $preset = Yaml::parseFile($oldPreset);
        if (!is_array($preset)) {
            throw new \RuntimeException('The existing RTE Manager preset must contain a YAML mapping.');
        }
        if (array_key_exists('rteManagement', $preset)) {
            $preset['rteManager'] = $preset['rteManagement'];
            unset($preset['rteManagement']);
        }
        $config = &$preset['editor']['config'];
        foreach (['Classes', 'RootSize', 'Icons'] as $suffix) {
            if (array_key_exists('rteManagement' . $suffix, $config ?? [])) {
                $config['rteManager' . $suffix] = $config['rteManagement' . $suffix];
                unset($config['rteManagement' . $suffix]);
            }
        }
        foreach ($config['importModules'] ?? [] as $index => $module) {
            if (is_array($module) && str_starts_with($module['module'] ?? '', '@placeholder/rte-management/')) {
                $config['importModules'][$index]['module'] = '@kst3/rte-manager/' . substr($module['module'], strlen('@placeholder/rte-management/'));
            }
        }
        foreach ($config['contentsCss'] ?? [] as $index => $path) {
            if (is_string($path) && str_starts_with($path, 'fileadmin/rte_management/')) {
                $config['contentsCss'][$index] = 'fileadmin/rte_manager/' . substr($path, strlen('fileadmin/rte_management/'));
            }
        }
        unset($config);
        $filesystem = new Filesystem();
        foreach (['fonts', 'styles'] as $directory) {
            if (is_dir($oldDirectory . '/' . $directory)) {
                $filesystem->mirror($oldDirectory . '/' . $directory, $newDirectory . '/' . $directory);
            }
        }
        // Publish the preset last. Keep the old files intact for rollback.
        $filesystem->dumpFile($newPreset, Yaml::dump($preset, 8, 2));
        return true;
    }
}
