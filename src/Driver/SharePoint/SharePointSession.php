<?php

namespace RemoteStorage\Driver\SharePoint;

use RemoteStorage\RemoteStorageException;
use RuntimeException;
use SimpleXMLElement;

class SharePointSession
{
    protected $url = 'https://login.microsoftonline.com/extSTS.srf';

    protected $site;

    protected $username;

    protected $password;

    protected $token;

    protected $cookie;

    protected $expire = 0;

    protected $digest;

    protected $ns = [
                        "wsse" => "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd",
                        "psf" => "http://schemas.microsoft.com/Passport/SoapServices/SOAPFault",
                        "d" => "http://schemas.microsoft.com/ado/2007/08/dataservices",
                        "S" => "http://www.w3.org/2003/05/soap-envelope"
                    ];
    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    public function __construct($site, $username, $password)
    {
        $this->site = $site;
        $this->username = $username;
        $this->password = $password;
    }

    public function login()
    {
        $xml = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'saml-template.xml');
        $xml = str_replace('{username}', $this->username, $xml);
        $xml = str_replace('{password}', $this->password, $xml);
        $xml = str_replace('{site}', $this->site, $xml);

        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $responseResult = curl_exec($curl);
        curl_close($curl);

        $simpleXMLElement = new SimpleXMLElement($responseResult);
        $data = $simpleXMLElement->xpath('//wsse:BinarySecurityToken');

        if(isset($data[0]))
        {
            $this->token = (string)$data[0];
            $this->initCookies();
        }else
        {
            throw new \RuntimeException(sprintf('Auth failed'));
        }
    }

    protected function initCookies()
    {
        $curl = curl_init('https://' . $this->site . '/_forms/default.aspx?wa=wsignin1.0');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->token);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Host: '. $this->site]);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);

        $this->cookie = implode('; ', ($matches[1]));

        $curl = curl_init('https://' . $this->site . '/_api/web');
        curl_setopt($curl, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_exec($curl);

        $info = curl_getinfo($curl);
        curl_close($curl);

        if($info['http_code'] != 200)
        {
            throw new RemoteStorageException('Auth failed');
        }
    }

    public function getDigest()
    {
        if($this->expire > time())
        {
            return $this->digest;
        }

        $curl = curl_init('https://' . $this->site . '/_api/contextinfo');
        curl_setopt($curl, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($curl, CURLOPT_POSTFIELDS, '');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $responseResult = curl_exec($curl);

        $simpleXMLElement = new SimpleXMLElement($responseResult);
        $simpleXMLElement->registerXPathNamespace('d', $this->ns['d']);
        $digest = (string)$simpleXMLElement->xpath('//d:FormDigestValue')[0];
        $ex = (string)$simpleXMLElement->xpath('//d:FormDigestTimeoutSeconds')[0];

        $this->expire = time()+$ex;

        return $digest;
    }

    public function get($url)
    {
        return $this->send($url);
    }

    public function post($url, $data, array $headers = [])
    {
        return $this->send($url, $data, 'POST', $headers);
    }

    public function send($url, $data = '', $method = 'GET', array $headers = [])
    {

        $headers = array_merge([
            'Authorization: Bearer ' . $this->getDigest(),
            'User-Agent: NONISV|DentsuAegisNetworkUkraine|FileServicesMigration/1.0',
            'accept: application/json;odata=verbose
            ',
        ], $headers);

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_COOKIE, $this->getCookie());

        if($method == 'POST')
        {
            curl_setopt($curl, CURLOPT_POST, true);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);

            if(is_string($data))
            {
                $headers[] =  'Content-length:' . strlen($data);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }elseif(isset($data['file']) && $data['file'] instanceof \CURLFile)
            {
                curl_setopt($curl, CURLOPT_INFILE, fopen($data['file']->getFilename(), 'rb'));
                curl_setopt($curl, CURLOPT_INFILESIZE, filesize($data['file']->getFilename()));
                curl_setopt($curl, CURLOPT_TIMEOUT,3600*12);
                $headers[] = 'Content-length: ' . filesize($data['file']->getFilename());
            }

        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);

        if($response === false)
        {
            throw new RemoteStorageException(curl_error($curl));
        }

        $response = json_decode($response);

        if(isset($response->error))
        {
            throw new RemoteStorageException($response->error->message->value);
        }

        return $response;
    }

    public function getToFile($url, $filePath)
    {
        $fp = fopen($filePath,"w");

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getDigest(),
            'User-Agent: NONISV|DentsuAegisNetworkUkraine|FileServicesMigration/1.0',
            'accept: application/json;odata=verbose',
            'content-type: charset=UTF-8'
        ]);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_COOKIE, $this->getCookie());
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_exec($curl);
        fclose($fp);
        curl_close($curl);
    }

}
