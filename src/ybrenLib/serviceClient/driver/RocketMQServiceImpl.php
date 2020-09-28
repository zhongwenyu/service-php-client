<?php
namespace ybrenLib\serviceClient\driver;

use ybrenLib\logger\LoggerFactory;
use ybrenLib\serviceClient\driver\rocketmq\MessageQueueType;
use ybrenLib\serviceClient\driver\rocketmq\RocketMQClient;

class RocketMQServiceImpl implements Service {

    private $log;

    /**
     * @var RocketMQClient
     */
    private $rocketMQClient;

    public function __construct(){
        $this->log = LoggerFactory::getLogger(RocketMQServiceImpl::class);
        $this->rocketMQClient = new RocketMQClient();
    }

    /**
     * 发送rocketmq消息
     * @param string $serviceName
     * @param string $requestUri
     * @param string $httpMode
     * @param string $dataType
     * @param array $data
     * @param array $option
     * @return mixed|void
     */
    public function sendHttpRequst(string $serviceName, string $requestUri, string $httpMode, string $dataType, array
    $data, array $option = []){
        $messageQueueType = $option['messageQueueType'];
        $messageQueueParams = $option['messageQueueParams'] ?? [];

        $messageBody = [
            "serviceName" => $serviceName,
            "mode" => $httpMode,
            "path" => $requestUri,
            "param" => $data,
        ];

        $response = null;
        switch ($messageQueueType){
            case MessageQueueType::NORMAL:
                // 普通消息
                $response = $this->rocketMQClient->sendNormalMsg($messageBody);
                $this->log->info("send normal message ".$response->getMessageId());
                break;
            case MessageQueueType::TRANSACTION:
                // 普通消息
                $response = $this->rocketMQClient->sendTransactionMsg($messageBody);
                $this->log->info("send transaction message ".$response->getMessageId());
                break;
            case MessageQueueType::DELAY:
                // 普通消息
                $response = $this->rocketMQClient->sendDelayMsg($messageBody , $messageQueueParams);
                $this->log->info("send delay message ".$response->getMessageId());
                break;
            case MessageQueueType::COMMIT:
                // 事务提交
                $response = $this->rocketMQClient->commit($data);
                $this->log->info("commit transaction message [".count($data)."] ".$response->getStatusCode());
                break;

        }

        return $response;
    }
}