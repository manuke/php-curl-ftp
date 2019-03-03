<?php

declare(strict_types=1);
/**
 * The software is provided under Mit License.
 * For the full copyright and license information, please view the LICENSE file.
 *
 * PHP version 7
 */

namespace manuke\PhpCurlFtp;

/**
 * Class CurlFtp.
 *
 * @author manuke
 */
class CurlFtpExec
{
    /**
     * @var int
     *          default 2MB
     */
    private $maxMemory = 2097152;

    /**
     * @var CurlFtpHandler
     */
    private $handler;

    /**
     * @var bool
     */
    private $directMemory;

    /**
     * CurlFtpExec constructor.
     *
     * @param CurlFtpHandler $handler
     */
    public function __construct(CurlFtpHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param array $remoteLocal
     *
     * @return array
     */
    public function getSingleSessionFtpString(array $remoteLocal) :array
    {
        $this->handler->setOpt(CURLOPT_UPLOAD, false);
        $this->directMemory = true;

        return $this->execSingleSessionFtp('get', $remoteLocal);
    }

    /**
     * @param array $remoteLocal
     *
     * @return array
     */
    public function putSingleSessionFtpString(array $remoteLocal) :array
    {
        $this->handler->setOpt(CURLOPT_UPLOAD, true);
        $this->directMemory = true;

        return $this->execSingleSessionFtp('put', $remoteLocal);
    }

    /**
     * @param array $remoteLocal
     *
     * @return array
     */
    public function getSingleSessionFtpFile(array $remoteLocal) :array
    {
        $this->handler->setOpt(CURLOPT_UPLOAD, false);
        $this->directMemory = false;

        return $this->execSingleSessionFtp('get', $remoteLocal);
    }

    /**
     * @param array $remoteLocal
     *
     * @return array
     */
    public function putSingleSessionFtpFile(array $remoteLocal) :array
    {
        $this->handler->setOpt(CURLOPT_UPLOAD, true);
        $this->directMemory = false;

        return $this->execSingleSessionFtp('put', $remoteLocal);
    }

    /**
     * @param string $filePathOrString
     */
    private function createPutFileStream(string $filePathOrString) :void
    {
        if ($this->directMemory) {
            $resource = fopen('php://temp/maxmemory:'.$this->maxMemory, 'r+');
            fwrite($resource, $filePathOrString);
            rewind($resource);
            $this->handler->setOpt(CURLOPT_INFILESIZE, mb_strlen($filePathOrString, '8bit'));
        } else {
            if (! file_exists($filePathOrString) || ! is_readable($filePathOrString)) {
                throw new \InvalidArgumentException("can't read a file:".$filePathOrString);
            } else {
                $resource = fopen($filePathOrString, 'r');
                $this->handler->setOpt(CURLOPT_INFILESIZE, filesize($filePathOrString));
            }
        }
        $this->handler->setOpt(CURLOPT_INFILE, $resource);
    }

    /**
     * @param string $filePath
     * @param null   $tempFileHandler
     */
    private function createGetFileStream(string $filePath, $tempFileHandler = null)
    {
        if ($this->directMemory) {
            //return transfer data
            $this->handler->setOpt(CURLOPT_RETURNTRANSFER, true);
        } else {
            //not return transfer data
            $this->handler->setOpt(CURLOPT_RETURNTRANSFER, false);
            if (is_writable($filePath)) {
                throw new \InvalidArgumentException("can't write a file:".$filePath);
            } else {
                $this->handler->setOpt(CURLOPT_FILE, $tempFileHandler);
            }
        }
    }

    /**
     * @param string $putOrGet    put|get
     * @param array  $remoteLocal
     *
     * @return array
     */
    private function execSingleSessionFtp(string $putOrGet, array $remoteLocal) :array
    {
        $result = [];
        $tempFileHandler = null;
        foreach ($remoteLocal as $to => $from) {
            $this->handler->setTargetPath($to);
            if ($putOrGet == 'put') {
                $this->createPutFileStream($from);
            } elseif ($putOrGet == 'get') {
                if ($this->directMemory) {
                    $this->createGetFileStream($from);
                } else {
                    //write tempfile and move when finish
                    $tempFileHandler = tmpfile();
                    $this->createGetFileStream($from, $tempFileHandler);
                }
            }
            $response = $this->handler->exec();
            if ($putOrGet == 'get' && ! $this->directMemory && $response) {
                rename(stream_get_meta_data($tempFileHandler)['uri'], $from);
            }
            $result[$to] = $response;
        }

        return $result;
    }

    /**
     * @param int $size
     */
    public function setMaxMemory(int $size)
    {
        $this->maxMemory = $size;
    }
}
