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
 * Class CurlVerboseStream.
 *
 * @author manuke
 */
class CurlVerboseStream
{
    /**
     * @var resource
     */
    public $tempStream;

    /**
     * CurlVerboseStream constructor.
     *
     * @param CurlFtpHandler $ch
     */
    public function __construct(CurlFtpHandler $ch, int $maxMemorySize)
    {
        $this->tempStream = fopen('php://temp/maxmemory:'.$maxMemorySize, 'r+');
        $ch->setOpt(CURLOPT_STDERR, $this->tempStream);
    }

    public function __destruct()
    {
        fclose($this->tempStream);
    }

    /**
     * @return bool|string
     */
    public function __toString()
    {
        rewind($this->tempStream);

        return stream_get_contents($this->tempStream);
    }
}
