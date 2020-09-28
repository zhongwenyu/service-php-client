<?php
namespace ybrenLib\serviceClient\driver\rocketmq;

use ybrenLib\apolloClient\ConfigService;
use ybrenLib\apolloClient\core\utils\EncryptUtil;
use ybrenLib\logger\LoggerFactory;

class RocketMQConfig{

    /**
     * 获取阿里云rocketmq连接配置
     * @return AliyunRocketMQConnectConfig
     */
    public static function getAliyunConnectConfig(){
        $rocketMQConnectConfig = new AliyunRocketMQConnectConfig();
        try{
            $apolloConfig = ConfigService::getConfigProperty("backGroup.rocketmqApi");
            if(!empty($apolloConfig)){
                $rocketMQConnectConfig->setInstanceId($apolloConfig['rocketmq.instanceId']);
                $rocketMQConnectConfig->setAccessKey($apolloConfig['rocketmq.accessKey']);
                $rocketMQConnectConfig->setEndPoint($apolloConfig['rocketmq.endPointHttp']);
                $rocketMQConnectConfig->setSecretKey(EncryptUtil::decrypt($apolloConfig['rocketmq.secretKey']
                    , $apolloConfig['rocketmq.key']));
                $rocketMQConnectConfig->setTopicNormal($apolloConfig['rocketmq.topic.normal']);
                $rocketMQConnectConfig->setTopicDelay($apolloConfig['rocketmq.topic.delay']);
                $rocketMQConnectConfig->setTopicTransaction($apolloConfig['rocketmq.topic.transaction']);
                $rocketMQConnectConfig->setGrouIdTransaction($apolloConfig['rocketmq.groupId.transaction']);
                return $rocketMQConnectConfig;
            }
        }catch (\Exception $e){
            LoggerFactory::getLogger(RocketMQConfig::class)->warn("apollo rocketmq config has not found: ".$e->getMessage());
        }

        if(!class_exists('Yaconf')){
            throw new \Exception("class Yaconf is not exists");
        }
        LoggerFactory::getLogger(RocketMQConfig::class)->info("get rocketmq config from yaconf");
        $yaconf = new \Yaconf();
        $yaconfConfig = $yaconf::get("database.rocketMQ" , null);
        $rocketMQConnectConfig->setInstanceId($yaconfConfig['instanceId']);
        $rocketMQConnectConfig->setAccessKey($yaconfConfig['accessId']);
        $rocketMQConnectConfig->setEndPoint($yaconfConfig['endPoint']);
        $rocketMQConnectConfig->setSecretKey($yaconfConfig['accessKey']);
        $rocketMQConnectConfig->setTopicNormal($yaconfConfig['topicNormal'] ?? "common_normal");
        $rocketMQConnectConfig->setTopicDelay($yaconfConfig['topicDelay'] ?? "common_delay");
        $rocketMQConnectConfig->setTopicTransaction($yaconfConfig['topicTransaction'] ?? "common_transaction");
        $rocketMQConnectConfig->setGrouIdTransaction($yaconfConfig['groupIdTransaction'] ?? "GID_transaction");
        return $rocketMQConnectConfig;
    }
}