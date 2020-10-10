<?php
namespace ybrenLib\serviceClient\utils;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use ybrenLib\logger\LoggerFactory;
use ybrenLib\logger\utils\ContextUtil;
use ybrenLib\zipkinphp\bean\ServiceZipkinBean;
use ybrenLib\zipkinphp\handler\ZipkinHandler;

class HttpRequestUtil{

    /**
     * @var Client
     */
    private static $httpClient = null;

    public static function request($method , $url , $data = []  , $type = HttpRequestType::JSON , $timeout = 5 , $headers = [
        'Accept' => 'application/json',
    ] , $zipkin = false){

        if($zipkin){
            $formatUrl = self::formatUrl($url);
            $serviceZipkinBean = new ServiceZipkinBean();
            $serviceZipkinBean->setUrl(self::formatUrl($url));
            $serviceZipkinBean->setMethod($method);
            $serviceZipkinBean->setRequest(json_encode($data , JSON_UNESCAPED_UNICODE));
            $childSpan = ZipkinHandler::serviceStart($serviceZipkinBean , $headers);
        }

        if(is_null(self::$httpClient)){
            self::$httpClient = new Client();
        }

        $method = strtoupper($method);

        $requestOptions = [
            'timeout' => $timeout,
        ];
        switch ($type){
            case "query" :
                if(is_array($data)){
                    $requestOptions['query'] = http_build_query($data);
                }else{
                    $requestOptions['query'] = $data;
                }
                break;
            case "form" :
                $requestOptions['form_params'] = $data;
                break;
            case "json" :
                $requestOptions['json'] = $data;
                break;
            case "body" :
                $requestOptions['body'] = $data;
                break;
        }

        (is_array($headers) && !empty($headers)) && $requestOptions['headers'] = $headers;

        $response = null;
        $jsonResponseBody = null;
        try{
            LoggerFactory::getLogger(HttpRequestUtil::class)->info("begin request url [".$formatUrl."] , options: "
                .json_encode($requestOptions , JSON_UNESCAPED_UNICODE));

            $response = self::$httpClient->request($method , $url , $requestOptions);
            $responseContent = $response->getBody()->getContents();

            LoggerFactory::getLogger(HttpRequestUtil::class)->info("request url [".$formatUrl."] response: "
                .$responseContent);

            $jsonResponseBody = json_decode($responseContent , true);

            $zipkin && ZipkinHandler::serviceEnd($childSpan , $serviceZipkinBean);

            ContextUtil::put(HttpRequestUtil::class . "response" , $response);

            return is_null($jsonResponseBody) ? $responseContent : $jsonResponseBody;
        }catch (\Exception $e){
            LoggerFactory::getLogger(HttpRequestUtil::class)->error("request [".$formatUrl."] fail: ".$e->getMessage());

            if($zipkin){
                $serviceZipkinBean->setException($e);
                ZipkinHandler::serviceEnd($childSpan , $serviceZipkinBean);
            }

            throw $e;
        }
    }

    /**
     * @return Response
     */
    public static function getResponse(){
        return ContextUtil::get(HttpRequestUtil::class . "response" , null);
    }

    public static function get($url , $data = []  , $type = HttpRequestType::QUERY , $timeout = 5 , $headers = ['Accept' => 'application/json',] , $zipkin = true){
        return self::request('GET' , $url , $data  , $type , $timeout , $headers , $zipkin);
    }

    public static function post($url , $data = []  , $type = HttpRequestType::JSON , $timeout = 5 , $headers = ['Accept' => 'application/json',] , $zipkin = true){
        return self::request('POST' , $url , $data  , $type , $timeout , $headers , $zipkin);
    }

    public static function put($url , $data = []  , $type = HttpRequestType::JSON , $timeout = 5 , $headers = ['Accept' => 'application/json',] , $zipkin = true){
        return self::request('PUT' , $url , $data  , $type , $timeout , $headers , $zipkin);
    }

    public static function delete($url , $data = []  , $type = HttpRequestType::JSON , $timeout = 5 , $headers = ['Accept' => 'application/json',] , $zipkin = true){
        return self::request('DELETE' , $url , $data  , $type , $timeout , $headers , $zipkin);
    }

    private static function formatUrl($url){
        if(strpos($url  ,"@")){
            $regex = "/(?<=:).*?(?=@)/";
            return preg_replace($regex , "***" , $url);
        }
        return $url;
    }
}