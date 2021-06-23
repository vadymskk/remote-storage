<?php

use RemoteStorage\RemoteStorage;

include '../vendor/autoload.php';

$remoteStorage = new RemoteStorage(
    RemoteStorage::SHARE_POINT,
    'worksection@dentsuaegis.com.ua',
    '***********',
    'ks7977355.sharepoint.com',
    '80',
    ['siteName' => 'UA-DAN-WORKSECTION']
);
$remoteStorage->login();

include 'tests.php';

tests($remoteStorage, '/');
