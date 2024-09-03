<?php
use PHPUnit\Framework\TestCase;

class MergeTemplatesTest extends TestCase
{
    private $sourceDir;
    private $targetDir;

    protected function setUp(): void
    {
        // Creazione di directory temporanee per i test
        $this->sourceDir = __DIR__ . '/fixtures/source/';
        $this->targetDir = __DIR__ . '/fixtures/target/';
        
        // Ripulisce e ricrea le directory per ogni test
        $this->clearDirectory($this->targetDir);
        mkdir($this->targetDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Pulizia finale delle directory di test
        $this->clearDirectory($this->targetDir);
    }

    private function clearDirectory($dir)
    {
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
        }
    }

    public function testCopyFiles()
    {
        // Simula l'esecuzione dello script di merge
        processDirectory($this->sourceDir, $this->targetDir, false);

        // Verifica che i file siano stati copiati correttamente
        $this->assertFileExists($this->targetDir . 'somefile.txt');
    }

    public function testMergeContent()
    {
        // Crea file di test per il merge
        $sourceMergeFile = $this->sourceDir . '+1_targetfile.txt';
        file_put_contents($sourceMergeFile, 'PLACEHOLDER' . PHP_EOL . 'Merged Content');
        
        $targetFile = $this->targetDir . 'targetfile.txt';
        file_put_contents($targetFile, 'Start' . PHP_EOL . 'PLACEHOLDER' . PHP_EOL . 'End');

        // Esegui la funzione di merge
        processDirectory($this->sourceDir, $this->targetDir, false);

        // Verifica che il contenuto sia stato mergiato correttamente
        $expectedContent = 'Start' . PHP_EOL . 'PLACEHOLDER' . PHP_EOL . 'Merged Content' . PHP_EOL . 'End';
        $this->assertStringEqualsFile($targetFile, $expectedContent);
    }

    public function testFileOverwriteWithForce()
    {
        // Crea file di test
        $targetFile = $this->targetDir . 'somefile.txt';
        file_put_contents($targetFile, 'Original Content');

        $sourceFile = $this->sourceDir . 'somefile.txt';
        file_put_contents($sourceFile, 'New Content');

        // Simula l'esecuzione dello script di merge con opzione --force
        processDirectory($this->sourceDir, $this->targetDir, true);

        // Verifica che il file sia stato sovrascritto
        $this->assertStringEqualsFile($targetFile, 'New Content');
    }

    public function testExceptionOnFileExistsWithoutForce()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("File");

        // Crea file di test
        $targetFile = $this->targetDir . 'somefile.txt';
        file_put_contents($targetFile, 'Original Content');

        $sourceFile = $this->sourceDir . 'somefile.txt';

        file_put_contents($sourceFile, 'New Content');

        // Simula l'esecuzione dello script di merge senza opzione --force
        processDirectory($this->sourceDir, $this->targetDir, false);

        // Questo punto non dovrebbe mai essere raggiunto poiché si prevede un'eccezione
        $this->fail('Expected exception not thrown');
    }
}
