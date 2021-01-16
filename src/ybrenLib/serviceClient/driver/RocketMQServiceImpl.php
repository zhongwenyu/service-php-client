<?php
namespace ybrenLib\serviceClient\driver;

use ybrenLib\logger\LoggerFactory;
use ybrenLib\rocketmq\entity\TransactionSendResult;
use ybrenLib\rocketmq\producer\MQProducerFallback;
use ybrenLib\serviceClass\core\ServiceApiClient;
use ybrenLib\serviceClient\core\TransactionManagement;
use ybrenLib\serviceClient\driver\rocketmq\MessageQueueType;
use ybrenLib\serviceClient\driver\rocketmq\RocketMQClient;
use ybrenLib\serviceClient\driver\rocketmq\RocketMQProducer;
use ybrenLib\serviceClient\driver\rocketmq\RocketMQTransactionHandle;
use ybrenLib\serviceClient\ServiceClientFactory;
use ybrenLib\serviceClient\utils\HttpMode;
use ybrenLib\serviceClient\utils\HttpRequestType;

class RocketMQServiceImpl implements Service {

    private $log;

    /**
     * @var RocketMQProducer
     */
    private $rorkcetMQProducer;

    /**
     * @var TransactionSendResult[]
     */
    private $transactionSendResults = [];

    public function __construct(){
        $this->log = LoggerFactory::getLogger(RocketMQServiceImpl::class);
        $this->orkcetMQProducer = new RocketMQProducer();
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
            case MessageQueueType::COMMIT:
                // 提交事务
                $this->rorkcetMQProducer->commit($this->transactionSendResults);
                $this->transactionSendResults = [];
                break;
            case MessageQueueType::ROLLBACK:
                // 回滚事务
                $this->rorkcetMQProducer->rollback($this->transactionSendResults);
                $this->transactionSendResults = [];
                break;
            case MessageQueueType::NORMAL:
                // 普通消息
                $response = $this->rorkcetMQProducer->sendMessage($messageBody , false);
                $this->log->info("send normal message ".$response->getMsgKeys());
                break;
            case MessageQueueType::TRANSACTION_UNCHECK:
                // 事务消息
                $response = $this->rorkcetMQProducer->sendMessage($messageBody , true);
                $this->addTransaction($response);
                $this->log->info("send transaction message ".$response->getMsgKeys());
                break;
            case MessageQueueType::TRANSACTION:
                // 事务消息
                // 前置检查
                $this->transactionMessagePrecheck($serviceName , $requestUri , $data);
                $response = $this->rorkcetMQProducer->sendMessage($messageBody , true);
                $this->addTransaction($response);
                $this->log->info("send transaction message ".$response->getMsgKeys());
                break;
            /*case MessageQueueType::DELAY:
                // 延时消息
                $response = $this->rocketMQClient->sendDelayMsg($messageBody , $messageQueueParams);
                $this->log->info("send delay message ".$response->getMsgKeys());
                break;*/
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
     * @param TransactionSendResult $transactionSendResult
     */
    private function addTransaction(TransactionSendResult $transactionSendResult){
        $this->transactionSendResults[] = $transactionSendResult;
    }
}