<?php

use PHPUnit\Framework\TestCase;

class MergeTemplatesTest extends TestCase
{
    protected $sourceDir;
    protected $targetDir;

    protected function setUp(): void
    {
        // Define paths for source and target directories
        $this->sourceDir = __DIR__ . '/test_source_dir';
        $this->targetDir = __DIR__ . '/test_target_dir';

        // Create directories for source and target
        mkdir($this->sourceDir, 0777, true);
        mkdir($this->targetDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Clean up by removing source and target directories
        $this->removeDirectory($this->sourceDir);
        $this->removeDirectory($this->targetDir);
    }

    protected function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($dir);
    }


    protected function runScript($options)
    {
        $command = "php " . __DIR__ . "/../merge_templates.php $options " . $this->sourceDir . " " . $this->targetDir;
        exec($command);
    }

    ### 2. Test for Basic File Copying

    public function testPasteFilesTrue(): void
    {
        // Prepare files in source directory
        file_put_contents($this->sourceDir . '/file1.txt', 'Content of file1');

        // Run the script with paste-files=true (default behavior)
        $this->runScript('--paste-files=true');

        // Check if the file was copied
        $this->assertFileExists($this->targetDir . '/file1.txt');
        $this->assertEquals('Content of file1', file_get_contents($this->targetDir . '/file1.txt'));
    }


    ### 3. Test for Skipping File Copying

    public function testPasteFilesFalse(): void
    {
        // Prepare files in source directory
        file_put_contents($this->sourceDir . '/file2.txt', 'Content of file2');

        // Run the script with paste-files=false
        $this->runScript('--paste-files=false');

        // Check that the file was not copied
        $this->assertFileDoesNotExist($this->targetDir . '/file2.txt');
    }


    ### 4. Test for Conditional File Overwriting


    public function testPasteFilesReplaceFalse(): void
    {
        // Prepare files in source and target directories
        file_put_contents($this->sourceDir . '/file3.txt', 'Content from source');
        file_put_contents($this->targetDir . '/file3.txt', 'Content from target');

        // Run the script with paste-files-replace=false
        $this->runScript('--paste-files-replace=false');

        // Check that the target file was not overwritten
        $this->assertEquals('Content from target', file_get_contents($this->targetDir . '/file3.txt'));
    }

    public function testPasteFilesReplaceTrue(): void
    {
        // Prepare files in source and target directories
        file_put_contents($this->sourceDir . '/file4.txt', 'Content from source');
        file_put_contents($this->targetDir . '/file4.txt', 'Content from target');

        // Run the script with paste-files-replace=true (default behavior)
        $this->runScript('--paste-files-replace=true');

        // Check that the target file was overwritten
        $this->assertEquals('Content from source', file_get_contents($this->targetDir . '/file4.txt'));
    }


    ### 5. Test for Merging Content


    public function testMergeContentsTrue(): void
    {
        // Prepare files in source and target directories
        file_put_contents($this->sourceDir . '/+1_file5.txt', "###placeholder_start\nContent to merge\n###placeholder_end");
        file_put_contents($this->targetDir . '/file5.txt', "###placeholder_start\nOriginal content\n###placeholder_end");

        // Run the script with merge-contents=true (default behavior)
        $this->runScript('--merge-contents=true');

        // Check that the content was merged correctly
        $expectedContent = "###placeholder_start\nContent to merge\nOriginal content\n###placeholder_end";
        $this->assertEquals($expectedContent, file_get_contents($this->targetDir . '/file5.txt'));
    }

    public function testMergeContentsFalse(): void
    {
        // Prepare files in source and target directories
        file_put_contents($this->sourceDir . '/+1_file6.txt', "###placeholder_start\nContent to merge\n###placeholder_end");
        file_put_contents($this->targetDir . '/file6.txt', "###placeholder_start\nOriginal content\n###placeholder_end");

        // Run the script with merge-contents=false
        $this->runScript('--merge-contents=false');

        // Check that the content was not merged
        $expectedContent = "###placeholder_start\nOriginal content\n###placeholder_end";
        $this->assertEquals($expectedContent, file_get_contents($this->targetDir . '/file6.txt'));
    }


    ### 6. Test for Duplicate Prevention in Merging


    public function testAllowMergeContentsDupsFalse(): void
    {
        // Prepare files in source and target directories
        file_put_contents($this->sourceDir . '/+1_file7.txt', "###placeholder_start\nDuplicate content\n###placeholder_end");
        file_put_contents($this->targetDir . '/file7.txt', "###placeholder_start\nDuplicate content\nOriginal content\n###placeholder_end");

        // Run the script with allow-merge-contents-dups=false
        $this->runScript('--allow-merge-contents-dups=false');

        // Check that the duplicate content was not inserted
        $expectedContent = "###placeholder_start\nDuplicate content\nOriginal content\n###placeholder_end";
        $this->assertEquals($expectedContent, file_get_contents($this->targetDir . '/file7.txt'));
    }

    public function testAllowMergeContentsDupsTrue(): void
    {
        // Prepare files in source and target directories
        file_put_contents($this->sourceDir . '/+1_file8.txt', "###placeholder_start\nDuplicate content\n###placeholder_end");
        file_put_contents($this->targetDir . '/file8.txt', "###placeholder_start\nOriginal content\n###placeholder_end");

        // Run the script with allow-merge-contents-dups=true
        $this->runScript('--allow-merge-contents-dups=true');

        // Check that the duplicate content was inserted
        $expectedContent = "###placeholder_start\nDuplicate content\nOriginal content\nDuplicate content\n###placeholder_end";
        $this->assertEquals($expectedContent, file_get_contents($this->targetDir . '/file8.txt'));
    }


    ### 7. Test for Handling Non-Existent Placeholders


    public function testMergeWithNonExistentPlaceholder(): void
    {
        // Prepare files in source and target directories
        file_put_contents($this->sourceDir . '/+1_file9.txt', "###placeholder_start\nContent to merge\n###placeholder_end");
        file_put_contents($this->targetDir . '/file9.txt', "No placeholders here");

        // Run the script with merge-contents=true (default behavior)
        $this->runScript('--merge-contents=true');

        // Check that the target file was not modified
        $this->assertEquals('No placeholders here', file_get_contents($this->targetDir . '/file9.txt'));
    }

}