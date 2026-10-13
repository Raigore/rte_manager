<?php

declare(strict_types=1);

namespace Kst3\RteManager\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/** Serialize saves and restore the previous files when publishing fails. */
final class PresetWriter
{
    public function __construct(private readonly Filesystem $filesystem = new Filesystem()) {}

    /** @param array<string, string> $files Files in publication order. */
    public function write(array $files, string $lockPath): void
    {
        $this->filesystem->mkdir(dirname($lockPath));
        $lock = @fopen($lockPath, 'c');
        if ($lock === false) throw new IOException('Cannot open the RTE settings lock.', 0, null, $lockPath);
        try {
            if (!flock($lock, LOCK_EX)) throw new IOException('Cannot lock RTE settings.');
            $previous = [];
            foreach ($files as $path => $contents) {
                if (file_exists($path) && !is_file($path)) {
                    throw new IOException('The preset target is not a regular file.', 0, null, $path);
                }
                $previous[$path] = is_file($path) ? @file_get_contents($path) : null;
                if ($previous[$path] === false) throw new IOException('Cannot back up the preset file.', 0, null, $path);
            }
            $written = [];
            try {
                foreach ($files as $path => $contents) {
                    $this->filesystem->dumpFile($path, $contents);
                    $written[] = $path;
                }
            } catch (\Throwable $exception) {
                $failed = [];
                foreach (array_reverse($written) as $path) {
                    try {
                        if ($previous[$path] === null) {
                            $this->filesystem->remove($path);
                        } else {
                            $this->filesystem->dumpFile($path, $previous[$path]);
                        }
                    } catch (\Throwable) {
                        $failed[] = $path;
                    }
                }
                if ($failed !== []) {
                    throw new PresetRollbackException('RTE settings rollback failed. Restore the preset and generated CSS from backup.', 0, $exception);
                }
                throw $exception;
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
