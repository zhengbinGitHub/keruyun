<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/17
 * Time: 上午10:18
 */
class DataResponseClient
{
    /**
     * 请求响应返回的数据状态
     */
    public $messageUuid;

    /**
     * 请求响应返回的code
     */
    public $code;

    /**
     * 请求响应返回的信息
     */
    public $msg;

    /**
     * 请求响应返回的结果
     */
    public $result;

    /**
     * @var
     */
    public $apiMessage;


    /**
     * 获取返回code
     */
    public function getCode(){
        return $this->code;
    }

    public function setCode($code){
        $this->code = $code;
    }

    /**
     * 获取返回status
     */
    public function getMessageUuid(){
        return $this->messageUuid;
    }

    public function setMessageUuid($messageUuid){
        $this->messageUuid = $messageUuid;
    }

    /**
     * 获取返回msg
     */
    public function getMsg(){
        return $this->msg;
    }

    public function setMsg($msg){
        $this->msg = $msg;
    }

    public function getApiMessage(){
        return $this->apiMessage;
    }

    public function setApiMessage($msg){
        $this->apiMessage = $msg;
    }

    /**
     * 获取返回result
     */
    public function getResult(){
        return $this->result;
    }

    public function setResult($result){
        $this->result = $result;
    }
}