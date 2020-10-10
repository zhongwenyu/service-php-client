<?php
namespace ybrenLib\serviceClient\driver;

use ybrenLib\logger\LoggerFactory;
use ybrenLib\serviceClass\core\ServiceApiClient;
use ybrenLib\serviceClient\core\TransactionManagement;
use ybrenLib\serviceClient\driver\rocketmq\MessageQueueType;
use ybrenLib\serviceClient\driver\rocketmq\RocketMQClient;
use ybrenLib\serviceClient\driver\rocketmq\RocketMQTransactionHandle;
use ybrenLib\serviceClient\ServiceClientFactory;
use ybrenLib\serviceClient\utils\HttpMode;
use ybrenLib\serviceClient\utils\HttpRequestType;

class RocketMQServiceImpl implements Service {

    private $log;

    /**
     * @var RocketMQClient
     */
    private $rocketMQClient;

    /**
     * @var RocketMQTransactionHandle
     */
    private $rocketMQTransactionHandle;

    public function __construct(){
        $this->log = LoggerFactory::getLogger(RocketMQServiceImpl::class);
        $this->rocketMQClient = new RocketMQClient();
        $this->rocketMQTransactionHandle = new RocketMQTransactionHandle($this->rocketMQClient);
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
        $messageQueueType = $option['messageQueueType'] ?? MessageQueueType::UN_USED;
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
            case MessageQueueType::TRANSACTION_UNCHECK:
                // 事务消息
                $response = $this->rocketMQClient->sendTransactionMsg($messageBody);
                $this->addTransaction($response);
                $this->log->info("send transaction message ".$response->getMessageId());
                break;
            case MessageQueueType::TRANSACTION:
                // 事务消息
                // 前置检查
                $this->transactionMessagePrecheck($serviceName , $requestUri , $data);
                $response = $this->rocketMQClient->sendTransactionMsg($messageBody);
                $this->addTransaction($response->getReceiptHandle());
                $this->log->info("send transaction message ".$response->getMessageId());
                break;
            case MessageQueueType::DELAY:
                // 延时消息
                $response = $this->rocketMQClient->sendDelayMsg($messageBody , $messageQueueParams);
                $this->log->info("send delay message ".$response->getMessageId());
                break;
        }

        return $response;
    }

    /**
     * 事务消息前置检查
     * @param $serviceName
     * @param $data
     * @throws \ybrenLib\serviceClient\exception\ServiceImplNotFoundException
     */
    private function transactionMessagePrecheck($serviceName , $requestUri , $data){
        $requestPathArr = explode("/" , $requestUri);
        $response = ServiceClientFactory::build()->request(RestServiceImpl::class , $serviceName , "Transaction/check" ,
            HttpMode::POST ,
            HttpRequestType::JSON , [
                "controller" => $requestPathArr[0] ?? "",
                "action" => $requestPathArr[1] ?? "",
                "requestUri" => $requestUri,
                "data" => $data
            ]);
    }

    /**
     * @param $receiptHandle
     */
    private function addTransaction($receiptHandle){
        // 添加事务处理器
        TransactionManagement::addTransactionHandle($this->rocketMQTransactionHandle , "rocketMQTransactionHandle");
        // 添加事务权柄
        $this->rocketMQTransactionHandle->addReceiptHandle($receiptHandle);
    }
}