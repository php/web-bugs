<?php declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\Utils\Uploader;

class UploaderTest extends TestCase
{
    /** @var string */
    private $fixturesDirectory = TEST_FIXTURES_DIRECTORY . '/files';

    /**
     * @dataProvider filesProvider
     */
    public function testUpload(string $validExtension, array $file): void
    {
        $_FILES = ['uploaded' => $file];

        /** @var Uploader|MockObject $uploader */
        $uploader = $this->getMockBuilder(Uploader::class)
            ->setMethods(['isUploadedFile', 'moveUploadedFile'])
            ->getMock();

        $uploader->expects($this->once())
                 ->method('isUploadedFile')
                 ->will($this->returnValue(true));

        $uploader->expects($this->once())
                 ->method('moveUploadedFile')
                 ->will($this->returnValue(true));

        $uploader->setMaxFileSize(100 * 1024);
        $uploader->setValidExtension($validExtension);
        $uploader->setDir(TEST_VAR_DIRECTORY . '/uploads');
        $tmpFile = $uploader->upload('uploaded');

        $this->assertNotNull($tmpFile);
    }

    public function filesProvider(): array
    {
        return [
            [
                'txt',
                [
                    'name'     => 'patch.txt',
                    'tmp_name' => $this->fixturesDirectory . '/patch.txt',
                    'size'     => filesize($this->fixturesDirectory . '/patch.txt'),
                    'error'    => UPLOAD_ERR_OK,
                ]
            ],
        ];
    }
}
