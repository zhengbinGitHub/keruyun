<?php
require_once PATH_ROOT . '/lib/BaseModel.php';
/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午12:24
 */
class AddOrderModel implements BaseModel
{
    /**
     * 第三方订单号
     * @var
     */
    public $tpOrderId;

    public $needInvoice;

    public $invoiceTitle;

    public $taxpayerId;

    /**
     * 订单创建时间
     * @var
     */
    public $createTime;

    public $status;

    public $remark;

    /**
     * 就餐人数
     * @var
     */
    public $peopleCount;

    /**
     * 店铺信息
     * @var
     */
    public $shop;

    /**
     * 商品信息
     * @var
     */
    public $products;

    /**
     * 配送信息
     * @var
     */
    public $delivery;

    /**
     * 支付信息
     * @var
     */
    public $payment;

    public $customers;

    public $discountDetails;

    public $isPrint;

    public $printTemplateTypes;

    public function setTpOrderId($tpOrderId)
    {
        !empty($tpOrderId) ? $this->tpOrderId = $tpOrderId : trigger_error('tp_order_id不能为空', E_USER_ERROR);
    }

    public function getTpOrderId()
    {
        return $this->tpOrderId;
    }

    public function setCreateTime($createTime)
    {
        !empty($createTime) ? $this->createTime = $createTime : trigger_error('create_cime不能为空', E_USER_ERROR);
    }

    public function getCreateTime()
    {
        return $this->createTime;
    }

    public function setPeopleCount($peopleCount)
    {
        !empty($peopleCount) ? $this->peopleCount = $peopleCount : trigger_error('people_count不能为空', E_USER_ERROR);
    }

    public function getPeopleCount()
    {
        return $this->peopleCount;
    }

    public function setShop($shop)
    {
        isset($shop) ? $this->shop = $shop : trigger_error('shop不能为空', E_USER_ERROR);
    }

    public function getShop()
    {
        return $this->shop;
    }

    public function setProducts($products)
    {
        isset($products) ? $this->products = $products : trigger_error('products不能为空', E_USER_ERROR);
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function setDelivery($delivery)
    {
        !empty($delivery) ? $this->delivery = $delivery : trigger_error('delivery不能为空', E_USER_ERROR);
    }

    public function getDelivery()
    {
        return $this->delivery;
    }

    public function setPayment($payment)
    {
        !empty($payment) ? $this->payment = $payment : trigger_error('payment不能为空', E_USER_ERROR);
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setNeedInvoice($needInvoice)
    {
        isset($needInvoice) ? $this->needInvoice = $needInvoice : trigger_error('need_invoice不能为空', E_USER_ERROR);
    }

    public function getNeedInvoice()
    {
        return $this->needInvoice;
    }

    public function setInvoiceTitle($invoiceTitle)
    {
        isset($invoiceTitle) ? $this->invoiceTitle = $invoiceTitle : trigger_error('invoice_title不能为空', E_USER_ERROR);
    }

    public function getInvoiceTitle()
    {
        return $this->invoiceTitle;
    }

    public function setTaxpayerId($taxpayerId)
    {
        !empty($taxpayerId) ? $this->taxpayerId = $taxpayerId : trigger_error('taxpayer_id不能为空', E_USER_ERROR);
    }

    public function getTaxpayerId()
    {
        return $this->taxpayerId;
    }

    public function setStatus($status)
    {
        !empty($status) ? $this->status = $status : trigger_error('status不能为空', E_USER_ERROR);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setRemark($remark)
    {
        $this->remark = $remark ? $remark : '';
    }

    public function getRemark()
    {
        return $this->remark;
    }

    public function setCustomers($customers)
    {
        $this->customers = $customers ? $customers : '';
    }

    public function getCustomers()
    {
        return $this->customers;
    }

    public function setDiscountDetails($discountDetails)
    {
        $this->discountDetails = $discountDetails ? $discountDetails : '';
    }

    public function getDiscountDetails()
    {
        return $this->discountDetails;
    }

    public function setIsPrint($isPrint = null)
    {
        $this->isPrint = is_null($isPrint) ? null : $isPrint;
    }

    public function getIsPrint()
    {
        return $this->isPrint;
    }

    public function setPrintTemplateTypes(array $printTemplateTypes)
    {
        $this->printTemplateTypes = $printTemplateTypes ? $printTemplateTypes : [];
    }

    public function getPrintTemplateTypes()
    {
        return $this->printTemplateTypes;
    }

    public function getBusinessParams()
    {
        // TODO: Implement getBusinessParams() method.
        $params = [
            "tpOrderId"     =>(string)$this->getTpOrderId(),
            "needInvoice"   =>$this->getNeedInvoice(),
            "invoiceTitle"  =>$this->getInvoiceTitle(),
            "createTime"    =>$this->getCreateTime(),
            "peopleCount"   =>$this->getPeopleCount(),
            "remark"        =>$this->getRemark(),
            "shop"          =>$this->getShop(),
            "products"      =>$this->getProducts(),
            "delivery"      =>$this->getDelivery(),
            "payment"       =>$this->getPayment(),
            "status"        =>$this->getStatus()
        ];
        //1打印，0不打印，为空默认打印类型为9：打印消费清单
        if($this->getIsPrint() == 1 || is_null($this->isPrint)){//8：后厨单，9：消费清单
            $params['isPrint']            = $this->getIsPrint();
            $params['printTemplateTypes'] = is_null($this->isPrint) ? [9] : $this->getPrintTemplateTypes();
        }
        if($this->discountDetails){
            $params['discountDetails'] = $this->getDiscountDetails();
        }
        return $params;
    }
}