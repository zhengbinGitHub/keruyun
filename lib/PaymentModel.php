<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午8:44
 */
class PaymentModel
{
    /**
     * 订单总价，订单总价=商品总金额+餐盒费+配送费，单位：分
     * @var
     */
    public $totalFee = 0;

    /**
     * 配送费，单位：分
     * @var
     */
    public $deliveryFee;

    /**
     * 餐盒费，餐盒费=餐盒数量 * 餐盒单价， 单位：分
     * @var
     */
    public $packageFee;

    /**
     * 优惠总金额，优惠总金额=平台优惠金额+商户优惠金额，单位：分
     * @var
     */
    public $discountFee;

    /**
     * 平台优惠总金额，单位：分
     * @var
     */
    public $platformDiscountFee;

    /**
     * 传discountDetails时:商家优惠总金额=商家优惠+客如云优惠券总额，单位：分 不传discountDetails时: 商家优惠
     * @var
     */
    public $shopDiscountFee;

    /**
     * 商户实收总价
     * @var
     */
    public $shopFee;

    /**
     * 用户实付总价，用户实付=订单总价-优惠总金额，单位：分 注:若优惠金额>订单总价,该字段请传0
     * @var
     */
    public $userFee;

    /**
     * 服务费(商户向平台支付的佣金等)，单位：分
     * @var
     */
    public $serviceFee;

    /**
     * 平台补贴(第三方平台每单给与商家的补贴)，单位：分
     * @var
     */
    public $subsidies;

    /**
     * 支付方式 1:线下支付/货到付款 2:在线支付 3:会员卡余额 4:优惠券
     * @var
     */
    public $payType;

    public function setTotalFee(int $totalFee)
    {
        !empty($totalFee) ? $this->totalFee = $totalFee : trigger_error('total_fee不能为空', E_USER_ERROR);
    }

    public function getTotalFee()
    {
        return $this->totalFee;
    }

    public function setDeliveryFee(int $deliveryFee)
    {
        !is_null($deliveryFee) ? $this->deliveryFee = $deliveryFee : trigger_error('delivery_fee不能为空', E_USER_ERROR);
    }

    public function getDeliveryFee()
    {
        return $this->deliveryFee;
    }

    public function setPackageFee(int $packageFee)
    {
        !is_null($packageFee) ? $this->packageFee = $packageFee : trigger_error('package_fee不能为空', E_USER_ERROR);
    }

    public function getPackageFee()
    {
        return $this->packageFee;
    }

    public function setDiscountFee(int $discountFee)
    {
        !is_null($discountFee) ? $this->discountFee = $discountFee : trigger_error('discount_fee不能为空', E_USER_ERROR);
    }

    public function getDiscountFee()
    {
        return $this->discountFee;
    }

    public function setPlatformDiscountFee(int $platformDiscountFee)
    {
        !is_null($platformDiscountFee) ? $this->platformDiscountFee = $platformDiscountFee : trigger_error('platform_discount_fee不能为空', E_USER_ERROR);
    }

    public function getPlatformDiscountFee()
    {
        return $this->platformDiscountFee;
    }

    public function setShopDiscountFee(int $shopDiscountFee)
    {
        !is_null($shopDiscountFee) ? $this->shopDiscountFee = $shopDiscountFee : trigger_error('shop_discount_fee不能为空', E_USER_ERROR);
    }

    public function getShopDiscountFee()
    {
        return $this->shopDiscountFee;
    }

    public function setShopFee(int $shopFee)
    {
        !is_null($shopFee) ? $this->shopFee = $shopFee : trigger_error('shop_fee不能为空', E_USER_ERROR);
    }

    public function getShopFee()
    {
        return $this->shopFee;
    }

    public function setUserFee(int $userFee)
    {
        !is_null($userFee) ? $this->userFee = $userFee : trigger_error('user_fee不能为空', E_USER_ERROR);
    }

    public function getUserFee()
    {
        return $this->userFee;
    }

    public function setServiceFee($serviceFee)
    {
        !is_null($serviceFee) ? $this->serviceFee = $serviceFee : trigger_error('service_fee不能为空', E_USER_ERROR);
    }

    public function getServiceFee()
    {
        return $this->serviceFee;
    }

    public function setSubsidies($subsidies)
    {
        !is_null($subsidies) ? $this->subsidies = $subsidies : trigger_error('subsidies不能为空', E_USER_ERROR);
    }

    public function getSubsidies()
    {
        return $this->subsidies;
    }

    public function setPayType($payType)
    {
        !empty($payType) ? $this->payType = $payType : trigger_error('pay_type不能为空', E_USER_ERROR);
    }

    public function getPayType()
    {
        return $this->payType;
    }
}