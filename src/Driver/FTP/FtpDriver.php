<?php

namespace RemoteStorage\Driver\FTP;

use RemoteStorage\Driver\DriverInterface;
use RemoteStorage\RemoteStorageException;

class FtpDriver implements DriverInterface
{

    protected $host = 'localhost';

    protected $port = 21;

    protected $user = 'Anonymous';

    protected $password;

    protected $timeout = 30;

    protected $resource;

    protected $isLogin = false;

    protected $dir;

    protected $isSFTP = false;

    /**
     * @return bool
     */
    public function isSFTP(): bool
    {
        return $this->isSFTP;
    }

    /**
     * @param bool $isSFTP
     */
    public function setIsSFTP(bool $isSFTP): void
    {
        $this->isSFTP = $isSFTP;
    }

    public function __construct($user = 'Anonymous', $password = 'Email', $host = 'localhost', $port = 21)
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

        if($this->isSFTP)
        {
            $this->resource = ftp_ssl_connect($this->host, $this->port, $this->timeout);
        }else
        {
            $this->resource = ftp_connect($this->host, $this->port, $this->timeout);
        }

        return $this->resource;
    }

    public function login()
    {
        if(!$this->resource)
        {
            $this->connect();
        }

        if (!$this->isLogin)
        {
            $this->isLogin = ftp_login($this->resource, $this->user, $this->password);
            if(!$this->isLogin)
            {
                throw new RemoteStorageException('Login failed');
            }
        }
        return $this->isLogin;
    }

    public function quit()
    {
        if ($this->resource)
        {
            ftp_close($this->resource);
        }
        $this->resource = null;
    }

    public function passive($pasv):bool
    {
        return ftp_pasv($this->resource, $pasv);
    }

    public function chdir(string $dir)
    {
        $result = ftp_chdir($this->resource, $dir);

        if ($result)
        {
            $this->dir = $dir;
        }

        return $result;
    }

    public function pwd():?string
    {
        $dir = ftp_pwd($this->resource);
        $this->dir = $dir;
        return $dir;
    }

    public function is_exists($path)
    {
        $pathname = basename($path);
        $dir = dirname($path);

        if (!$dir)
        {
            $dir = '.';
        }

        $arrDir = ftp_nlist($this->resource, $dir);
        if (!$arrDir)
        {
            return false;
        }

        foreach ($arrDir as $key => $val)
        {
            $val = basename(str_replace('\\', '/', $val));
            if ($val == '.' || $val == '..')
            {
                continue;
            }
            $arr_dir[$key] = $val;
        }
        return in_array(basename($pathname), $arrDir);
    }

    public function nlist($dir = '.')
    {
        return  @ftp_nlist($this->resource, $dir);
    }

    public function place($remoteFile, $localFile, $mode = FTP_BINARY)
    {
        return ftp_put($this->resource, $remoteFile, $localFile, $mode);
    }

    public function put($remoteFile, $localFile, $mode = FTP_BINARY)
    {
        return ftp_put($this->resource, $remoteFile, $localFile, $mode);
    }

    public function get($remoteFile, $localFile, $mode = FTP_BINARY)
    {
        return ftp_get($this->resource, $localFile, $remoteFile, $mode);
    }

    public function mkdir($dir)
    {
        return ftp_mkdir($this->resource, $dir) ? true : false;
    }

    public function rmdir($dir)
    {
        return ftp_rmdir($this->resource, $dir);
    }

    public function delete($remoteFile)
    {
        return ftp_delete($this->resource, $remoteFile);
    }

    public function rename($from, $to)
    {
        return ftp_rename($this->resource, $from, $to);
    }

    public function __destruct()
    {
       $this->quit();
    }
}
