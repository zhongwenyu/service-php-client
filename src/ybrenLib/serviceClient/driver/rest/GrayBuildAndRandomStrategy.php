<?php
namespace ybrenLib\serviceClient\driver\rest;

use ybrenLib\apolloClient\ConfigService;
use ybrenLib\logger\Logger;
use ybrenLib\logger\LoggerFactory;
use ybrenLib\registerCenter\core\bean\Instance;
use ybrenLib\registerCenter\core\driver\eureka\discovery\DiscoveryStrategy;

class GrayBuildAndRandomStrategy implements DiscoveryStrategy{

    /**
     * @var Logger
     */
    private $log;

    private $defaultBuildVersion = 1;

    public function __construct(){
        $this->log = LoggerFactory::getLogger(GrayBuildAndRandomStrategy::class);
    }

    /**
     * @param Instance[] $instances
     * @return Instance
     */
    public function getInstance($instances){
        $upInstances = $this->getUpInstances($instances);
        if(empty($upInstances)){
            // 无视下线服务
            $this->log->info("not found alive node,return all [".json_encode($instances)."]");
            $upInstances = $instances;
        }
        // 灰度筛选
        $upInstances = $this->grayBuildFliter($upInstances);
        return $upInstances[rand(0 , (count($upInstances) - 1))];
    }

    /**
     * 灰度筛选
     * @param Instance[] $instances
     * @return Instance[]
     */
    private function grayBuildFliter($instances){
        $buildVersion = $this->getBuildVersion();
        $grayInstances = [];
        if(!empty($instances)){
            foreach ($instances as $instance){
                $meteData = $instance->getMetaData();
                if($meteData['buildVersion'] == $buildVersion){
                    $this->log->info("add gray node [".$instance->getAddress()."]");
                    $grayInstances[] = $instance;
                }
            }
            if(empty($grayInstances)){
                $grayInstances = $instances;
                $this->log->info("not found gray node,return all[".json_encode($instances)."]");
            }
        }
        return $grayInstances;
    }

    /**
     * 获取灰度版本号
     * @return int
     */
    private function getBuildVersion(){
        $buildVersion = $this->defaultBuildVersion;
        try{
            $constants = ConfigService::getConfigProperty("backGroup.constant");
            $buildVersion = $constants['buildVersion'] ?? $this->defaultBuildVersion;
        }catch (\Exception $e){
            $this->log->errorWithException($e->getMessage() , $e);
        }
        return $buildVersion;
    }

    /**
     * @param Instance[] $instances
     * @return Instance[]
     */
    private function getUpInstances($instances){
        $result = [];
        if(!empty($instances)){
            foreach ($instances as $instance){
                if($instance->getStatus() != "UP"){
                    $this->log->info("instance[".$instance->getAddress()."] has removed because ["
                        .$instance->getStatus()
                        ."]");
                    continue;
                }
                $result[] = $instance;
            }
        }
        return $result;
    }
}