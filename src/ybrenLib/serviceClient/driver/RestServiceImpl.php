<?php
namespace ybrenLib\serviceClient\driver;

use ybrenLib\registerCenter\core\driver\eureka\discovery\DiscoveryStrategy;
use ybrenLib\registerCenter\core\RegisterCenterClient;
use ybrenLib\serviceClient\driver\rest\GrayBuildAndRandomStrategy;
use ybrenLib\serviceClient\exception\InstanceNotFoundException;
use ybrenLib\serviceClient\utils\HttpRequestUtil;

/**
 * Class RestServiceImpl
 * @package ybrenLib\serviceClient\driver
 */
class RestServiceImpl implements Service {

    private $timeout = 30;

    /**
     * @var RegisterCenterClient
     */
    private $registerCenterClient;

    /**
     * @var DiscoveryStrategy
     */
    private $discoveryStrategy;
    
    public function __construct(){
        $this->registerCenterClient = new RegisterCenterClient();
        $this->discoveryStrategy = new GrayBuildAndRandomStrategy();
    }

    public function sendHttpRequst(string $serviceName, string $requestUri, string $httpMode, string $dataType, array 
$data, array $option = []){
        // 获取服务节点
        $instance = $this->registerCenterClient->getInstance($serviceName , $this->discoveryStrategy);
        
        if(empty($instance)){
            throw new InstanceNotFoundException("service [".$serviceName."] instance is empty");
        }

        // 组装url
        $url = sprintf("http://%s:%s/%s" , $instance->getIp() , $instance->getPort() , $requestUri);

        // 请求服务节点
        $response = HttpRequestUtil::request($httpMode , $url , $data , $dataType , $this->timeout , [
            'Accept' => 'application/json',
        ] , true);

        if($response != null && is_array($response) && isset($response['Status'])){
            if($response['Status'] == 0){
                $exceptionClass = (isset($response['Exception']) && class_exists("ybrenLib\\serviceClass\\exception\\".$response['Exception'])) ? "ybrenLib\\serviceClass\\exception\\".$response['Exception'] : "ybrenLib\\serviceClient\\exception\\RestRequestException";
                throw new $exceptionClass(($response['ErrorMsg'] ?? "") , ($response['Code'] ?? 401));
            }
        }

        return $response;
    }
}