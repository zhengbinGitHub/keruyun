<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午12:53
 */
class ShopModel
{
    /**
     * 商户id
     * @var
     */
    public $shopIdenty;

    /**
     * 合作方商户id
     * @var
     */
    public $tpShopId;

    /**
     * 商户名称
     * @var
     */
    public $shopName;

    public function setShopIdenty($shopIdenty)
    {
        !empty($shopIdenty) ? $this->shopIdenty = $shopIdenty : trigger_error('shop_identy不能为空', E_USER_ERROR);
    }

    public function getShopIdenty()
    {
        return $this->shopIdenty;
    }

    public function setTpShopId($tpShopId)
    {
        !empty($tpShopId) ? $this->tpShopId = $tpShopId : trigger_error('tp_shop_id不能为空', E_USER_ERROR);
    }

    public function getTpShopId()
    {
        return $this->tpShopId;
    }

    public function setShopName($shopName)
    {
        !empty($shopName) ? $this->shopName = $shopName : trigger_error('shop_name不能为空', E_USER_ERROR);
    }

    public function getShopName()
    {
        return $this->shopName;
    }
}