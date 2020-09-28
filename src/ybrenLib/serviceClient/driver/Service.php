<?php
namespace ybrenLib\serviceClient\driver;

interface Service{

    /**
     * 发送http请求
     * @param string $serviceName
     * @param string $requestUri
     * @param string $httpMode
     * @param string $dataType
     * @param array $data
     * @param array $option
     * @return mixed
     */
    function sendHttpRequst(string $serviceName , string $requestUri , string $httpMode , string $dataType , array
    $data , array $option = []);
}