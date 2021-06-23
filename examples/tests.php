<?php


use RemoteStorage\RemoteStorage;

function tests(RemoteStorage $remoteStorage, $rootDir = '/')
{
    echo 'pwd'.PHP_EOL;
    echo $remoteStorage->pwd().PHP_EOL;

    echo 'nlist'.PHP_EOL;
    print_r($remoteStorage->nlist($rootDir));

    $folder = 'test_'.date('Y-m-d_His');
    echo 'mkdir: '. $rootDir.$folder.PHP_EOL;
    $remoteStorage->mkdir($rootDir.$folder);

    echo 'nlist: '.$rootDir.PHP_EOL;
    print_r($remoteStorage->nlist($rootDir));


    echo 'nlist:' . $rootDir.$folder.PHP_EOL;
    $remoteStorage->chdir($rootDir.$folder);

    echo 'pwd'.PHP_EOL;
    echo $remoteStorage->pwd().PHP_EOL;

    echo 'put: '.$rootDir.$folder.'/file_example.txt'.PHP_EOL;
    $remoteStorage->put($rootDir.$folder.'/file_example.txt', 'file_example.txt');

    echo 'nlist:'.$rootDir.$folder.PHP_EOL;
    print_r($remoteStorage->nlist($rootDir.$folder));

    echo 'put:'.$rootDir.$folder.'/file_example_2.txt' .PHP_EOL;
    $remoteStorage->put($rootDir.$folder.'/file_example_2.txt', 'file_example.txt');

    echo 'nlist: '.$rootDir.$folder.PHP_EOL;
    print_r($remoteStorage->nlist($rootDir.$folder));

    echo 'delete:'.$rootDir.$folder.'/file_example_2.txt'.PHP_EOL;
    $remoteStorage->delete($rootDir.$folder.'/file_example_2.txt');

    echo 'nlist:'.$rootDir.$folder.PHP_EOL;
    print_r($remoteStorage->nlist($rootDir.$folder));


    $folder2 = 'test_'.date('Y-m-d_His');
    echo 'mkdir: '. $rootDir.$folder.'/'.$folder2 .PHP_EOL;
    $remoteStorage->mkdir($rootDir.$folder.'/'.$folder2);

    echo 'nlist:'.$rootDir.$folder.PHP_EOL;
    print_r($remoteStorage->nlist($rootDir.$folder));

    echo 'rmdir: '.$rootDir.$folder.PHP_EOL;
    $remoteStorage->rmdir($rootDir.$folder.'/'.$folder2);

    echo 'nlist:'.PHP_EOL;
    print_r($remoteStorage->nlist($rootDir.$folder));
}
