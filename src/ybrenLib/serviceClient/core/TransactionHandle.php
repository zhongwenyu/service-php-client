<?php
namespace ybrenLib\serviceClient\core;

interface TransactionHandle{

    function startTrans();

    function commit();

    function rollback();
}