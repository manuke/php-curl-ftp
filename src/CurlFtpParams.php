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
 * Class CurlFtpParams.
 *
 * @author manuke
 */
class CurlFtpParams
{
    /**
     * @var bool
     */
    public $isVerbose = false;

    /**
     * @var bool
     */
    public $isPassiveMode = true;

    /**
     * @var int
     */
    public $controlPort = 21;

    /**
     * @var bool
     */
    public $isAscii = false;

    /**
     * @var string
     */
    //'-' as system default ip(use only active mode)
    public $portCommandUsingAddress = '-';

    /**
     * @var bool
     */
    public $useEpsv = true;

    /**
     * @var bool
     */
    public $useErpt = false;

    /**
     * @var bool
     */
    public $appendFile = false;

    /**
     * @var int
     */
    public $dnsCacheTimeout = 120;

    /**
     * @var bool
     */
    public $forbidReuse = false;

    /**
     * @var bool
     */
    public $freshConnect = false;

    /**
     * @var int
     */
    //all operation timeout(include all multiple files transfer time)
    public $curlWholeProcessTimeout = 20;

    /**
     * @var int
     */
    public $curlConnectTimeout = 2;

    /**
     * @var int
     */
    //put only, if not exist contents dir create
    //libcurl 7.1.9(tells libcurl to retry the CWD command again if the subsequent MKD command fails)
    public $createMissingDirs = CURLFTP_CREATE_DIR_RETRY;

    /**
     * @var int
     */
    public $curlProtocol = CURLPROTO_FTP;

    /**
     * @var int
     */
    public $verboseLogTempMaxMemorySize = 2097152;

    /**
     * @var int
     */
    public $directMemoryPutTempMaxMemorySize = 2097152;

    /**
     * @var array
     */
    //any curl option value pairs
    public $extraOption = [];
}
