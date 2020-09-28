<?php
namespace ybrenLib\serviceClient\core;

use ybrenLib\serviceClient\driver\RestServiceImpl;
use ybrenLib\serviceClient\driver\RocketMQServiceImpl;

class ServiceImpl{

    const Rest = RestServiceImpl::class;
    
    const RocketMQ = RocketMQServiceImpl::class;
}