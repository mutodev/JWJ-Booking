<?php

namespace Tests\Unit;

use App\Services\SecureImageUploadService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

class SecureImageUploadServiceTest extends CIUnitTestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    public function testUsesServerGeneratedNameAndTrustedExtension(): void
    {
        $temporaryImage = $this->pngFixture();
        $upload = $this->createMock(UploadedFile::class);
        $upload->method('isValid')->willReturn(true);
        $upload->method('hasMoved')->willReturn(false);
        $upload->method('getSize')->willReturn(filesize($temporaryImage));
        $upload->method('getMimeType')->willReturn('image/png');
        $upload->method('getTempName')->willReturn($temporaryImage);
        $upload->expects($this->once())->method('move')->with(
            FCPATH . 'img',
            $this->matchesRegularExpression('/^[a-f0-9]{32}\.png$/')
        )->willReturn(true);

        $result = (new SecureImageUploadService())->upload($upload);

        $this->assertMatchesRegularExpression('#^/img/[a-f0-9]{32}\.png$#', $result);
    }

    public function testRejectsNonImageContentEvenWithImageMime(): void
    {
        $temporaryFile = $this->temporaryFile('not an image');
        $upload = $this->createMock(UploadedFile::class);
        $upload->method('isValid')->willReturn(true);
        $upload->method('hasMoved')->willReturn(false);
        $upload->method('getSize')->willReturn(filesize($temporaryFile));
        $upload->method('getMimeType')->willReturn('image/png');
        $upload->method('getTempName')->willReturn($temporaryFile);

        $this->expectException(HTTPException::class);
        (new SecureImageUploadService())->upload($upload);
    }

    public function testDeleteCannotEscapePublicImageDirectory(): void
    {
        $outsideImageDirectory = $this->temporaryFile('keep me');

        (new SecureImageUploadService())->delete('/../writable/' . basename($outsideImageDirectory));

        $this->assertFileExists($outsideImageDirectory);
    }

    private function pngFixture(): string
    {
        return $this->temporaryFile(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));
    }

    private function temporaryFile(string $contents): string
    {
        $file = tempnam(WRITEPATH, 'security-test-');
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;
        return $file;
    }
}
