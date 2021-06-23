<?php


namespace RemoteStorage\Driver;


interface DriverInterface
{
    public function connect();

    public function login();

    public function quit();

    public function passive($parv):bool;

    public function chdir(string $dir);

    public function pwd():?string;

    public function is_exists($path);

    public function nlist($dir);

    public function place($remoteFile, $localFile, $mode);

    public function put($remoteFile, $localFile, $mode);

    public function get($remoteFile, $localFile, $mode);

    public function mkdir($dir);

    public function rmdir($dir);

    public function delete($remoteFile);

    public function rename($from, $to);
}
