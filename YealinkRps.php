<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;

require 'vendor/autoload.php';
class YealinkRps
{
        
    function __construct(string $accessKey, string $secretKey): void
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }

    function getDeviceDetail($mac)
    {
        $url = self::BASE_URL . self::INFO_LOCATION;
        $timeStamp = substr(implode(explode(".", strval(microtime(true)))), 0, -1);
        $nonce = uniqid();

        $bodyArray = [
            "key" => $mac,
            "status" => "bound",
            "skip" => 0,
            "autoCount" => true,
            "limit" => 10
        ];
        $body = json_encode($bodyArray);
        $bodyStream = Psr7\Utils::streamFor($body);
        $contentMd5 = base64_encode(hash("md5", $bodyStream, true));
        $headerString = $this->createHeaderString($this->accessKey, $nonce, $timeStamp, $contentMd5);
        $urlForSign = substr(self::INFO_LOCATION, 1);
        $strToSign = $this->createStrToSign("POST", $headerString, $urlForSign);
        $signature = $this->createSignature($strToSign);
        $headers = [
            "Accept" => "application/json",
            "Content-type" => "application/json;charset=UTF-8",
            "X-Ca-Key" => $this->accessKey,
            "X-Ca-Timestamp" => $timeStamp,
            "X-Ca-Nonce" => $nonce,
            "Content-MD5" => $contentMd5,
            "X-Ca-Signature" => $signature
        ];
        $request = new Request("POST", $url, $headers, $bodyStream);
        $client = new Client();
        try {
            $response = $client->send($request);
            echo $response->getBody()->getContents();
        } catch (RuntimeException $e) {
            echo "Houston! We got the following error: " . "\n" . $e->getMessage();
        }
        //$response = $client->send($request, ['timeout' => 2]);
        //$request->getBody()->read(1);

    }

    function checkDeviceExists($mac)
    {
        $parameter = "?mac=" . $mac;
        $url = self::BASE_URL . self::EXISTS_LOCATION . $parameter;
        $timeStamp = substr(implode(explode(".", strval(microtime(true)))), 0, -1);
        $nonce = uniqid();
        $headerString = $this->createHeaderString($this->accessKey, $nonce, $timeStamp);
        $urlForSign = substr(self::EXISTS_LOCATION, 1);
        $paramsArray = array("mac" => $mac);
        $paramString = $this->createParameterString($paramsArray);
        $strToSign = $this->createStrToSign("GET", $headerString, $urlForSign, $paramString);
        $signature = $this->createSignature($strToSign);
        $client = new Client();
        try {
            $res = $client->request("GET", $url, [
                "headers" => [
                    "Accept" => "application/json",
                    "Content-type" => "application/json;charset=UTF-8",
                    "X-Ca-Key" => $this->accessKey,
                    "X-Ca-Timestamp" => $timeStamp,
                    "X-Ca-Nonce" => $nonce,
                    "X-Ca-Signature" => $signature
                ]
            ]);
            echo json_encode(json_decode($res->getBody()->getContents()), JSON_PRETTY_PRINT);
        } catch (RequestException $e) {
            echo $e->getResponse();
        }
    }

    function createSignature($strToSign)
    {
        return base64_encode(hash_hmac("sha256", $strToSign, $this->secretKey, TRUE));
    }

    function createStrToSign($httpMethod, $headers, $apiUri, $parameters = NULL)
    {
        $strToSign = "";
        if (is_null($parameters)) {
            $strToSign = $httpMethod . "\n" .
                $headers . "\n" . $apiUri;
        } else {
            $strToSign = $httpMethod . "\n" .
                $headers . "\n" . $apiUri . "\n" .
                $parameters;
        }

        return $strToSign;
    }

    function createHeaderString($accessKey, $nonce, $timeStamp, $contentMd5 = NULL)
    {
        $headers = "";
        if (is_null($contentMd5)) {
            $headers = "X-Ca-Key:" . $accessKey . "\n" .
                "X-Ca-Nonce:" . $nonce . "\n" .
                "X-Ca-Timestamp:" . $timeStamp;
        } else {
            $headers = "Content-MD5:" . $contentMd5 . "\n" .
                "X-Ca-Key:" . $accessKey . "\n" .
                "X-Ca-Nonce:" . $nonce . "\n" .
                "X-Ca-Timestamp:" . $timeStamp;
        }
        return $headers;
    }

    function createParameterString($parameters)
    {
        $parameterString = "";
        foreach ($parameters as $key => $value) {
            $parameterString .= $key . "=" . $value . "&";
        }
        return substr($parameterString, 0, -1);
    }

    // Instance variables
    private $accessKey;
    private $secretKey;

    //Constant
    const BASE_URL = "https://api-dm.yealink.com:8443";
    #const BASE_URL = "http://172.16.0.1";
    const EXISTS_LOCATION = "/api/open/v1/device/checkMac";
    const INFO_LOCATION = "/api/open/v1/device/list";
}

$rps = new YealinkRps(accessKey: "access", secretKey: "key");
#$rps->getDeviceDetail("805e0c75c111");
$rps->checkDeviceExists("805e0c75c111");
