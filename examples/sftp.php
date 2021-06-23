<?php

use RemoteStorage\RemoteStorage;

include '../vendor/autoload.php';

$remoteStorage = new RemoteStorage(RemoteStorage::SFTP, 'den', '1', 'localhost', 22);
$remoteStorage->connect();
$remoteStorage->login();

include 'tests.php';

tests($remoteStorage, '/home/den/ftp/');
