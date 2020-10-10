<?php
namespace ybrenLib\serviceClient\core;

use ybrenLib\logger\LoggerFactory;
use ybrenLib\logger\utils\ContextUtil;

class TransactionManagement{

    private static $linkKey = "transactionManagementLinkIds";

    private static $flag = "transactionManagementFlag";

    /**
     * 事务开始
     */
    public static function startTrans(){
        ContextUtil::put(self::$flag , true);
    }

    /**
     * 事务提交
     * @throws \think\exception\PDOException
     */
    public static function commit(){
        $linkIds = ContextUtil::get(TransactionManagement::$linkKey , []);
        if(!empty($linkIds)){
            foreach ($linkIds as $linkId => $receiptHandle){
                if($receiptHandle instanceof TransactionHandle){
                    LoggerFactory::getLogger(TransactionManagement::class)->info("commit transaction: ".$linkId);
                    $receiptHandle->commit();
                }
            }
        }

        // 事务重置
        self::reset();
    }

    /**
     * 事务回滚
     * @throws \think\exception\PDOException
     */
    public static function rollback(){
        $linkIds = ContextUtil::get(TransactionManagement::$linkKey , []);
        if(!empty($linkIds)){
            foreach ($linkIds as $linkId => $receiptHandle){
                if($receiptHandle instanceof TransactionHandle){
                    LoggerFactory::getLogger(TransactionManagement::class)->info("rollback transaction: ".$linkId);
                    $receiptHandle->rollback();
                }
            }
        }

        // 事务重置
        self::reset();
    }

    /**
     * 添加事务权柄
     * @param $receiptHandle
     */
    public static function addTransactionHandle($transactionHandle , $linkId = null){
        $linkIds = ContextUtil::get(TransactionManagement::$linkKey , []);
        if(is_null($linkId)){
            $linkId = uniqid();
        }
        if(!isset($linkIds[$linkId]) && $transactionHandle instanceof TransactionHandle){
            LoggerFactory::getLogger(TransactionManagement::class)->info("addTransactionHandle: ".$linkId);
            $linkIds[$linkId] = $transactionHandle;
            $transactionHandle->startTrans();
        }
        ContextUtil::put(TransactionManagement::$linkKey , $linkIds);
    }

    /**
     * @return boolean
     */
    public static function checkTransStatus(){
        return ContextUtil::get(self::$flag , false);
    }

    /**
     * 重置
     */
    private static function reset(){
        ContextUtil::delete(TransactionManagement::$linkKey);
        ContextUtil::delete(TransactionManagement::$flag);
    }
}