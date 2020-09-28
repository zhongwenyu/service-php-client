<?php
namespace ybrenLib\serviceClient\driver\rocketmq;

use MQ\Constants;
use MQ\Http\HttpClient;
use MQ\Model\TopicMessage;
use MQ\MQClient;
use MQ\MQProducer;
use MQ\Requests\AckMessageRequest;
use MQ\Responses\AckMessageResponse;
use ybrenLib\zipkinphp\bean\ProducerZipkinBean;
use ybrenLib\zipkinphp\handler\ZipkinHandler;

class RocketMQClient{

    /**
     * @var MQClient 
     */
    private $client;

    /**
     * @var HttpClient
     */
    private $httpClient = null;
    
    private $instanceId;

    /**
     * @var AliyunRocketMQConnectConfig
     */
    private $rocketmqConnectConfig;
    
    /**
     * @var \MQ\MQTransProducer
     */
    private $transactionProducer = null;

    /**
     * @var \MQ\MQTransProducer
     */
    private $normalProducer = null;

    /**
     * @var \MQ\MQProducer
     */
    private $delayProducer = null;

    private $messageKey = "";

    private $headers = [];

    private $config = [];

    public function __construct(){
        $this->rocketMQConnectConfig = RocketMQConfig::getAliyunConnectConfig();
        $this->instanceId = $this->rocketMQConnectConfig->getInstanceId();
        $this->client = new MQClient($this->rocketMQConnectConfig->getEndPoint(), $this->rocketMQConnectConfig->getAccessKey(),
            $this->rocketMQConnectConfig->getSecretKey());
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient(){
        return $this->httpClient ?? new HttpClient($this->rocketMQConnectConfig->getEndPoint(), $this->rocketMQConnectConfig->getAccessKey(),
                $this->rocketMQConnectConfig->getSecretKey(), null, null);
    }
    
    public function getMessageKey(){
        return $this->messageKey;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return \MQ\MQTransProducer
     * @throws \Exception
     */
    public function getTransactionProducer(){
        if(is_null($this->transactionProducer)){
            $this->transactionProducer = $this->client->getTransProducer($this->instanceId , "common_transaction" , "GID_transaction");
        }
        return $this->transactionProducer;
    }

    /**
     * @return \MQ\MQTransProducer
     * @throws \Exception
     */
    public function getNormalProducer(){
        if(is_null($this->normalProducer)){
            $this->normalProducer = $this->client->getProducer($this->instanceId , "common_normal");
        }
        return $this->normalProducer;
    }

    /**
     * @return \MQ\MQProducer
     * @throws \Exception
     */
    public function getDelayProducer(){
        if(is_null($this->delayProducer)){
            $this->delayProducer = $this->client->getProducer($this->instanceId , "common_delay" );
        }
        return $this->delayProducer;
    }

    /**
     * 发送事务消息
     * @param array $messageBody
     * @return TopicMessage
     */
    public function sendTransactionMsg(array $messageBody){
        $result = $this->sendMsg($this->getTransactionProducer() , MessageQueueType::TRANSACTION , $messageBody);
        return $result->getReceiptHandle();
    }

    /**
     * 发送普通消息
     * @param array $messageBody
     * @return TopicMessage
     */
    public function sendNormalMsg(array $messageBody){
        return $this->sendMsg($this->getNormalProducer() , MessageQueueType::NORMAL , $messageBody);
    }

    /**
     * 获取半消息
     * @param $numOfMessages
     * @param int $waitSeconds
     * @return \MQ\Message
     */
    public function consumeHalfMessage($numOfMessages, $waitSeconds = 3){
        $transProducer = $this->getTransactionProducer();
        return $transProducer->consumeHalfMessage($numOfMessages , $waitSeconds);
    }

    /**
     * 发送定时消息
     * @param array $messageBody
     * @param array $messageQueueParams
     * @return TopicMessage
     */
    public function sendDelayMsg(array $messageBody,$messageQueueParams){
        return $this->sendMsg($this->getDelayProducer() , MessageQueueType::DELAY , $messageBody , (isset($messageQueueParams['sendTime'])?strtotime($messageQueueParams['sendTime'])*1000:''));
    }

    /**
     * 发送MQ消息
     * @param MQProducer $MQProducer
     * @param $messageType
     * @param $messageBody
     * @param null $sendTime
     * @return TopicMessage
     * @throws \Exception
     */
    private function sendMsg(MQProducer $MQProducer , $messageType , $messageBody , $sendTime = null){
        $this->messageKey = $messageKey = $messageBody['_msgId'] = $this->getUniqueId($messageType);
        $messageBody['_createAt'] = time();

        // zipkin start
        $producerZipkinBean = new ProducerZipkinBean();
        $producerZipkinBean->setMessageKey($messageKey);
        $producerZipkinBean->setBody(json_encode($messageBody , JSON_UNESCAPED_UNICODE));
        $producerZipkinBean->setInstanceId($this->config['instanceId']);
        $producerZipkinBean->setDestination($MQProducer->getTopicName());
        $producerZipkinBean->setEndpoint($this->config['endPoint']);

        $headers = [];
        $childSpan = ZipkinHandler::produceStart($producerZipkinBean , $headers);
        if(!empty($headers)){
            $messageBody['headers'] = $headers;
        }

        $pubMsg = new TopicMessage(json_encode($messageBody , JSON_UNESCAPED_UNICODE));
        $pubMsg->setMessageKey($messageKey);
        if(!is_null($sendTime)){
            $pubMsg->setStartDeliverTime($sendTime); //毫秒
        }

        $result = null;
        try{
            $result = $MQProducer->publishMessage($pubMsg);
            if($result != null){
                $producerZipkinBean->setMessageId($result->getMessageId());
                $producerZipkinBean->setResponse(json_encode($result , JSON_UNESCAPED_UNICODE));
            }
            ZipkinHandler::produceEnd($childSpan , $producerZipkinBean);
        }catch (\Exception $exception){
            $producerZipkinBean->setException($exception);
            ZipkinHandler::produceEnd($childSpan , $producerZipkinBean);
            throw $exception;
        }
        return $result;
    }

    /**
     * 事务提交
     * @param array $receiptHandles
     * @return \MQ\Responses\AckMessageResponse
     */
    public function commit(array $receiptHandles){
        $request = new AckMessageRequest($this->instanceId, $this->rocketmqConnectConfig->getTopicTransaction(),
            $this->rocketmqConnectConfig->getGrouIdTransaction(), $receiptHandles);
        $request->setTrans(Constants::TRANSACTION_COMMIT);
        $response = new AckMessageResponse();
        return $this->getHttpClient()->sendRequest($request, $response);
    }

    /**
     * 事务回滚
     * @param $receiptHandle
     */
    public function rollback($receiptHandle){
        $this->transactionProducer->rollback($receiptHandle);
    }

    public function getUniqueId($type = ""){
        return md5("rocketMQ:".$type.":messageKey:".uniqid(rand() , true));
    }
}