<?php
namespace Models;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;


class YealinkRps
{
     /**
     * Constructor of the calls
     *
     * @param string $accessKey API access key
     * @param string $secretKey API secret key
     * @return void
     */         
    function __construct(string $accessKey, string $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    /**
     * Send and HTTP POST request to retrieve the details from a device based on the mac
     * It will return the device ID on the Yealink system so that you can perform actions
     * @param string $mac Phone MAC address
     * @return void
     */
    function getDeviceDetail(string $mac): void
    {
        $url = self::BASE_URL . self::INFO_LOCATION;
        //Get the timestamp in unix time with milliseconds. You need to drop the last digit
        $timeStamp = substr(implode(explode(".", strval(microtime(true)))), 0, -1);
        $nonce = uniqid();
        //You need to get the MD5 of the HTTP body bites as par of the signature before sending the request
        //Consequently, you need to create a Guzzle Request and then send it
        $bodyArray = [
            "key" => $mac,
            "status" => "bound",
            "skip" => 0,
            "autoCount" => true,
            "limit" => 10
        ];
        $body = json_encode($bodyArray);
        $bodyStream = Psr7\Utils::streamFor($body);
        //You need true in the hash function so it will return the bytes instead of an string
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
        } catch (TransferException $e) {
            echo "Houston! We got the following error: " . "\n" . $e->getMessage();
        }
        //$response = $client->send($request, ['timeout' => 2]);
        //$request->getBody()->read(1);

    }
    /**
     * Send an HTTP GET request to check if a MAC address exists in the Yealink RPS
     * @param string $mac
     * @return void
     */
    function checkDeviceExists(string $mac): void
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
        } catch (TransferException $e) {
            echo $e->getMessage();
        }
    }
    /**
     * Return the signature encoding in Base64
     *
     * @param string $strToSign
     * @return string
     */
    function createSignature(string $strToSign): string
    {
        return base64_encode(hash_hmac("sha256", $strToSign, $this->secretKey, TRUE));
    }
    /**
     * Receives the parameters and then returns a single string with all the values to pass it
     * to the signature method
     * @param string $httpMethod
     * @param string $headers
     * @param string $apiUri
     * @param string $parameters
     * @return string
     */
    function createStrToSign(string $httpMethod, string $headers, string $apiUri, string $parameters = NULL): string
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
    /**
     * Create part of the string used for sigining. It will contain the headers used in the HTTP request
     *
     * @param string $accessKey
     * @param string $nonce
     * @param string $timeStamp
     * @param string $contentMd5
     * @return string
     */
    function createHeaderString(string $accessKey, string $nonce, string $timeStamp, string $contentMd5 = NULL): string
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

    function createParameterString(array $parameters): string
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
    #const BASE_URL = "https://api-dm.yealink.com:8443";
    const BASE_URL = "http://www.google.com";
    const EXISTS_LOCATION = "/api/open/v1/device/checkMac";
    const INFO_LOCATION = "/api/open/v1/device/list";
}

// $rps = new YealinkRps(accessKey: "access", secretKey: "key");
// $rps->getDeviceDetail("805e0c75c111");
#$rps->checkDeviceExists("805e0c75c111");
