<?php

declare(strict_types=1);
/**
 * The software is provided under Mit License.
 * For the full copyright and license information, please view the LICENSE file.
 *
 * PHP version 7
 */

namespace manuke\PhpCurlFtp;

use manuke\PhpCurlFtp\Exception\CurlFtpException;

/**
 * Class CurlFtp.
 *
 * @author manuke
 */
class CurlFtp extends CurlFtpParams
{
    /**
     * curl exec result information.
     *
     * @var array
     */
    private $curlInfo = [];

    /**
     * @var CurlFtpHandler
     */
    private $ch = null;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var CurlVerboseStream
     */
    private $verboseStream = null;

    /**
     * CurlFtp constructor.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     */
    public function __construct(string $host, string $user, string $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->ch = new CurlFtpHandler();
        $this->ch->setConnectInfo($this->host, $this->user, $this->password);
    }

    /**
     * Attempt to close all curl handle.
     */
    public function __destruct()
    {
        if ($this->ch !== null) {
            $this->ch->closeConnection();
        }
    }

    /**
     * put file.
     *
     * @param string $targetFilePath
     * @param string $localFilePath
     *
     * @throws CurlFtpException
     *
     * @return bool
     */
    public function putFile(string $targetFilePath, string $localFilePath) :bool
    {
        $remoteKeyLocalValue = [$targetFilePath => $localFilePath];
        $returned = $this->execFtp('put', $remoteKeyLocalValue, false)[$targetFilePath];
        if ($returned === false) {
            $this->handleCurlException();
        }

        return $returned;
    }

    /**
     * put file from string.
     *
     * @param string $targetFilePath
     * @param string $contents
     *
     * @throws CurlFtpException
     *
     * @return bool
     */
    public function putFileString(string $targetFilePath, string $contents) :bool
    {
        $remoteKeyLocalValue = [$targetFilePath => $contents];
        $returned = $this->execFtp('put', $remoteKeyLocalValue, true)[$targetFilePath];
        if ($returned === false) {
            $this->handleCurlException();
        }

        return $returned;
    }

    /**
     * get file.
     *
     * @param string $targetFilePath
     * @param string $downloadFilePath
     *
     * @throws CurlFtpException
     *
     * @return bool
     */
    public function getFile(string $targetFilePath, string $downloadFilePath) :bool
    {
        $remoteKeyLocalValue = [$targetFilePath => $downloadFilePath];
        $returned = $this->execFtp('get', $remoteKeyLocalValue, false)[$targetFilePath];
        if ($returned === false) {
            $this->handleCurlException();
        }

        return $returned;
    }

    /**
     * get file string.
     *
     * @param string $targetFilePath
     *
     * @throws CurlFtpException
     *
     * @return string
     */
    public function getFileString(string $targetFilePath) :string
    {
        $remoteKeyLocalValue = [$targetFilePath => ''];
        $returned = $this->execFtp('get', $remoteKeyLocalValue, true)[$targetFilePath];
        if ($returned === false) {
            $this->handleCurlException();
        }

        return $returned;
    }

    /**
     * ls directory.
     *
     * @param string $targetDirPath
     *
     * @throws CurlFtpException
     *
     * @return array
     */
    public function listDir(string $targetDirPath) :array
    {
        $exec = function (bool $isListOnly) :array {
            $this->ch->setOpt(CURLOPT_FTPLISTONLY, $isListOnly);
            $list = $this->execFtp('list');
            if ($list === false) {
                $this->handleCurlException();
            } elseif (! $list) {
                $list = [];
            } else {
                $list = explode("\n", trim($list));
            }

            return $list;
        };
        //[/] must appear on end of path
        if (! preg_match('@/$@', $targetDirPath)) {
            $targetDirPath = $targetDirPath.'/';
        }
        $this->ch->setTargetPath($targetDirPath);

        //dir/file name & metadata
        $remoteList = $exec(false);

        //dir/file name only
        $nameList = $exec(true);
        if (count($nameList) != count($remoteList)) {
            throw new CurlFtpException('not match metadata and filename only counts', 0);
        }
        $fileOnlyList = [];
        $dirOnlyList = [];
        $i = 0;
        foreach ($remoteList as $value) {
            //check filename & file metadata
            if (preg_match("/.* $nameList[$i]$/", $value)) {
                //file
                //windows not support space file name?
                if (preg_match('/^d/', $value) || preg_match('/ <DIR> /', $value)) {
                    $dirOnlyList[$targetDirPath.$nameList[$i].'/'] = $value;
                } else {
                    $fileOnlyList[$targetDirPath.$nameList[$i]] = $value;
                }
                $i++;
            } else {
                throw new CurlFtpException('not match metadata and filename names:'.$value, 0);
            }
        }

        return ['dirs' => $dirOnlyList, 'files' => $fileOnlyList];
    }

