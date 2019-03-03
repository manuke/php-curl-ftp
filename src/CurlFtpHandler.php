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
class CurlFtpHandler
{
    /**
     * @var false|resource
     */
    public $curlHandler;

    /**
     * @var string
     */
    private $host;

    /**
     * CurlFtpHandler constructor.
     */
    public function __construct()
    {
        $this->curlHandler = curl_init();
    }

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     */
    public function setConnectInfo(string $host, string $user, string $password) :void
    {
        $this->host = $host;
        curl_setopt($this->curlHandler, CURLOPT_USERPWD, $user.':'.$password);
    }

    /**
     * @param string $filePath
     */
    public function setTargetPath(string $filePath) :void
    {
        //replace directory delimiter to [/]
        $url = 'ftp://'.$this->host.'/'.str_replace('%2F', '/', curl_escape($this->curlHandler, $filePath));
        curl_setopt($this->curlHandler, CURLOPT_URL, $url);
        //when multihandle each transffer logs
        //cURL 7.10.3
        curl_setopt($this->curlHandler, CURLOPT_PRIVATE, $url);
    }

    /**
     * @param int   $param
     * @param mixed $value
     */
    public function setOpt(int $param, $value)
    {
        curl_setopt($this->curlHandler, $param, $value);
    }

    /**
     * @return bool|string
     */
    public function exec()
    {
        $result = curl_exec($this->curlHandler);

        return $result;
    }

    /**
     * @return array
     */
    public function info() :array
    {
        return curl_getinfo($this->curlHandler);
    }

    /**
     * @return string
     */
    public function strError() :string
    {
        return curl_error($this->curlHandler);
    }

    /**
     * @return int
     */
    public function intError() :int
    {
        return curl_errno($this->curlHandler);
    }

    public function closeConnection()
    {
        curl_close($this->curlHandler);
    }
}
