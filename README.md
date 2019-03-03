## manuke/php-curl-ftp
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://travis-ci.org/manuke/php-curl-ftp.svg?branch=master)](https://travis-ci.org/manuke/php-curl-ftp)


A PHP FTP wrapper using libcurl.

## What this wrapper can do
- list remote directory(can return raw output from any ftp server)
- get remote file to local
- put local file to remote

## Todo
- rename remote file
- delete remote file
- recursive operation
- curl multi
- proxy support

## Install

```
composer require manuke/php-curl-ftp
```

## Basic Usage

```
<?php
use manuke\PhpCurlFtp\CurlFtp;
use manuke\PhpCurlFtp\Exception\CurlFtpException;

$remotehost = 'ftp.example.com';
$user = 'foo';
$password = 'bar';
$remoteDirPath = '/';
$remoteFilePath = '/test/remote.txt';
$localFilePath = '/tmp/some.txt';

//create instance
$phpcurlftp = new CurlFtp($remotehost, $user, $password);

//change params
$phpcurlftp->isVerbose = true;

//if any error throw exception
try { 
    //put a file to remotehost
    $result = $phpcurlftp->putFile($remoteFilePath, $localFilePath);
    
    //list remote directory
    $list = $phpcurlftp->listDir($remoteDirPath);
    
    //get a file from remotehost
    $result = $phpcurlftp->getFile($remoteFilePath, $localFilePath);
    
    //put string variable as a remote host file
    $result = $phpcurlftp->getFileToString($remoteFilePath, $localFilePath);
    
    //get a remote file as local string 
    $contents = $phpcurlftp->putStringToFile($remoteFilePath, $localFilePath);
    
    //curl exec detail information
    var_dump($phpcurlftp->getCurlInfo());
} catch (CurlFtpException $e) {
    $e->getMessage();
}

```

