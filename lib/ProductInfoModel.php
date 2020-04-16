<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午12:53
 */
class ProductInfoModel
{
    public $shopIdenty, $ids;

    public function setShopIdenty($shopIdenty)
    {
        !empty($shopIdenty) ? $this->shopIdenty = $shopIdenty : trigger_error('shop_identy不能为空', E_USER_ERROR);
    }

    public function getShopIdenty()
    {
        return $this->shopIdenty;
    }

    public function setIds(array $ids)
    {
        !empty($ids) ? $this->ids = $ids : trigger_error('菜品Ids不能为空', E_USER_ERROR);
    }

    public function getIds()
    {
        return $this->ids;
    }

    public function getBusinessParams()
    {
        $params=[];
        $params['shopIdenty'] = $this->getShopIdenty();
        $params['ids'] = $this->getIds();
        return $params;
    }

}