<?php
namespace ybrenLib\serviceClient\driver\rocketmq;

use ybrenLib\logger\LoggerFactory;
use ybrenLib\rocketmq\entity\Message;
use ybrenLib\rocketmq\entity\TransactionSendResult;
use ybrenLib\rocketmq\producer\TransactionMQProducer;
use ybrenLib\zipkinphp\bean\ProducerZipkinBean;
use ybrenLib\zipkinphp\handler\ZipkinHandler;

class RocketMQProducer
{
    /**
     * @var TransactionMQProducer
     */
    private $transactionMQProducer;
    private $topic;

    public function __construct(){
        $rocketMQClusterConfig = RocketMQConfig::getRocketMQClusterConfig();
        $topicAndGroupConfig = RocketMQConfig::getTopicAndGroupConfig();
        $this->topic = $topicAndGroupConfig["topic"];
        $this->transactionMQProducer = new TransactionMQProducer($topicAndGroupConfig["group"]);
        $this->transactionMQProducer->setNamesrvAddr($rocketMQClusterConfig->getNamesrvAddr());
        // 事务回查时间
        $this->transactionMQProducer->setCheckImmunityTime(60);
        // 设置本地事务执行回调
        $this->transactionMQProducer->setTransactionListener(RocketMQConfig::getTransactionListener());
        $this->transactionMQProducer->start();
    }

    /**
     * @param TransactionSendResult[]
     */
    public function commit(array $transactionSendResults){
        if(!empty($transactionSendResults)){
            foreach ($transactionSendResults as $transactionSendResult){
                try{
                    $result = $this->transactionMQProducer->commit($transactionSendResult);
                    if($result == null || $result->getCode() != 0){
                        throw new \Exception("commit fail");
                    }
                    LoggerFactory::getLogger(RocketMQProducer::class)->info("commit {} success" , $transactionSendResult->getMsgKeys());
                }catch (\Exception $e){
                    LoggerFactory::getLogger(RocketMQProducer::class)->error("commit {} error: {}" , $transactionSendResult->getMsgKeys() , $e->getMessage());
                }
            }
        }
    }

    /**
     * @param TransactionSendResult[]
     */
    public function rollback(array $transactionSendResults){
        if(!empty($transactionSendResults)){
            foreach ($transactionSendResults as $transactionSendResult){
                try{
                    $result = $this->transactionMQProducer->rollback($transactionSendResult);
                    if($result == null || $result->getCode() != 0){
                        throw new \Exception("rollback fail");
                    }
                    LoggerFactory::getLogger(RocketMQProducer::class)->info("rollback {} success" , $transactionSendResult->getMsgKeys());
                }catch (\Exception $e){
                    LoggerFactory::getLogger(RocketMQProducer::class)->error("rollback {} error: {}" , $transactionSendResult->getMsgKeys() , $e->getMessage());
                }
            }
        }
    }

    /**
     * 发送消息
     * @param $messageBody
     * @param false $isTransaction
     * @return \ybrenLib\rocketmq\entity\SendResult|TransactionSendResult
     * @throws \ybrenLib\rocketmq\exception\RocketMQClientException
     */
    public function sendMessage($messageBody , $isTransaction = false){
        $message = new Message($this->topic , "");
        $messageKey = $this->getUniqueId();
        // zipkin start
        $producerZipkinBean = new ProducerZipkinBean();
        $producerZipkinBean->setMessageKey($messageKey);
        $producerZipkinBean->setBody(json_encode($messageBody , JSON_UNESCAPED_UNICODE));
        $producerZipkinBean->setDestination($this->topic);

        $headers = [];
        $childSpan = ZipkinHandler::produceStart($producerZipkinBean , $headers);
        if(!empty($headers)){
            $messageBody['headers'] = $headers;
        }
        $message->setBody(json_encode($messageBody , JSON_UNESCAPED_UNICODE));

        try{
            if($isTransaction){
                $sendResult = $this->transactionMQProducer->send($message);
            }else{
                $sendResult = $this->transactionMQProducer->sendMessageInTransaction($message);
            }
            ZipkinHandler::produceEnd($childSpan , $producerZipkinBean);
        }catch (\Exception $e){
            ZipkinHandler::produceEnd($childSpan , $producerZipkinBean);
            throw $e;
        }

        return $sendResult;
    }

    private function getUniqueId($type = ""){
        return md5("rocketMQ:".$type.":messageKey:".uniqid(rand() , true));
    }
}