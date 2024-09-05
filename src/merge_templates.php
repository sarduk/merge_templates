<?php

function printHelp() {
    echo "Usage: php merge_templates.php [OPTIONS] path_source_dir/ path_target_dir/\n\n";
    echo "[OPTIONS]\n";
    echo "  --paste-files=true|false           Copy files from source to target directory (default: true)\n";
    echo "  --paste-files-replace=true|false   Replace existing files during copy (default: true)\n";
    echo "  --merge-contents=true|false        Process 'merge_add_content' files (default: true)\n";
    echo "  --allow-merge-contents-dups=true|false  Allow duplicates in placeholders (default: false)\n";
    echo "  --help, -h                        Display this help message\n";
    echo "  --version, -v                     Display the version of the script\n";
}

function printVersion() {
    echo "merge_templates.php version 2.0.0\n";
}

//function mergeAddContent($sourceFilePath, $targetFilePath, $allowDups) {
/**
 * Merges the content of the source file into the target file based on placeholders.
 *
 * @param string $sourceFilePath Path of the source file containing content to merge.
 * @param string $targetFilePath Path of the target file where the content will be merged.
 * @return void
 * @throws Exception If the source or target file is invalid.
 */
function mergeAddContent($sourceFilePath, $targetFilePath, $allowDups)
{
    // Ensure that source and target files exist
    if (!file_exists($sourceFilePath)) {
        throw new Exception("Source file $sourceFilePath does not exist.");
    }

    if (!file_exists($targetFilePath)) {
        throw new Exception("Target file $targetFilePath does not exist.");
    }

    // Read the source file and extract placeholders and content
    $sourceLines = file($sourceFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $placeholderStart = trim($sourceLines[0]); // First line is the opening placeholder
    $placeholderEnd = trim(end($sourceLines)); // Last line is the closing placeholder
    $sourceContent = array_slice($sourceLines, 1, -1); // Content between the placeholders

    // Read the target file
    $targetLines = file($targetFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Create a buffer to store the new content of the target file
    $newTargetContent = [];
    $insidePlaceholder = false;

    foreach ($targetLines as $line) {
        $trimmedLine = trim($line);

        if ($trimmedLine === $placeholderStart) {
            $insidePlaceholder = true;
            $newTargetContent[] = $line; // Add the opening placeholder
            foreach ($sourceContent as $contentLine) {
                $newTargetContent[] = $contentLine;
            }
        } elseif ($trimmedLine === $placeholderEnd && $insidePlaceholder) {
            // Add the content from the source file before closing the placeholder block
            $newTargetContent[] = $line; // Add the closing placeholder
            $insidePlaceholder = false;
        } else {
            if ($insidePlaceholder) {
                // If inside a placeholder block, keep the original content
                $newTargetContent[] = $line;
            } else {
                // Add lines outside placeholders as is
                $newTargetContent[] = $line;
            }
        }
    }

    // Write the updated content back to the target file
    file_put_contents($targetFilePath, implode(PHP_EOL, $newTargetContent) . PHP_EOL);
}


function processDirectory($sourceDir, $targetDir, $pasteFiles, $pasteFilesReplace, $mergeContents, $allowMergeDups) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $targetPath = str_replace($sourceDir, $targetDir, $item->getPathname());

        if ($item->isDir()) {
            if ($pasteFiles && !file_exists($targetPath)) {
                mkdir($targetPath, 0777, true);
            }
        } else {
            $filename = $item->getFilename();
            if (preg_match('/^\+\d+_(.+)$/', $filename, $matches)) {
                if ($mergeContents) {
                    $targetFileName = $matches[1];
                    $targetFilePath = $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPath() . DIRECTORY_SEPARATOR . $targetFileName;
                    mergeAddContent($item->getPathname(), $targetFilePath, $allowMergeDups);
                }
            } else {
                if ($pasteFiles) {
                    if (file_exists($targetPath) && !$pasteFilesReplace) {
                        continue;
                    }
                    copy($item->getPathname(), $targetPath);
                }
            }
        }
    }
}

$options = getopt("hv", ["help", "version", "paste-files::", "paste-files-replace::", "merge-contents::", "allow-merge-contents-dups::"]);

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

$pasteFiles = isset($options['paste-files']) ? filter_var($options['paste-files'], FILTER_VALIDATE_BOOLEAN) : true;
$pasteFilesReplace = isset($options['paste-files-replace']) ? filter_var($options['paste-files-replace'], FILTER_VALIDATE_BOOLEAN) : true;
$mergeContents = isset($options['merge-contents']) ? filter_var($options['merge-contents'], FILTER_VALIDATE_BOOLEAN) : true;
$allowMergeDups = isset($options['allow-merge-contents-dups']) ? filter_var($options['allow-merge-contents-dups'], FILTER_VALIDATE_BOOLEAN) : false;

try {
    processDirectory($sourceDir, $targetDir, $pasteFiles, $pasteFilesReplace, $mergeContents, $allowMergeDups);
    echo "Merge completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}