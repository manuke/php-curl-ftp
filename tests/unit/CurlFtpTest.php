<?php

namespace manuke\PhpCurlFtp\Tests;

use manuke\PhpCurlFtp\CurlFtp;
use manuke\PhpCurlFtp\Exception\CurlFtpException;

class CurlFtpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CurlFtp
     */
    private $ftp;
    private $filename = '日本語 #/日本語 #.txt';

    public function setUp()
    {
        $this->ftp = new CurlFtp('127.0.0.1', 'foo', 'bar');
    }

    public function tearDown()
    {
        $this->ftp = null;
    }

    public function testPortErrorNoVerbose()
    {
        $this->ftp->isPassiveMode = false;
        $this->expectException(CurlFtpException::class);
        $this->expectExceptionMessage('Failed to do PORT');
        $this->ftp->listDir('.');
    }

    public function testPortErrorVerbose()
    {
        $this->ftp->isVerbose = true;
        $this->ftp->isPassiveMode = false;
        $this->expectException(CurlFtpException::class);
        $this->expectExceptionMessage('500');
        $this->ftp->listDir('.');
    }

    public function testListUnknownDir()
    {
        $this->ftp->isVerbose = true;
        $this->expectException(CurlFtpException::class);
        $this->expectExceptionMessage('550');
        $this->ftp->listDir('unknown dir');
    }

    public function testPutFromString()
    {
        $result = $this->ftp->putFileString($this->filename, 'utf8 日本語');
        $this->assertTrue($result);
    }

    public function testListOk()
    {
        $dirname = dirname($this->filename);
        $dir = $this->ftp->listDir($dirname);
        $this->assertArrayHasKey("$dirname/日本語 #.txt", $dir['files']);
    }

    public function testGetAsString()
    {
        $result = $this->ftp->getFileString($this->filename);
        $this->assertIsString($result);
    }

    public function testPutFile()
    {
        $uploadFile = TEST_FILE_DIR.'upload.txt';
        file_put_contents($uploadFile, 'some text');
        $result = $this->ftp->putFile('./upload.txt', $uploadFile);
        if (file_exists($uploadFile)) {
            unlink($uploadFile);
        }
        $this->assertTrue($result);
    }

    public function testGettFile()
    {
        $downloadFile = TEST_FILE_DIR.'download.txt';
        if (file_exists($downloadFile)) {
            unlink($downloadFile);
        }
        $this->ftp->getFile('./upload.txt', $downloadFile);
        $this->assertFileExists($downloadFile);
        if (file_exists($downloadFile)) {
            unlink($downloadFile);
        }
    }
}
