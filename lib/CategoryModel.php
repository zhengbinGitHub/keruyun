<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午12:53
 */
class CategoryModel
{
    public function setShopIdenty($shopIdenty)
    {
        !empty($shopIdenty) ? $this->shopIdenty = $shopIdenty : trigger_error('shop_identy不能为空', E_USER_ERROR);
    }

    public function getShopIdenty()
    {
        return $this->shopIdenty;
    }

    public function getBusinessParams()
    {
        return [];
    }

}