#!/usr/bin/php
<?php

function printHelp() {
    echo "Usage: php merge_templates.php [OPTIONS] path_source_dir/ path_target_dir/\n\n";
    echo "[OPTIONS]\n";
    echo "  --force               Overwrite files in path_target_dir/ without prompting\n";
    echo "  --help, -h            Display this help message\n";
    echo "  --version, -v         Display the version of the script\n";
}

function printVersion() {
    echo "merge_templates.php version 1.0.0\n";
}

function mergeAddContent($sourceFilePath, $targetFilePath) {
    $lines = file($sourceFilePath, FILE_IGNORE_NEW_LINES);
    $placeholder = array_shift($lines);

    if (!file_exists($targetFilePath)) {
        throw new Exception("Target file $targetFilePath does not exist.");
    }

    $targetLines = file($targetFilePath, FILE_IGNORE_NEW_LINES);
    $newContent = [];

    foreach ($targetLines as $line) {
        $newContent[] = $line;
        if (strpos($line, $placeholder) !== false) {
            foreach ($lines as $newLine) {
                $newContent[] = $newLine;
            }
        }
    }

    file_put_contents($targetFilePath, implode(PHP_EOL, $newContent));
}

function processDirectory($sourceDir, $targetDir, $force = false) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $targetPath = str_replace($sourceDir, $targetDir, $item->getPathname());

        if ($item->isDir()) {
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0777, true);
            }
        } else {
            $filename = $item->getFilename();
            if (preg_match('/^\+\d+_(.+)$/', $filename, $matches)) {
                $targetFileName = $matches[1];
                $targetFilePath = $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPath() . DIRECTORY_SEPARATOR . $targetFileName;

                mergeAddContent($item->getPathname(), $targetFilePath);
            } else {
                if (file_exists($targetPath) && !$force) {
                    throw new Exception("File $targetPath already exists. Use --force to overwrite.");
                }
                copy($item->getPathname(), $targetPath);
            }
        }
    }
}

$options = getopt("hv", ["help", "version", "force"]);

if (isset($options['h']) || isset($options['help'])) {
    printHelp();
    exit(0);
}

if (isset($options['v']) || isset($options['version'])) {
    printVersion();
    exit(0);
}

if ($argc < 3) {
    echo "Error: Missing required arguments.\n";
    printHelp();
    exit(1);
}

$sourceDir = rtrim($argv[$argc - 2], '/') . '/';
$targetDir = rtrim($argv[$argc - 1], '/') . '/';

if (!is_dir($sourceDir)) {
    echo "Error: Source directory $sourceDir does not exist.\n";
    exit(1);
}

if (!is_dir($targetDir)) {
    echo "Error: Target directory $targetDir does not exist.\n";
    exit(1);
}

$force = isset($options['force']);

try {
    processDirectory($sourceDir, $targetDir, $force);
    echo "Merge completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}