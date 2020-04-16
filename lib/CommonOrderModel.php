<?php
require_once PATH_ROOT . '/lib/BaseModel.php';
/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/11
 * Time: 下午11:08
 */
class CommonOrderModel implements BaseModel
{
    /**
     * 创建订单时系统返回给第三方的订单编号
     * @var
     */
    public $orderId;

    public $reason = '我不想要了';

    public $deliveryStatus;

    public $time;

    /**
     * 客如云门店ID
     * @var
     */
    public $shopIdenty;

    /**
     * 订单号
     * @var
     */
    public $tradeIds = [];

    public function setOrderId($orderId)
    {
        !empty($orderId) ? $this->orderId = $orderId : trigger_error('order_id不能为空', E_USER_ERROR);
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setReason($reason)
    {
        $this->reason = $reason?:$this->reason;
    }

    public function getReason()
    {
        return $this->reason;
    }

    /**
     * 客如云门店id
     * @param $shopIdenty
     */
    public function setShopIdenty($shopIdenty)
    {
        !empty($shopIdenty) ? $this->shopIdenty = $shopIdenty : trigger_error('shop_identy不能为空', E_USER_ERROR);
    }

    public function getShopIdenty()
    {
        return $this->shopIdenty;
    }

    public function setDeliveryStatus($deliveryStatus)
    {
        !empty($deliveryStatus) ? $this->deliveryStatus = $deliveryStatus : trigger_error('delivery_status不能为空', E_USER_ERROR);
    }

    public function getDeliveryStatus()
    {
        return $this->deliveryStatus;
    }

    public function setTime($time)
    {
        !empty($time) ? $this->time = $time : trigger_error('time不能为空', E_USER_ERROR);
    }

    public function getTime()
    {
        return $this->time;
    }

    public function setTradeId(array $tradeIds)
    {
        !empty($tradeIds) ? $this->tradeIds = $tradeIds : trigger_error('trade_id不能为空', E_USER_ERROR);
    }

    public function getTradeId()
    {
        return $this->tradeIds;
    }

    public function getBusinessParams()
    {
        // TODO: Implement getBusinessParams() method.
        $params["orderId"] = (string)$this->getOrderId();
        $reason = $this->getReason();
        $reason && $params['reason'] = $reason;

        //订单配送状态编码
        //1:待接单,2:待取货,3:配送中,4:已完成,5:已取消
        $deliveryStatus = $this->getDeliveryStatus();
        $deliveryStatus && $params['deliveryStatus'] = $deliveryStatus;

        //操作时间
        $time = $this->getTime();
        $time && $params['time'] = $time;

        $tradeIds = $this->getTradeId();
        $tradeIds && $params['ids'] = $tradeIds;

        $shopIdenty = $this->getShopIdenty();
        $shopIdenty && $params['shopIdenty'] = $shopIdenty;

        return $params;
    }
}