    /**
     * @return array
     */
    public function getCurlInfo() :array
    {
        return $this->curlInfo;
    }

    private function handleCurlException() :void
    {
        $this->generateCurlInfo($this->verboseStream);
        if ($this->verboseStream !== null) {
            throw new CurlFtpException((string) $this->verboseStream, $this->ch->intError());
        } else {
            throw new CurlFtpException($this->ch->strError(), $this->ch->intError());
        }
    }

    /**
     * @param CurlVerboseStream|null $verboseStream
     */
    private function generateCurlInfo(CurlVerboseStream $verboseStream = null) :void
    {
        $this->curlInfo = $this->ch->info();
        if ($verboseStream) {
            $this->curlInfo['verbose'] = (string) $verboseStream;
        } else {
            $this->curlInfo['verbose'] = null;
        }
    }

    /**
     * @param string $method              get/put/list
     * @param array  $remoteKeyLocalValue
     * @param bool   $directMemory
     *
     * @return array|bool|string
     */
    private function execFtp(string $method, array $remoteKeyLocalValue = [], bool $directMemory = false)
    {
        //create verbose stream
        $this->ch->setOpt(CURLOPT_VERBOSE, $this->isVerbose);
        if ($this->isVerbose) {
            $this->verboseStream = new CurlVerboseStream($this->ch, $this->verboseLogTempMaxMemorySize);
        } else {
            $verboseStream = null;
        }
        $this->ch->setOpt(CURLOPT_PROTOCOLS, $this->curlProtocol);
        $this->ch->setOpt(CURLOPT_DNS_CACHE_TIMEOUT, $this->dnsCacheTimeout);
        $this->ch->setOpt(CURLOPT_FORBID_REUSE, $this->forbidReuse);
        $this->ch->setOpt(CURLOPT_FRESH_CONNECT, $this->freshConnect);
        $this->ch->setOpt(CURLOPT_FTP_USE_EPSV, $this->useEpsv);
        $this->ch->setOpt(CURLOPT_FTP_USE_EPRT, $this->useErpt);
        $this->ch->setOpt(CURLOPT_TIMEOUT, $this->curlWholeProcessTimeout);
        $this->ch->setOpt(CURLOPT_CONNECTTIMEOUT, $this->curlConnectTimeout);
        $this->ch->setOpt(CURLOPT_FTPAPPEND, $this->appendFile);
        $this->ch->setOpt(CURLOPT_PORT, $this->controlPort);
        $this->ch->setOpt(CURLOPT_TRANSFERTEXT, $this->isAscii);
        foreach ($this->extraOption as $param => $value) {
            $this->ch->setOpt($param, $value);
        }
        if (! $this->isPassiveMode) {
            //if this parameter is set, lib use active mode
            $this->ch->setOpt(CURLOPT_FTPPORT, $this->portCommandUsingAddress);
        }
        $exec = new CurlFtpExec($this->ch);
        $exec->setMaxMemory($this->directMemoryPutTempMaxMemorySize);
        $returned = false;
        if ($method === 'get') {
            $this->ch->setOpt(CURLOPT_FTP_CREATE_MISSING_DIRS, CURLFTP_CREATE_DIR_NONE);
            if ($directMemory) {
                $returned = $exec->getSingleSessionFtpString($remoteKeyLocalValue);
            } else {
                $returned = $exec->getSingleSessionFtpFile($remoteKeyLocalValue);
            }
        } elseif ($method === 'put') {
            $this->ch->setOpt(CURLOPT_FTP_CREATE_MISSING_DIRS, $this->createMissingDirs);
            if ($directMemory) {
                $returned = $exec->putSingleSessionFtpString($remoteKeyLocalValue);
            } else {
                $returned = $exec->putSingleSessionFtpFile($remoteKeyLocalValue);
            }
        } elseif ($method === 'list') {
            $this->ch->setOpt(CURLOPT_FTP_CREATE_MISSING_DIRS, CURLFTP_CREATE_DIR_NONE);
            $this->ch->setOpt(CURLOPT_RETURNTRANSFER, true);
            $returned = $this->ch->exec();
        }
        $this->generateCurlInfo($this->verboseStream);

        return $returned;
    }
}
