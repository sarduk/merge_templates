#!/usr/bin/php
<?php

//echo __FILE__."\n";

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

    // Parse the target file into an array of blocks
    $targetContent = file($targetFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $parsedBlocks = parseTargetFileIntoBlocks($targetContent, $placeholderStart, $placeholderEnd);

    parsedBlocksMergeContent($parsedBlocks, $placeholderStart, $sourceContent, $allowDups);

    // Write the updated content back to the target file
    $newTargetContent = convertBlocksToString($parsedBlocks, $placeholderStart, $placeholderEnd);
    file_put_contents($targetFilePath, $newTargetContent);
}

function parsedBlocksMergeContent( & $parsedBlocks, $placeholderStart, $sourceContent, $allowDups)
{
    // Iterate through the parsed blocks and merge content
    foreach ($parsedBlocks as $blockIndex => $block) {
        // Check if the block is inside a placeholder
        foreach ($block as $placeholder => $content) {
            if ($placeholder === $placeholderStart) {
                $str_sourceContent = implode("\n", $sourceContent);
                if (!$allowDups) {
                    if (strpos($content, $str_sourceContent) !== false) {
                        continue;
                    }
                }
                // Accumulate the source content inside the placeholder block
                $parsedBlocks[$blockIndex][$placeholder] = $str_sourceContent . "\n" . $content;
            }
        }
    }
}

/**
 * Parses the target file into blocks and separates them by placeholders.
 *
 * @param array $targetContent The content of the target file.
 * @param string $placeholderStart The placeholder that marks the start of a placeholder block.
 * @param string $placeholderEnd The placeholder that marks the end of a placeholder block.
 * @return array Parsed blocks of content.
 */
function parseTargetFileIntoBlocks($targetContent, $placeholderStart, $placeholderEnd)
{
    $blocks = [];
    $currentBlock = '';
    $insidePlaceholder = false;
    $currentPlaceholder = '';

    foreach ($targetContent as $line) {
        $trimmedLine = trim($line);

        if ($trimmedLine === $placeholderStart) {
            // Save the current block if it's not inside a placeholder
            if ($currentBlock !== '') {
                $blocks[] = ['' => $currentBlock];
            }

            // Start a new block inside the placeholder
            $currentPlaceholder = $placeholderStart;
            $insidePlaceholder = true;
            $currentBlock = '';
        } elseif ($trimmedLine === $placeholderEnd && $insidePlaceholder) {
            // End the placeholder block and save it
            $blocks[] = [$currentPlaceholder => $currentBlock];
            $insidePlaceholder = false;
            $currentBlock = '';
        } else {
            // Add the line to the current block
            $currentBlock .= $line . "\n";
        }
    }

    // Add the last block if necessary
    if ($currentBlock !== '') {
        $blocks[] = ['' => $currentBlock];
    }

    return $blocks;
}

/**
 * Converts the array of blocks back into a string to be written to the target file.
 *
 * @param array $blocks The parsed blocks of content.
 * @return string The content to be written to the target file.
 */
function convertBlocksToString($blocks, $placeholderStart, $placeholderEnd)
{
    $output = '';

    foreach ($blocks as $block) {
        foreach ($block as $placeholder => $content) {
            if ($placeholder === $placeholderStart) {
                $output .= $placeholderStart . "\n";
            }
            $output .= $content;
            if ($placeholder === $placeholderStart) {
                $output .= $placeholderEnd ."\n";
            }
        }
    }

    return $output;
}

///////
///////
///////

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