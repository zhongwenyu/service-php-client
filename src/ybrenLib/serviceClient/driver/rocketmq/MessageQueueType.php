<?php
namespace ybrenLib\serviceClient\driver\rocketmq;

class MessageQueueType{

    // 不使用消息
    const UN_USED = 0;

    // 事务消息
    const TRANSACTION = 1;

    // 普通消息
    const NORMAL = 2;

    // 顺序消息
    const ORDERLY = 3;

    // 延时消息
    const DELAY = 4;

    // 事务消息(不需要前置检查)
    const TRANSACTION_UNCHECK = 5;
}