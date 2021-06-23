<?php

use RemoteStorage\RemoteStorage;

include '../vendor/autoload.php';

$remoteStorage = new RemoteStorage(RemoteStorage::FTPS, 'den', '1', 'localhost', 21);
$remoteStorage->login();

include 'tests.php';

tests($remoteStorage, '/home/den/ftp/');
