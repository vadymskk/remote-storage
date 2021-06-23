<?php

namespace RemoteStorage\Driver\SFTP;

use phpseclib\Net\SFTP;
use RemoteStorage\Driver\DriverInterface;

class SftpDriver implements DriverInterface
{
    protected $host = 'localhost';

    protected $port = 22;

    protected $user = 'Anonymous';

    protected $password;

    protected $timeout = 30;

    /**
     * @var SFTP
     */
    protected $resource;

    protected $isLogin;

    protected $dir;

    public function __construct($user = 'Anonymous', $password = '', $host = 'localhost', $port = 22)
    {
        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
    }

    public function connect()
    {
        if ($this->resource)
        {
            return $this->resource;
        }
        $this->resource = new SFTP($this->host, $this->port, $this->timeout);
        return $this->resource;
    }

    public function login()
    {
        if (!$this->isLogin)
        {
            $this->isLogin = $this->resource->login($this->user, $this->password);
        }
        return $this->isLogin;
    }

    public function quit()
    {
        $this->resource->disconnect();
    }

    public function passive($pasv):bool
    {
        return  $pasv;
    }

    public function chdir(string $dir)
    {
        $result = $this->resource->chdir($dir);

        if ($result)
        {
            $this->dir = $dir;
        }

        return $result;
    }

    public function pwd():string
    {
        $dir       =  $this->resource->pwd();
        $this->dir = $dir;
        return $dir;
    }

    public function is_exists($path)
    {
        return $this->resource->file_exists($path);
    }

    public function nlist($dir = '.')
    {
        $list =  $this->resource->nlist($dir);

        return array_filter($list, function ($item){
           return !in_array($item, ['.', '..']);
        });
    }

    public function place($remoteFile, $localFile, $mode = SFTP::SOURCE_LOCAL_FILE)
    {
        return $this->put($remoteFile, $localFile);
    }

    public function put($remoteFile, $localFile, $mode = SFTP::SOURCE_LOCAL_FILE)
    {
        return $this->resource->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE);
    }

    public function get($remoteFile, $localFile = false, $mode = null)
    {
        return $this->resource->get($remoteFile, $localFile);
    }

    public function mkdir($dir)
    {
        return $this->resource->mkdir($dir);
    }

    public function rmdir($dir)
    {
        return $this->resource->rmdir($dir);
    }

    public function delete($remoteFile)
    {
        return $this->resource->delete($remoteFile);
    }

    public function rename($from, $to)
    {
        return $this->resource->rename($from, $to);
    }

    public function __destruct()
    {
       $this->resource->disconnect();
    }
}
