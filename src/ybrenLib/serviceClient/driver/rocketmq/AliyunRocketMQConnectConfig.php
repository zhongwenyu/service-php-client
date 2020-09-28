<?php
namespace ybrenLib\serviceClient\driver\rocketmq;

class AliyunRocketMQConnectConfig{

    private $accessKey;

    private $secretKey;

    private $endPoint;

    private $instanceId;

    private $topicNormal;

    private $topicDelay;

    private $topicTransaction;
    
    private $grouIdTransaction;

    /**
     * @return mixed
     */
    public function getAccessKey()
    {
        return $this->accessKey;
    }

    /**
     * @param mixed $accessKey
     */
    public function setAccessKey($accessKey)
    {
        $this->accessKey = $accessKey;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @param mixed $secretKey
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @return mixed
     */
    public function getEndPoint()
    {
        return $this->endPoint;
    }

    /**
     * @param mixed $endPoint
     */
    public function setEndPoint($endPoint)
    {
        $this->endPoint = $endPoint;
    }

    /**
     * @return mixed
     */
    public function getInstanceId()
    {
        return $this->instanceId;
    }

    /**
     * @param mixed $instanceId
     */
    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;
    }

    /**
     * @return mixed
     */
    public function getTopicNormal()
    {
        return $this->topicNormal;
    }

    /**
     * @param mixed $topicNormal
     */
    public function setTopicNormal($topicNormal)
    {
        $this->topicNormal = $topicNormal;
    }

    /**
     * @return mixed
     */
    public function getTopicDelay()
    {
        return $this->topicDelay;
    }

    /**
     * @param mixed $topicDelay
     */
    public function setTopicDelay($topicDelay)
    {
        $this->topicDelay = $topicDelay;
    }

    /**
     * @return mixed
     */
    public function getTopicTransaction()
    {
        return $this->topicTransaction;
    }

    /**
     * @param mixed $topicTransaction
     */
    public function setTopicTransaction($topicTransaction)
    {
        $this->topicTransaction = $topicTransaction;
    }

    /**
     * @return mixed
     */
    public function getGrouIdTransaction()
    {
        return $this->grouIdTransaction;
    }

    /**
     * @param mixed $grouIdTransaction
     */
    public function setGrouIdTransaction($grouIdTransaction)
    {
        $this->grouIdTransaction = $grouIdTransaction;
    }
}