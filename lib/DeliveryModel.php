<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午1:17
 */
class DeliveryModel
{
    /**
     * 期望送达时间,自提预约取货，时间戳，精确到秒，为0则立即送餐
     * @var
     */
    public $expectTime;

    /**
     * 配送方式,1:商家自配送,2:平台配送,3:自提
     * @var
     */
    public $deliveryParty;

    /**
     * 收货人姓名
     * @var
     */
    public $receiverName;

    /**
     * 收货人电话
     * @var
     */
    public $receiverPhone;

    public $receiverGender;

    public $delivererName;

    public $delivererPhone;

    public $delivererAddress;

    public $coordinateType;

    public $longitude;

    public $latitude;

    public function setExpectTime($expectTime)
    {
        !is_null($expectTime) ? $this->expectTime = $expectTime : trigger_error('expect_time不能为空', E_USER_ERROR);
    }

    public function getExpectTime()
    {
        return $this->expectTime;
    }

    public function setDeliveryParty($deliveryParty)
    {
        !empty($deliveryParty) ? $this->deliveryParty = $deliveryParty : trigger_error('delivery_party不能为空', E_USER_ERROR);
    }

    public function getDeliveryParty()
    {
        return $this->deliveryParty;
    }

    public function setReceiverName($receiverName)
    {
        !empty($receiverName) ? $this->receiverName = $receiverName : trigger_error('receiver_name不能为空', E_USER_ERROR);
    }

    public function getReceiverName()
    {
        return $this->receiverName;
    }

    public function setReceiverPhone($receiverPhone)
    {
        !empty($receiverPhone) ? $this->receiverPhone = $receiverPhone : trigger_error('receiver_phone不能为空', E_USER_ERROR);
    }

    public function getReceiverPhone()
    {
        return $this->receiverPhone;
    }

    public function setDelivererAddress($delivererAddress)
    {
        !empty($delivererAddress) ? $this->delivererAddress = $delivererAddress : trigger_error('deliverer_address不能为空', E_USER_ERROR);
    }

    public function getDelivererAddress()
    {
        return $this->delivererAddress;
    }

    public function setCoordinateType($coordinateType)
    {
        !empty($coordinateType) ? $this->coordinateType = $coordinateType : trigger_error('coordinate_type不能为空', E_USER_ERROR);
    }

    public function getCoordinateType()
    {
        return $this->coordinateType;
    }

    public function setLongitude($longitude)
    {
        !empty($longitude) ? $this->longitude = $longitude : trigger_error('longitude不能为空', E_USER_ERROR);
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLatitude($latitude)
    {
        !empty($latitude) ? $this->latitude = $latitude : trigger_error('latitude不能为空', E_USER_ERROR);
    }

    public function getLatitude()
    {
        return $this->latitude;
    }
}