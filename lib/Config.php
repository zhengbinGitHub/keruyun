<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午12:09
 */
class Config
{
    /**
     * 客如云开发者app_key
     */
    public $app_key = '';

    /**
     * 客如云开发者app_secret
     */
    public $app_secret = '';

    /**
     * api版本
     */
    public $v = "1.0";

    /**
     * 门店ID
     * @var
     */
    public $shop_identy;

    /**
     * 门店对应TOEKN
     * @var string
     */
    public $token;

    const SANDBOX_URL = 'https://gldopenapi.keruyun.com';
    const PRODUCT_URL = 'https://openapi.keruyun.com';

    /**
     * 构造函数
     */
    public function __construct($shop_identy, $appKey = '', $appSecret = '', $token = '', $isOnline = 0){
        $this->host = $isOnline ? self::PRODUCT_URL : self::SANDBOX_URL;
        $this->app_key = $appKey?:$this->app_key;
        $this->app_secret = $appSecret?:$this->app_secret;
        $this->shop_identy = $shop_identy;//商户号
        $this->token = $token;
    }

    public function getAppKey(){
        return $this->app_key;
    }

    public function getAppSecret(){
        return $this->app_secret;
    }

    public function getV(){
        return $this->v;
    }

    public function getShopIdenty(){
        return $this->shop_identy;
    }

    public function getHost(){
        return $this->host;
    }

    public function getToken()
    {
        return $this->token;
    }
}