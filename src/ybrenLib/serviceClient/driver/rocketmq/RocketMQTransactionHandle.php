<?php
namespace ybrenLib\serviceClient\driver\rocketmq;

use ybrenLib\logger\Logger;
use ybrenLib\logger\LoggerFactory;
use ybrenLib\logger\utils\ContextUtil;
use ybrenLib\serviceClient\core\TransactionHandle;

class RocketMQTransactionHandle implements TransactionHandle {

    private $receiptHandleKey = "rocketMQTransactionReceiptHandle";

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var RocketMQClient
     */
    private $rocketMQClient = null;

    public function __construct(RocketMQClient $rocketMQClient){
        $this->rocketMQClient = $rocketMQClient;
        $this->log = LoggerFactory::getLogger(RocketMQTransactionHandle::class);
    }

    public function addReceiptHandle($receiptHandle){
        $receiptHandles = ContextUtil::get($this->receiptHandleKey , []);
        $receiptHandles[] = $receiptHandle;
        ContextUtil::put($this->receiptHandleKey , $receiptHandles);
    }


    public function startTrans(){
    }

    public function commit(){
        $receiptHandles = ContextUtil::get($this->receiptHandleKey , []);
        if(!empty($receiptHandles)){
            $this->log->info("commit rocketMQ transaction[".count($receiptHandles)."]");
            $this->rocketMQClient->commit($receiptHandles);
            ContextUtil::delete($this->receiptHandleKey);
        }
    }

    public function rollback(){
        $receiptHandles = ContextUtil::get($this->receiptHandleKey , []);
        if(!empty($receiptHandles)){
            $this->log->info("rollback rocketMQ transaction[".count($receiptHandles)."]");
            $this->rocketMQClient->rollback($receiptHandles);
            ContextUtil::delete($this->receiptHandleKey);
        }
    }
}