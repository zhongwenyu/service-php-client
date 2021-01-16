<?php
namespace ybrenLib\serviceClient;

use ybrenLib\serviceClient\core\ServiceClient;

class ServiceClientFactory{

    /**
     * @var ServiceClient
     */
    private static $serviceClient = null;

    private static $fallback = null;

    /**
     * @return ServiceClient
     */
    public static function build(){
        if(is_null(self::$serviceClient)){
            self::$serviceClient = new ServiceClient();
        }
        return self::$serviceClient;
    }
}