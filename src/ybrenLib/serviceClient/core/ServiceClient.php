<?php
namespace ybrenLib\serviceClient\core;

use ybrenLib\serviceClient\driver\RestServiceImpl;
use ybrenLib\serviceClient\driver\RocketMQServiceImpl;
use ybrenLib\serviceClient\driver\Service;
use ybrenLib\serviceClient\exception\ServiceImplNotFoundException;

/**
 * Class ServiceClient
 * @package ybrenLib\serviceClient\core
 */
class ServiceClient{

    /**
     * @var RestServiceImpl
     */
    private $restServiceImpl = null;

    /**
     * @var RocketMQServiceImpl
     */
    private $rocketMQServiceImpl = null;

    /**
     * @param $serivceImplClass
     * @param $serviceName
     * @param $requestUri
     * @param $httpMode
     * @param $data
     * @param array $option
     * @return mixed
     * @throws ServiceImplNotFoundException
     */
    public function request($serivceImplClass , $serviceName , $requestUri , $httpMode , $dataType , $data , $option =
    []){
        $serviceImpl = null;
        switch ($serivceImplClass){
            case ServiceImpl::Rest:
                $serviceImpl = $this->getRestServiceImpl();
                break;
            case ServiceImpl::RocketMQ:
                $serviceImpl = $this->getRocketMQServiceImpl();
                break;    
        }
        
        if(is_null($serviceImpl)){
            throw new ServiceImplNotFoundException("service impl [".$serivceImplClass."] is not exist");
        }
        
        return $serviceImpl->sendHttpRequst($serviceName , $requestUri , $httpMode , $dataType , $data , $option);
    }

    /**
     * @return Service
     */
    private function getRestServiceImpl(){
        if(is_null($this->restServiceImpl)){
            $this->restServiceImpl = new RestServiceImpl();
        }
        return $this->restServiceImpl;
    }

    /**
     * @return Service
     */
    private function getRocketMQServiceImpl(){
        if(is_null($this->rocketMQServiceImpl)){
            $this->rocketMQServiceImpl = new RocketMQServiceImpl();
        }
        return $this->rocketMQServiceImpl;
    }
}