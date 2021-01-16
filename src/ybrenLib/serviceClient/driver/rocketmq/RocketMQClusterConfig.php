<?php
namespace ybrenLib\serviceClient\driver\rocketmq;


class RocketMQClusterConfig
{
    private $namesrvAddr;

    /**
     * @return mixed
     */
    public function getNamesrvAddr()
    {
        return $this->namesrvAddr;
    }

    /**
     * @param mixed $namesrvAddr
     */
    public function setNamesrvAddr($namesrvAddr)
    {
        $this->namesrvAddr = $namesrvAddr;
    }


}