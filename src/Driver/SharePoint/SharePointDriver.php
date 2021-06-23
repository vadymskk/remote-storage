<?php


namespace RemoteStorage\Driver\SharePoint;


use RemoteStorage\Driver\DriverInterface;

class SharePointDriver implements DriverInterface
{
    /**
     * @var SharePointSession
     */
    protected $session;

    public $dir = 'Shared Documents';

    protected $currentDir;

    protected $siteName;

    protected $apiUrl;

    protected $host;

    public function __construct($user, $password, $host, $siteName)
    {
        $this->siteName = $siteName;
        $this->host = $host;
        $this->currentDir = '/';
        $this->session = new SharePointSession($host, $user, $password);
        $this->apiUrl = sprintf('https://%s/sites/%s/_api/web/', $host, $siteName);
    }

    public function connect()
    {
        // TODO: Implement connect() method.
    }

    public function login()
    {
        $this->session->login();
    }

    public function quit()
    {
        // TODO: Implement quit() method.
    }

    public function passive($parv): bool
    {
        // TODO: Implement passive() method.
    }

    public function chdir(string $dir)
    {
        $dir = ltrim($dir, '/');
        $folder = str_replace(' ', '%20', $this->dir.'/'.$dir);
        $response =  $this->session->get($this->apiUrl.'GetFolderByServerRelativeUrl(\''.$folder.'\')');
        if(isset($response->d->results))
        {
            return $this->currentDir .= rtrim($dir,'/').'/';
        }
    }

    public function pwd(): string
    {
        return  $this->currentDir;
    }

    public function is_exists($path)
    {
        $result = $this->getInfoFolderOrFile($path);
        return !empty($result);
    }

    public function nlist($dir)
    {
        $folder = str_replace(' ', '%20', $this->dir.'/'.ltrim($dir, '/'));

        $list = [];

        $response =  $this->session->get($this->apiUrl.'GetFolderByServerRelativeUrl(\''.$folder.'\')/Folders');

        if(isset($response->d->results))
        {
            foreach ($response->d->results as $item)
            {
                $list[] = $item->Name;
            }
        }

        $response =  $this->session->get($this->apiUrl.'GetFolderByServerRelativeUrl(\''.$folder.'\')/Files');

        if(isset($response->d->results))
        {
            foreach ($response->d->results as $item)
            {
                $list[] = $item->Name;
            }
        }

        return $list;
    }

    public function place($remoteFile, $localFile, $mode = null)
    {
        $this->put($remoteFile, $localFile, $mode);
    }

    public function put($remoteFile, $localFile, $mode = null)
    {
        $data = [
            'file' => $this->makeCurlFile(realpath($localFile))
        ];

        $folder =  rawurlencode($this->dir.'/'.ltrim($remoteFile, '/'));
        $filename = basename($folder);
        $folder = dirname($folder);

        $url = sprintf( 'GetFolderByServerRelativeUrl(\'%s\')/Files/add(url=\'%s\',overwrite=true)', $folder, $filename);

        $response = $this->session->post($this->apiUrl.$url, $data);

        return $response;
    }

    public function makeCurlFile($file):\CURLFile
    {
        $mime = mime_content_type($file);
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new \CURLFile($file, $mime, $name);
        return $output;
    }

    public function get($remoteFile, $localFile, $mode = null)
    {
        $folder = str_replace(' ', '%20', $this->dir.'/'.ltrim($localFile, '/'));

        $url = sprintf( 'GetFolderByServerRelativeUrl(\'%s\')/$value', $folder);

        $this->session->getToFile($this->apiUrl.$url, $localFile);
    }

    public function mkdir($dir)
    {
        $data = [
            '__metadata' => [
                'type' => 'SP.Folder',
            ],
            'ServerRelativeUrl' => '/sites/'.$this->siteName.'/'.$this->dir.'/'.ltrim($dir, '/'),
        ];

        $response = $this->session->post($this->apiUrl.'/folders', json_encode($data), ['content-type: application/json;odata=verbose']);

        return $response;
    }

    public function rmdir($dir)
    {
        $dir = str_replace(' ', '%20', $this->dir.'/'.ltrim($dir));

        $url = sprintf('%s/GetFolderByServerRelativeUrl(\'%s\')',$this->apiUrl,$dir);

        $response = $this->session->post($url, '', ['X-HTTP-Method: DELETE']);

        return $response;
    }

    public function delete($remoteFile)
    {
        $this->rmdir($remoteFile);
    }

    public function rename($from, $to)
    {
        $info = $this->getInfoFolderOrFile($from);

        if(!$info)
        {
            return false;
        }

        if($info->__metadata->type == 'SP.Folder')
        {
            return $this->renameDir($from, $to);
        }
    }

    public function renameDir($from, $to)
    {
        $dir = str_replace(' ', '%20', $this->dir.'/'.ltrim($from, '/'));

        $url = sprintf('%sGetFolderByServerRelativeUrl(\'%s\')/ListItemAllFields', $this->apiUrl, $dir);
        $param = [
            '__metadata' => ['type' => 'SP.Folder'],
            'Name' => basename($to),
        ];

        $response =  $this->session->post($url, json_encode($param), ['content-type: application/json;odata=verbose', 'X-HTTP-Method: MERGE', 'IF-MATCH: *']);

        var_dump($param);
        var_dump($url);
        var_dump($response);
        var_dump(json_decode($response));
    }

    public function getInfoFolderOrFile($path)
    {
        $filename = basename($path);
        $folder = dirname($path);

        $dir = str_replace(' ', '%20', $this->dir.'/'.ltrim($folder, '/'));

        $url = sprintf('%sGetFolderByServerRelativeUrl(\'%s\')/Files(\'%s\')', $this->apiUrl, $dir, $filename);

        $response = $this->session->get($url);
        $response =  json_decode($response);

        if(isset($response->d))
        {
            return $response->d;
        }

        $url = sprintf('%sGetFolderByServerRelativeUrl(\'%s\')', $this->apiUrl, $dir.'/'.$filename);

        $response = $this->session->get($url);

        if(isset($response->d))
        {
            return $response->d;
        }
    }
}
