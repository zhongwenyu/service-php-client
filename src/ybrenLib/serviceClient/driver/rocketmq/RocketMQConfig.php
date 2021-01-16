<?php
namespace ybrenLib\serviceClient\driver\rocketmq;

use ybrenLib\apolloClient\ConfigService;
use ybrenLib\apolloClient\core\utils\EncryptUtil;
use ybrenLib\logger\LoggerFactory;
use ybrenLib\rocketmq\producer\TransactionListener;

class RocketMQConfig{

    /**
     * @return RocketMQClusterConfig
     */
    public static function getRocketMQClusterConfig(){
        $rocketMQClusterConfig = new RocketMQClusterConfig();
        $apolloConfig = ConfigService::getConfigProperty("backGroup.rocketMQCluster");
        $rocketMQClusterConfig->setNamesrvAddr($apolloConfig["rocketMQCluster.namesrv"]);
        return $rocketMQClusterConfig;
    }

    /**
     * @return array
     */
    public static function getTopicAndGroupConfig(){
        $topic = ConfigService::getAppConfig("rocketMQRestTopic");
        $group = ConfigService::getAppConfig("rocketMQRestGroup");
        if(empty($topic) || empty($group)){
            throw new \Exception("topic or group must not be empty");
        }
        return [
            "topic" => $topic,
            "group" => $group
        ];
    }

    /**
     * @return TransactionListener
     */
    public static function getTransactionListener(){
        $transactionListener = ConfigService::getAppConfig("rocketMQRestTransactionListener");
        return new $transactionListener();
    }
}