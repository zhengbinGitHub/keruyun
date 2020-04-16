<?php

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午1:00
 */
class TakeoutProductModel
{
    /**
     * 商品名称
     * @var
     */
    public $name;

    public $id;

    public $parentUuid;

    /**
     * 菜品类型 : 菜品种类 0:单菜 1:套餐 2:加料。不填默认唯0，套餐子菜请填0
     * @var
     */
    public $type;

    /**
     * 合作方商品ID
     * @var
     */
    public $tpId;

    /**
     * 份数
     * @var
     */
    public $quantity;

    public $unit;

    /**
     * 商品单价，单位：分
     * @var
     */
    public $price;

    /**
     * 餐盒单价，单位：分
     * @var
     */
    public $packagePrice;

    /**
     * 餐盒数量
     * @var
     */
    public $packageQuantity;

    public $totalFee;

    public $properties;

    public $remark;

    public $uuid;

    public function setName($name)
    {
        !empty($name) ? $this->name = $name : trigger_error('name不能为空', E_USER_ERROR);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        !is_null($type) ? $this->type = $type : trigger_error('type不能为空', E_USER_ERROR);
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTpId($tpId)
    {
        !empty($tpId) ? $this->tpId = $tpId : trigger_error('tp_id不能为空', E_USER_ERROR);
    }

    public function getTpId()
    {
        return $this->tpId;
    }

    public function setQuantity($quantity)
    {
        !empty($quantity) ? $this->quantity = $quantity : trigger_error('quantity不能为空', E_USER_ERROR);
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setPrice($price)
    {
        !empty($price) ? $this->price = $price : trigger_error('price不能为空', E_USER_ERROR);
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPackagePrice($packagePrice)
    {
        !empty($packagePrice) ? $this->packagePrice = $packagePrice : trigger_error('package_price不能为空', E_USER_ERROR);
    }

    public function getPackagePrice()
    {
        return $this->packagePrice;
    }

    public function setPackageQuantity($packageQuantity)
    {
        !empty($packageQuantity) ? $this->packageQuantity = $packageQuantity : trigger_error('package_quantity不能为空', E_USER_ERROR);
    }

    public function getPackageQuantity()
    {
        return $this->packageQuantity;
    }

    public function setTotalFee($totalFee)
    {
        !empty($totalFee) ? $this->totalFee = $totalFee : trigger_error('total_fee不能为空', E_USER_ERROR);
    }

    public function getTotalFee()
    {
        return $this->totalFee;
    }

    public function setProperties($properties)
    {
        !empty($properties) ? $this->properties = $properties : [];
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function setParentUuid($parentUuid)
    {
        $this->parentUuid = $parentUuid;
    }

    public function getParentUuid()
    {
        return $this->parentUuid;
    }

    public function setUuid($uuId)
    {
        $this->uuid = $uuId;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}