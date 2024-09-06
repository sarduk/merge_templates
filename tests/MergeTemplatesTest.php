<?php

use PHPUnit\Framework\TestCase;

class MergeTemplatesTest extends TestCase
{
    protected $sourceDir;
    protected $targetDir;

    public function printmsg($msg){
        //echo $msg . "\n";
        //fwrite(STDERR, $msg . "\n");
        echo  json_encode($msg) . "\n";
        
    }

    protected function setUp(): void
    {
        //$this->printmsg('function ' . __FUNCTION__);
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
        //$this->printmsg('function ' . __FUNCTION__);

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


    protected function runScript($options='')
    {
        //$this->printmsg('function ' . __FUNCTION__);
        $command = "php " . __DIR__ . "/../src/merge_templates.php $options " . $this->sourceDir . " " . $this->targetDir;
        //$this->printmsg('command = '.$command);
        exec($command);
    }

    ### 2. Test for Basic File Copying

    public function testPasteFilesTrue(): void
    {

        //$this->printmsg('function ' . __FUNCTION__);
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
        $filename = 'file3.txt';
        $filesource_original_content = 'Content from source';
        $filetarget_original_content = 'Content from target';
        file_put_contents($this->sourceDir . '/'.$filename, $filesource_original_content);
        file_put_contents($this->targetDir . '/'.$filename, $filetarget_original_content);

        // Run the script with paste-files-replace=false
        $this->runScript('--paste-files-replace=false');

        $expectedContent = $filetarget_original_content;
        $actualContent = file_get_contents($this->targetDir . '/'.$filename);
        $actualContent = trim($actualContent);

        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testPasteFilesReplaceTrue(): void
    {
        // Prepare files in source and target directories
        $filename = 'file4.txt';
        $filesource_original_content = 'Content from source';
        file_put_contents($this->sourceDir . '/'.$filename, $filesource_original_content);
        file_put_contents($this->targetDir . '/'.$filename, 'Content from target');

        // Run the script with paste-files-replace=true (default behavior)
        $this->runScript('--paste-files-replace=true');

        $expectedContent = $filesource_original_content;
        $actualContent = file_get_contents($this->targetDir . '/'.$filename);
        $actualContent = trim($actualContent);

        $this->assertEquals($expectedContent, $actualContent);
    }


    // ### 5. Test for Merging Content


    public function testMergeContentsTrue(): void
    {
        // Prepare files in source and target directories
        $filename = 'file5.txt';
        file_put_contents($this->sourceDir . '/+1_'.$filename, "###placeholder_start\nContent to merge\n###placeholder_end");
        file_put_contents($this->targetDir . '/'.$filename, "###placeholder_start\nOriginal content\n###placeholder_end");

        // Run the script with merge-contents=true (default behavior)
        $this->runScript('--merge-contents=true');

        // Check that the content was merged correctly
        $expectedContent = "###placeholder_start\nContent to merge\nOriginal content\n###placeholder_end";
        $actualContent = file_get_contents($this->targetDir . '/'.$filename);
        $actualContent = trim($actualContent);

        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testMergeContentsFalse(): void
    {
        // Prepare files in source and target directories
        $filename = 'file7.txt';
        file_put_contents($this->sourceDir . '/+1_'.$filename, "###placeholder_start\nContent to merge\n###placeholder_end");
        $filetarget_original_content =  "###placeholder_start\nOriginal content\n###placeholder_end";
        file_put_contents($this->targetDir . '/'.$filename, $filetarget_original_content);

        // Run the script with merge-contents=false
        $this->runScript('--merge-contents=false');

        // Check that the content was not merged
        $expectedContent = $filetarget_original_content;
        $actualContent = file_get_contents($this->targetDir . '/'.$filename);
        $actualContent = trim($actualContent);

        $this->assertEquals($expectedContent, $actualContent);
    }


    ### 6. Test for Duplicate Prevention in Merging


    public function testAllowMergeContentsDupsFalse(): void
    {
        // Prepare files in source and target directories
        $filename = 'file7.txt';
        file_put_contents($this->sourceDir . '/+1_'.$filename, "###placeholder_start\nDuplicate content\n###placeholder_end");
        $filetarget_original_content =  "###placeholder_start\nDuplicate content\nOriginal content\n###placeholder_end";
        file_put_contents($this->targetDir . '/'.$filename, $filetarget_original_content);

        // Run the script with allow-merge-contents-dups=false
        $this->runScript('--allow-merge-contents-dups=false');

        // Check that the duplicate content was not inserted
        $expectedContent = $filetarget_original_content;
        $actualContent = file_get_contents($this->targetDir . '/'.$filename);
        $actualContent = trim($actualContent);
        //$this->printmsg("function_".__FUNCTION__." expectedContent : '$expectedContent'");
        //$this->printmsg("function_".__FUNCTION__." actualContent : '$actualContent'");

        $this->assertEquals($expectedContent, $actualContent);
    }

    public function testAllowMergeContentsDupsTrue(): void
    {
        // Prepare files in source and target directories
        $filename = 'file8.txt';
        file_put_contents($this->sourceDir . '/+1_'.$filename, "###placeholder_start\nDuplicate content\n###placeholder_end");
        $filetarget_original_content =  "###placeholder_start\nOriginal content\nDuplicate content\n###placeholder_end";
        file_put_contents($this->targetDir . '/'.$filename, $filetarget_original_content);

        // Run the script with allow-merge-contents-dups=true
        $this->runScript('--allow-merge-contents-dups=true');

        // Check that the duplicate content was inserted
        $expectedContent = "###placeholder_start\nDuplicate content\nOriginal content\nDuplicate content\n###placeholder_end";
        $actualContent = file_get_contents($this->targetDir . '/'.$filename);
        $actualContent = trim($actualContent);
        //$this->printmsg("function_".__FUNCTION__." expectedContent : '$expectedContent'");
        //$this->printmsg("function_".__FUNCTION__." actualContent : '$actualContent'");

        $this->assertEquals($expectedContent, $actualContent);
    }


    ### 7. Test for Handling Non-Existent Placeholders

    public function testMergeWithNonExistentPlaceholder(): void
    {
        // Prepare files in source and target directories
        $filename = 'file9.txt';
        file_put_contents($this->sourceDir . '/+1_'.$filename, "###placeholder_start\nContent to merge\n###placeholder_end");
        $filetarget_original_content =  "No placeholders here";
        file_put_contents($this->targetDir . '/'.$filename, $filetarget_original_content);
        //$this->printmsg('function_testMergeWithNonExistentPlaceholder() file9.txt : '.file_get_contents($this->targetDir . '/file9.txt'));

        // Run the script with merge-contents=true (default behavior)
        $this->runScript('--merge-contents=true');

        $expectedContent = $filetarget_original_content;
        $actualContent = file_get_contents($this->targetDir . '/'.$filename);
        $actualContent = trim($actualContent);
        //$this->printmsg("function_testMergeWithNonExistentPlaceholder() expectedContent : '$expectedContent'");
        //$this->printmsg("function_testMergeWithNonExistentPlaceholder() actualContent : '$actualContent'");

        // Check that the target file has same content
        //assertStringEqualsStringIgnoringLineEndings doesn't work as expected
        //$this->assertStringEqualsStringIgnoringLineEndings($expectedContent, $actualContent);
        $this->assertEquals($expectedContent, $actualContent);
    }

}