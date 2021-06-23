<?php

namespace RemoteStorage;

use InvalidArgumentException;
use RemoteStorage\Driver\FTP\FtpDriver;
use RemoteStorage\Driver\DriverInterface;
use RemoteStorage\Driver\SFTP\SftpDriver;
use RemoteStorage\Driver\SharePoint\SharePointDriver;

class RemoteStorage
{
    protected $drivers = [];

    /**
     * @var DriverInterface
     */
    protected $driver;

    protected $user;

    protected $password;

    protected $host;

    protected $port;

    protected $driverName;

    protected $additionalOptions;

    public const FTP = 'ftp';
    public const FTPS = 'ftps';
    public const SFTP = 'sftp';
    public const SHARE_POINT = 'share_point';

    public function __construct(string $driverName, $user, $password, $host, $port, array $additionalOptions = [])
    {
        $this->driverName = $driverName;
        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->additionalOptions = $additionalOptions;
        $this->registerDriver(self::FTP, FtpDriver::class);
        $this->registerDriver(self::FTPS, FtpDriver::class);
        $this->registerDriver(self::SFTP, SftpDriver::class);
        $this->registerDriver(self::SHARE_POINT, SharePointDriver::class);
        $this->switchDriver($this->driverName);
    }

    public function switchDriver(string $name)
    {
        if (!isset($this->drivers[$name])) {
            throw new \RuntimeException('Driver not fount');
        }

        $this->driverName = $name;

        if ($this->driverName == self::SHARE_POINT)
        {
            if(!isset($this->additionalOptions['siteName']))
            {
                throw new InvalidArgumentException('Not additional options "siteName" ');
            }else
            {
                $this->driver = new $this->drivers[$name]($this->user, $this->password, $this->host, $this->additionalOptions['siteName']);
            }
        } else
        {

            $this->driver = new $this->drivers[$name]($this->user, $this->password, $this->host, $this->port);

            if ($this->driver instanceof FtpDriver && $this->driverName == self::FTPS)
            {
                $this->driver->setIsSFTP(true);
            }
        }


    }

    public function registerDriver(string$name, string $classPath)
    {
        $this->drivers[$name] = $classPath;
    }

    public function connect()
    {
        $this->driver->connect();
    }

    public function login()
    {
        $this->driver->login();
    }

    public function quit()
    {
        return $this->driver->quit();
    }

    public function passive($parv)
    {
        $this->driver->passive($parv);
    }

    public function chdir($dir)
    {
        $this->driver->chdir($dir);
    }

    public function pwd():string
    {
        return $this->driver->pwd();
    }

    public function is_exists($path)
    {
        return $this->driver->is_exists($path);
    }

    public function nlist($dir)
    {
        return $this->driver->nlist($dir);
    }

    public function place($remoteFile, $localFile, $mode = FTP_BINARY)
    {
        $this->driver->place($remoteFile, $localFile, $mode);
    }

    public function put($remoteFile, $localFile, $mode = FTP_BINARY)
    {
        try{
            $this->driver->put($remoteFile, $localFile, $mode);
        }catch (\Exception $exception)
        {
            print_r($exception->getMessage());
        }
    }

    public function get($remoteFile, $localFile, $mode = FTP_BINARY)
    {
        $this->driver->get($remoteFile, $localFile, $mode);
    }

    public function mkdir($dir)
    {
        $this->driver->mkdir($dir);
    }

    public function rmdir($dir)
    {
        $this->driver->rmdir($dir);
    }

    public function delete($remoteFile)
    {
        $this->driver->delete($remoteFile);
    }

    public function rename($from, $to)
    {
        return $this->driver->rename($from, $to);
    }
}
