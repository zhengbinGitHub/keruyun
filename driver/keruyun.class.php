<?php
require_once PATH_ROOT . '/lib/desEncry.php';
require_once PATH_ROOT . '/lib/Config.php';
require_once PATH_ROOT . '/lib/DataRequestClient.php';

/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/3
 * Time: 下午10:30
 */
class keruyunInit extends keruyun
{
    const KRY_CACHE_PREFIX = 'keruyun';

    const TOKEN_PREFIX = '-token-';
    const DISH_CATEGORY = '/open/v1/cater/dish/category';
    const DISH_MENU = '/open/v1/cater/dish/dishMenu';
    //外卖下单
    const CREATE_ORDER = '/open/v1/takeout/order/create';

    //订单状态查询
    const QUERY_ORDER = '/open/v1/takeout/order/status/get';

    //合作方取消订单
    const CANCEL_ORDER = '/open/v1/takeout/order/cancel';

    //获取token
    const STORE_TOKEN = '/open/v1/token/get';

    //推送配送状态
    const DELIVERY_STATUS = '/open/v1/takeout/order/delivery/status/push';

    //第三方合作方调用此接口，退款成功时通知客如云取消订单
    const ORDER_REFUND_CALLBACK = '/open/v1/takeout/order/refundCallback';

    //合作方申请退款
    const ORDER_APPLY_REFUND = '/open/v1/takeout/order/applyRefund';

    //(下行接口)查询订单详情
    const ORDER_EXPORT_DETAIL = '/open/v1/data/order/exportDetail';
    //菜品精确查询
    const DISH_MENU_BYIDS = '/open/v1/cater/dish/dishMenuByIds';
    //快递
    const EXPRESS_TYPE_RIDER = 1;//骑手配送

    const EXPRESS_TYPE_SELF = 2;//自己配送

    const EXPRESS_TYPE_PICKUP = 3; // 自提

    /**
     * 餐盒费
     * @var int
     */
    private $packageFee = 0;

    /**
     * 订单状态
     * @var array
     */
    private $orderStatus = [
        1 => '未处理',
        2 => '已确认',
        3 => '已完成(在线付款时: 商家接单就会流转到此状态)',
        4 => '已取消'
    ];

    /**
     * 配送状态
     * @var array
     */
    private $deliveryStatus = [
        1 => '待接单',
        2 => '待取货',
        3 => '配送中',
        4 => '已完成',
        5 => '已取消'
    ];

    /**
     * 自取状态
     * @var array
     */
    private $callDishStatus = [
        0 => '未取餐',
        1 => '已取餐'
    ];

    /**
     * 优惠
     * @var int
     */
    private $discountFee = 0;

    public static $_error;

    private $token;

    private $shopIdenty;
    private $appKey;
    private $appSecret;
    private $env;
    private $data;

    /**
     * 经纬度服务商
     */
    const MAP_SERVICE = 'qqmap';

    //优惠类型
    const DISCOUNT_TYPE = 3;//优惠类型 1:客如云优惠券(目前仅支持代金券) 2:平台优惠 3:商家优惠 　

    public function __construct()
    {
        // echo '－－－－－－客如云开始工作－－－－－－';
        return $this;
    }

    public function init($params)
    {
        $this->config = $params['config'];
        $this->setChannel();
        $this->setShop();
        $this->data = $params;
        return $this;
    }

    public function getResponse($content)
    {

    }

    /**
     * 创建订单
     * @param unknown $data
     * {
     * "result": {
     * "orderId": "b2dee711926e42a0be8aa1b2d9783534",
     * "tradeId": 234821460227816448,
     * "serialNumber": "12"
     * },
     * "code": 0,
     * "message": "成功[OK]",
     * "messageUuid": "ca55de3c23ee447b890597a71aca5ec4",
     * "apiMessage": null
     * }
     */
    /**
     * @return array
     */
    public function createOrder()
    {
        $order = $this->data['vendor']['order'];
        if (!$order['mobile']) {
            InitPHP::log('keruyun__addOrder_mobile_error:' . json_encode($order, JSON_BIGINT_AS_STRING), 'ERROR');
            return $this->returnMsg(0, '客户手机号不存在或格式错误');
        }
        if (!$order['address']) {
            InitPHP::log('keruyun__addOrder_address_error:' . json_encode($order, JSON_BIGINT_AS_STRING), 'ERROR');
            return $this->returnMsg(0, '客户地址不能为空');
        }
        try {
            //*********************1.配置项*************************
            $config = $this->_getConfig();
            $extinfos = unserialize($order['extinfo']);
            array_map(function ($v) {
                return $this->packageFee += $this->pricecalc($v['value']);
            }, $extinfos['price']);

            array_map(function ($v) {
                return $this->discountFee += $this->pricecalc($v['fee']);
            }, $extinfos['discount']);

            require_once PATH_ROOT . '/lib/AddOrderModel.php';
            $orderModel = new AddOrderModel();
            $orderModel->setTpOrderId($order['order_sn']);
            $orderModel->setCreateTime($order['create_time']);
            $orderModel->setPeopleCount($order['people_count'] ?? 1);
            $orderModel->setShop($this->_getShopInfo($config));

            $orderModel->setProducts($this->_getProducts($this->data['order']));
            $orderModel->setDelivery($this->_getDelivery($order));
            $orderModel->setPayment($this->_getPayment($order));
            $orderModel->setRemark($order['buyer_message']);
            $orderModel->setNeedInvoice($order['is_invoice']);
            $orderModel->setInvoiceTitle($order['invoice']);
            $orderModel->setStatus(1);//订单状态, 已接受订单会直接触发云打印，1：待接单，2：已接单，默认1
            $orderModel->setIsPrint($this->config['is_print'] ?? 1);

            $orderModel->setPrintTemplateTypes(isset($this->config['print_templates_type'])
            && is_array($this->config['print_templates_type'])
                ? $this->config['print_templates_type']
                : (isset($this->config['is_print']) && $this->config['is_print'] == 1 ? [9] : []));
            $orderModel->setDiscountDetails($this->_getDiscountDetails($extinfos['discount']));

            $response = (new DataRequestClient(
                $config,
                $orderModel,
                self::CREATE_ORDER))->makeRequest();

            if ($response->getCode() != 0) {
                return $this->returnMsg(0, $response->getMsg(), $response->getResult());
            }

            $result = $response->getResult();

            $callbackDatas['vendor_order_sn'] = $result['orderId'];
            $callbackDatas['vendor_trade_id'] = $result['tradeId'];
            $callbackDatas['vendor_serial_number'] = $result['serialNumber'];
            $callbackDatas['content'] = [
                'connector_id' => $this->data['config']['channel']['id'],
                'driver' => 'keruyun',
                'order_sn' => $order['order_sn'],
                'orderId' => $result['orderId'],
                'tradeId' => $result['tradeId'],
                'serialNumber' => $result['serialNumber']
            ];

            return $this->returnMsg(1, $response->getMsg(), $callbackDatas);
        } catch (Exception $e) {
            return $this->returnMsg(0, $e->getMessage());
        }
    }

    /**
     * 合作方取消订单
     * @param unknown $data
     * @return array
     */
    public function cancelOrder()
    {
        $data = $this->data;
        $connectorOrderSn = $data['vendor']['order']['order_detail']['connector_order_sn'];
        if (!$connectorOrderSn) {
            InitPHP::log(print_r($data, 1), 'keruyun__cancelOrder');
            return $this->returnMsg(0, '订单ID不存在');
        }
        try {
            //*********************1.配置项*************************
            $config = $this->_getConfig();
            require_once PATH_ROOT . '/lib/CommonOrderModel.php';
            $orderModel = new CommonOrderModel();
            $orderModel->setOrderId($connectorOrderSn);
            $orderModel->setReason($data['reason'] ?? '我不想要了');

            $response = (new DataRequestClient(
                $config,
                $orderModel,
                self::CANCEL_ORDER))->makeRequest();
            //已处理申请导致提示内部接口异常 TODO
            if ($response->getCode() == '3001') {
                return $this->returnMsg(1, '取消成功');
            }
            if ($response->getCode() != 0) {
                return $this->returnMsg(0, $response->getMsg() ?? '取消失败');
            }
            return $this->returnMsg(1, '取消成功');
        } catch (Exception $e) {
            return $this->returnMsg(0, $e->getMessage());
        }
    }

    public function sendSms($data)
    {

    }

    public function parse($data, $type, $item)
    {

    }

    public function userInfoParse($id)
    {

    }

    /**
     * TODO
     * @param $data
     * @return bool
     */
    public function checkSign($data)
    {
        return false;
    }

    /**
     * 门店ID
     * @return mixed
     */
    private function _getShopIdenty()
    {
        return $this->shopIdenty;
    }

    /**
     * app_key
     * @return mixed
     */
    private function _getAppkey()
    {
        return $this->appKey;
    }

    /**
     * app_secret
     * @return mixed
     */
    private function _getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * 环境
     * @return mixed
     */
    private function _getEnv()
    {
        return $this->env;
    }

    /**
     * 订单状态查询
     * @param $data
     * @return array
     * {
     * "result": null,
     * "code": 3001,
     * "message": "获取订单状态失败",
     * "messageUuid": "a523a8b785ec4205802803731d479291",
     * "apiMessage": "内部接口异常[获取订单状态失败]"
     * }
     * 只能查询2天内的外卖订单状态
     */
    public function getOrderStatus($connectorOrderSn = '')
    {
        $data = $this->data;
        $connectorOrderSn = $connectorOrderSn ?: $data['vendor']['order']['order_detail']['connector_order_sn'];
        if (!$connectorOrderSn) {
            InitPHP::log(print_r($data, 1), 'keruyun__queryOrder');
            return $this->returnMsg(0, '订单ID不存在');
        }
        try {
            //*********************1.配置项*************************
            $config = $this->_getConfig();
            require_once PATH_ROOT . '/lib/CommonOrderModel.php';
            $orderModel = new CommonOrderModel();
            $orderModel->setOrderId($connectorOrderSn);
            $result = (new DataRequestClient(
                $config,
                $orderModel,
                self::QUERY_ORDER))->makeRequest();
            if ($result->getCode() != 0) {
                return $this->returnMsg(0, $result->getMsg() ?? '查询失败');
            }

            $callbackDatas['vendor_order_sn'] = $connectorOrderSn;
            $callbackDatas['content'] = array(
                'message_uuid' => $result->getMessageUuid() ?? '',
                'order_sn' => $data['order_sn'],
                'status_code' => $result->getResult()['status'],
                'status' => $this->orderStatus[$result->getResult()['status']],//订单状态
                'delivery_status_code' => $result->getResult()['deliveryStatus'],
                'delivery_status' => $this->deliveryStatus[$result->getResult()['deliveryStatus']],
                'call_dish_status_code' => $result->getResult()['callDishStatus'] ?? 0,
                'call_dish_status' => $this->callDishStatus[$result->getResult()['callDishStatus'] ?? 0],//自取状态
            );

            return $this->returnMsg(1, '查询成功', $callbackDatas);
        } catch (Exception $e) {
            return $this->returnMsg(0, $e->getMessage());
        }
    }

    /**
     * 合作方退款成功通知
     * @param $data
     * @return array
     * {
     * "result": null,
     * "code": 1000,
     * "message": "http method error: Request method 'GET' not supported",
     * "messageUuid": "1282b5f771564d0083167d1f2b89962d",
     * "apiMessage": null
     * }
     * 第三方合作方调用此接口，退款成功时通知客如云取消订单
     */
    public function refundCallback()
    {
        $data = $this->data;
        if (!$data['vendor']['order']['order_detail']['connector_order_sn']) {
            return $this->returnMsg(0, $data['vendor']['order']['order_sn'] . ' 订单ID不存在');
        }
        try {
            //*********************1.配置项*************************
            $config = $this->_getConfig();
            require_once PATH_ROOT . '/lib/CommonOrderModel.php';
            $orderModel = new CommonOrderModel();
            $orderModel->setOrderId($data['vendor']['order']['order_detail']['connector_order_sn']);

            $result = (new DataRequestClient(
                $config,
                $orderModel,
                self::ORDER_REFUND_CALLBACK))->makeRequest();
            if ($result->getCode() != 0) {
                return $this->returnMsg(0, $result->getMsg() ?? '退款失败');
            }
            return $this->returnMsg(1, '退款成功', ['vendor_order_sn' => $data['vendor']['order']['order_detail']['connector_order_sn']]);
        } catch (Exception $e) {
            return $this->returnMsg(0, $e->getMessage());
        }
    }

    /**
     * 合作方申请退款
     * @param $data
     * @return array
     */
    public function applyRefund()
    {
        $data = $this->data;
        try {
            $apiOrderSn = $data['vendor']['order']['order_detail']['connector_order_sn'];
            if (!$apiOrderSn) {
                return $this->returnMsg(0, '第三方订单号不能为空');
            }
            //*********************1.配置项*************************
            $config = $this->_getConfig();
            require_once PATH_ROOT . '/lib/CommonOrderModel.php';
            $orderModel = new CommonOrderModel();
            $orderModel->setOrderId($apiOrderSn);
            $orderModel->setReason($data['reason'] ?? '我想退款');

            $result = (new DataRequestClient(
                $config,
                $orderModel,
                self::ORDER_APPLY_REFUND))->makeRequest();
            if ($result->getCode() != 0) {
                return $this->returnMsg(0, $result->getMsg() ?? '申请退款失败');
            }
            return $this->returnMsg(1, '申请退款成功');
        } catch (Exception $e) {
            return $this->returnMsg(0, $e->getMessage());
        }
    }

    /**
     * 设置渠道用户信息
     * @param unknown $channel
     */
    private function setChannel()
    {
        $options = json_decode($this->config['channel']['option'], true);
        $this->appKey = $options['app_key'];
        $this->appSecret = $options['app_secret'];
    }

    /**
     * 门店信息
     */
    private function setShop()
    {
        $optionStr = isset($this->config['user']['option']) ? $this->config['user']['option'] : $this->config['user'];
        $options = unserialize($optionStr);
        //print_r($this->config);
        $this->shopIdenty = $options['shop_identy'];
        $this->env = $options['env'];
    }

    /**
     * @return Config
     */
    private function _getConfig()
    {
        if (!$this->_getShopIdenty()) {
            throw new Exception('门店商户ID为空');
        }
        try {
            $this->setStoreToken();
            $token = $this->getStoreToken();
            if (!$token) {
                throw new Exception('获取门店授权TOKEN失败');
            }
            return new Config(
                $this->_getShopIdenty(),
                $this->_getAppkey(),
                $this->_getAppSecret(),
                $token,
                $this->_getEnv()
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function _getShopInfo($config)
    {
        try {
            require_once PATH_ROOT . '/lib/ShopModel.php';
            $shop = new ShopModel();
            $shop->setShopIdenty($config->getShopIdenty());
            $shop->setShopName($this->data['vendor']['outlet']['name'] ?? '码到云门店(' . $config->getShopIdenty() . ')');
            $shop->setTpShopId($this->data['vendor']['outlet']['id'] ?? $config->getShopIdenty());
            return array(
                'shopIdenty' => $shop->getShopIdenty(),
                'tpShopId' => $shop->getTpShopId(),
                'shopName' => $shop->getShopName()
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 商品信息
     * @param array $data
     * @throws Exception
     */
    private function _getProducts(array $data)
    {
        try {
            require_once PATH_ROOT . '/lib/TakeoutProductModel.php';
            $product = new TakeoutProductModel();
            $goodsTotal = 0;
            $details = array();
            foreach ($data['order_detail'] as $key => $val) {
                $goodsTotal += $val['goods_total'];
                $product->setName($val['title']);
                $product->setType(0);
                $product->setTpId($val['goods_id']);
                $product->setQuantity($val['goods_total']);
                $product->setPrice($this->pricecalc($val['real_price']));
                $product->setTotalFee($this->pricecalc($val['price_total']));
                $product->setUuid($val['item_sn']);
                if($val['goods']['connector_product_id'])$product->setId($val['goods']['sn']);
                if ($val['item_name']) {//ProductProperty
                    $product->setProperties(array(array(
                        'name' => $val['item_name'],//菜品属性名称
                        'type' => 4,//属性类型1：做法 2：标签 3：备注 4：规格
                    )));
                }

                $details[$key] = array(
                    'name' => $product->getName(),
                    'id' => $product->getId(),
                    'type' => $product->getType(),
                    'tpId' => $product->getTpId(),
                    'quantity' => $product->getQuantity(),
                    'price' => $product->getPrice(),
                    'uuid' => $product->getUuid(),
                    'packagePrice' => $key == 0 ? $this->packageFee : 0,
                    'packageQuantity' => $key == 0 ? 1 : 0,
                    'totalFee' => $product->getTotalFee()
                );
                if ($val['item_name']) {
                    $details[$key]['properties'] = $product->getProperties();
                }
            }
            if ($data['goods_total'] != $goodsTotal) {
                throw new Exception('对接商品信息数据与购买数量不符合');
            }
            return $details;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 配送信息
     * @param array $data
     * @throws Exception
     */
    private function _getDelivery(array $data)
    {
        try {
            require_once PATH_ROOT . '/lib/DeliveryModel.php';
            $delivery = new DeliveryModel();

            $delivery->setExpectTime($data['express_type'] == self::EXPRESS_TYPE_PICKUP
                ? $data['appointment_time']
                : 0);
            //客如云配送方式,1:商家自配送,2:平台配送,3:自提
            $delivery->setDeliveryParty($data['express_type'] == self::EXPRESS_TYPE_PICKUP ? 3 : 1);

            $delivery->setReceiverName($data['username']);
            $delivery->setReceiverPhone($data['mobile']);

            $params = array(
                'expectTime' => $delivery->getExpectTime(),
                'deliveryParty' => $delivery->getDeliveryParty(),
                'receiverName' => $delivery->getReceiverName(),
                'receiverPhone' => $delivery->getReceiverPhone()
            );

            if ($data['express_type'] == self::EXPRESS_TYPE_RIDER) {
                if (!$data['longitude'] || !$data['latitude']) {
                    $location = $this->_getAddressLocation($data['address']);
                    if (!$location) {
                        throw new Exception('商家自配送:高德地图获取客户地址经纬度为空');
                    }
                    $data['longitude'] = $location['longitude'];
                    $data['latitude'] = $location['latitude'];
                }
                $delivery->setDelivererAddress($data['address']);
                $delivery->setCoordinateType(2);
                $delivery->setLatitude($data['latitude']);
                $delivery->setLongitude($data['longitude']);

                $params['delivererAddress'] = $delivery->getDelivererAddress();
                $params['coordinateType'] = $delivery->getCoordinateType();
                $params['longitude'] = $delivery->getLongitude();
                $params['latitude'] = $delivery->getLatitude();
            }
            return $params;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param array $data
     * @throws Exception
     */
    private function _getPayment(array $data)
    {
        try {
            require_once PATH_ROOT . '/lib/PaymentModel.php';
            $payment = new PaymentModel();

            $totalFee = $this->pricecalc($data['payable_price']) + $this->pricecalc($data['express_fee']) + $this->packageFee;
            //分 注:若优惠金额>订单总价,该字段请传0
            $priceTotal = ($this->discountFee > $totalFee) ? 0 : $totalFee - $this->discountFee;

            $payment->setTotalFee((int)$totalFee);//订单总价=商品总金额+餐盒费+配送费，单位：分
            $payment->setDeliveryFee($this->pricecalc($data['express_fee']));//配送费
            $payment->setPackageFee((int)$this->packageFee);//餐盒费
            $payment->setDiscountFee((int)$this->discountFee);//优惠总金额=平台优惠金额+商户优惠金额
            $payment->setPlatformDiscountFee(0);//平台优惠总金额
            $payment->setShopDiscountFee((int)$this->discountFee);//商家优惠总金额=商家优惠+客如云优惠券总额
            $payment->setShopFee((int)$priceTotal);//商户实收总价
            $payment->setUserFee((int)$priceTotal);//用户实付总价
            $payment->setServiceFee(0);
            $payment->setSubsidies(0);
            $payment->setPayType(2);//支付方式 1:线下支付/货到付款 2:在线支付 3:会员卡余额 4:优惠券

            return array(
                'totalFee' => $payment->getTotalFee(),
                'deliveryFee' => $payment->getDeliveryFee(),
                'packageFee' => $payment->getPackageFee(),
                'discountFee' => $payment->getDiscountFee(),
                'platformDiscountFee' => $payment->getPlatformDiscountFee(),
                'shopDiscountFee' => $payment->getShopDiscountFee(),
                'shopFee' => $payment->getShopFee(),
                'userFee' => $payment->getUserFee(),
                'serviceFee' => $payment->getServiceFee(),
                'subsidies' => $payment->getSubsidies(),
                'payType' => $payment->getPayType()
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 配送状态
     * @param $key
     * @return mixed|string
     */
    public function getDeliveryStatus()
    {
        return $this->deliveryStatus;
    }

    /**
     * 合作方获取门店授权对应的token
     * @param $shopIdenty
     */
    public function setStoreToken()
    {
        $shopIdenty = $this->_getShopIdenty();
        $key = self::KRY_CACHE_PREFIX . self::TOKEN_PREFIX . $shopIdenty;
        if ($token = $this->getRedis()->get($key)) {
            $this->token = $token;
        } else {
            $result = (new DataRequestClient(
                new Config(
                    $shopIdenty,
                    $this->_getAppkey(),
                    $this->_getAppSecret(),
                    '',
                    $this->_getEnv()
                ),
                '',
                self::STORE_TOKEN))->makeRequest(true);
            if ($result->getCode() != 0) {
                throw new Exception($result->getMsg() ?? '获取TOKEN失败');
            }
            $this->token = $result->getResult()['token'];
            $this->getRedis()->set($key, $this->token);
        }
    }

    public function getStoreToken()
    {
        return $this->token;
    }

    /**
     * 合作方推送配送状态
     * @param array $datas
     * @return array
     */
    public function pushDeliveryStatus()
    {
        try {
            $data = $this->data;
            //*********************1.配置项*************************
            $connectorOrderSn = $data['vendor']['order_detail']['connector_order_sn'];
            if (!$connectorOrderSn) {
                return $this->returnMsg(0, '第三方订单号不能为空');
            }
            $config = $this->_getConfig();
            require_once PATH_ROOT . '/lib/CommonOrderModel.php';
            $orderModel = new CommonOrderModel();
            $orderModel->setOrderId($connectorOrderSn);
            $orderModel->setDeliveryStatus($data['delivery_status']);
            $orderModel->setTime($_SERVER['REQUEST_TIME']);

            $result = (new DataRequestClient(
                $config,
                $orderModel,
                self::DELIVERY_STATUS))->makeRequest();
            if ($result->getCode() != 0) {
                return $this->returnMsg(0, $result->getMsg() ?? '推送配送状态失败');
            }
            return $this->returnMsg(1, '推送配送状态成功');
        } catch (Exception $e) {
            return array(0, $e->getMessage());
        }
    }

    /**
     * @param string $address
     * @return array|bool
     */
    private function _getAddressLocation($address = '')
    {
        $result = $this->_getMap($address);
        if (!$result) {
            return false;
        }
        $params = [];
        //经度，纬度
        $params['longitude'] = $result['lng'];
        $params['latitude'] = $result['lat'];
        $params['city_name'] = $result['city_name'];
        $params['area_name'] = $result['area_name'];
        return $params;
    }

    /**
     * 地址经纬度
     * @param string $address
     */
    private function _getMap($address = '')
    {
        try {
            InitPHP::import('library/plugin/Map/Map.init.php');
            $result = Map::init(self::MAP_SERVICE)->geocode($address);
            if (!$result) {
                return false;
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 优惠明细
     */
    private function _getDiscountDetails(array $discounts)
    {
        $params = [];
        array_walk($discounts, function ($item) use (&$params) {
            $fee = $this->pricecalc($item['fee']);
            if ($fee > 0) {
                $params[] = [
                    'discountType' => self::DISCOUNT_TYPE,
                    'discountFee' => $fee,
                    'description' => $item['name'],
                ];
            }
        });
        return $params;
    }

    /**
     * 订单详情
     * @return array
     */
    public function getOrderDetail()
    {
        $data = $this->data;
        try {
            $tradeIds = $data['trade_ids'];
            if (!$tradeIds || !is_array($tradeIds)) {
                return $this->returnMsg(0, '订单号不能为空或格式错误');
            }
            //*********************1.配置项*************************
            $config = $this->_getConfig();
            require_once PATH_ROOT . '/lib/CommonOrderModel.php';
            $orderModel = new CommonOrderModel();
            $orderModel->setTradeId($tradeIds);
            $orderModel->setShopIdenty($config->shop_identy);
            $result = (new DataRequestClient(
                $config,
                $orderModel,
                self::ORDER_EXPORT_DETAIL))->makeRequest();
            if ($result->getCode() != 0) {
                return $this->returnMsg(0, $result->getMsg());
            }

            $baseInfos = $result['BaseInfo'];

            return $this->returnMsg(1, 'ok', [
                'order_sn' => $baseInfos['tradeNo'],
                'trade_id' => $baseInfos['id'],
                'deliver_type' => $baseInfos['deliverType'],
                'trade_pay_status' => $baseInfos['tradePayStatus'],
                'trade_status' => $baseInfos['tradeStatus'],
                'shop_id' => $baseInfos['shopId'],
                'shop_name' => $baseInfos['shopName'],
            ]);
        } catch (Exception $e) {
            return $this->returnMsg(0, $e->getMessage());
        }
    }

    /**
     * 解决0.58*100 = 57.999999999
     * @param $price
     * @return int
     */
    private function pricecalc($price)
    {
        return (int)(($price * 1000) / 10);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCategory()
    {
        echo 'getCategory';
        $config = $this->_getConfig();
        require_once PATH_ROOT . '/lib/CategoryModel.php';
        $model = new CategoryModel();
        $model->setShopIdenty($config->getShopIdenty());
        $result = (new DataRequestClient(
            $config,
            $model,
            self::DISH_CATEGORY))->makeRequest();
        //print_r($result);
        if ($result->getCode() != 0) {
            return $this->returnMsg(0, $result->getMsg() ?? 'p');
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getProduct()
    {
        //echo 'dishMenu';
        $config = $this->_getConfig();
        require_once PATH_ROOT . '/lib/ProductModel.php';
        $model = new ProductModel();
        $model->setShopIdenty($config->getShopIdenty());
        $result = (new DataRequestClient(
            $config,
            $model,
            self::DISH_MENU))->makeRequest();
        //print_r($result);
        if ($result->getCode() != 0) {
            return $this->returnMsg(0, $result->getMsg() ?? 'p');
        }
        $res = $result->getResult()['dishTOList'];
        $data = [];
        foreach ($res as $k => $v) {
            $data[$k]['name'] = $v['name'];
            $data[$k]['id'] = $v['id'];
            $data[$k]['image'] = $v['imgUrl'];
            $data[$k]['brief'] = $v['desc'];
            $data[$k]['price'] = $v['price'] / 100;
            $data[$k]['categorys'] = $this->_parseCategory($v['categorys']);
            $data[$k]['sku'] = $v['attrs'];
            $data[$k]['attribute'] = ['package_count'=>1,'package_price'=>1,'package_capacity'=>1];//在这里拼接，产品具体扩展参数，比如餐盒数量，餐盒价格，等等
            $data[$k]['start_buy'] = $v['minOrderNum'];//起卖份数
            $data[$k]['goods_number'] = $v['residueTotal'];//剩余可售数量
            $data[$k]['sale_total'] = $v['saleTotal'];//每日售卖限额
            $data[$k]['status'] = $v['clearStatus'] == 2 ? 3 : 1;
            $data[$k]['connector_status'] = $v['clearStatus'];
        }
        return $this->returnMsg(1, 'dishMenu成功', $data);
    }

    /**
     * @param $data
     * @return array
     */
    private function _parseCategory($data)
    {
        #array_column($v['categorys'], 'categoryName', 'name');
        $res = [];
        foreach ($data as $k => $v) {
            $res[$k]['id'] = $v['categoryId'];
            $res[$k]['name'] = $v['categoryName'];
        }
        return $res;
    }

    /**
     * 菜品精确查询
     * @return array
     * @throws Exception
     */
    public function dishMenuByIds(array $ids)
    {
        $config = $this->_getConfig();
        require_once PATH_ROOT . '/lib/ProductInfoModel.php';
        $model = new ProductInfoModel();
        $model->setShopIdenty($config->getShopIdenty());
        $model->setIds($ids);
        $result = (new DataRequestClient(
            $config,
            $model,
            self::DISH_MENU_BYIDS))->makeRequest();
        //print_r($result);
        if ($result->getCode() != 0) {
            return $this->returnMsg(0, $result->getMsg() ?? 'p');
        }
        $res = $result->getResult();
        $data = [];
        foreach ($res as $k => $v) {
            $data[$k]['name'] = $v['name'];
            $data[$k]['shop_identy'] = $v['shopIdenty'];
            $data[$k]['id'] = $v['id'];
            $data[$k]['image'] = $v['imgUrl'];
            $data[$k]['brief'] = $v['desc'];
            $data[$k]['price'] = $v['price'] / 100;
            $data[$k]['categorys'] = $this->_parseCategory($v['categorys']);
            $data[$k]['sku'] = $v['attrs'];
            $data[$k]['attribute'] = ['package_count'=>1,'package_price'=>1,'package_capacity'=>1];//在这里拼接，产品具体扩展参数，比如餐盒数量，餐盒价格，等等
            $data[$k]['start_buy'] = $v['minOrderNum'];//起卖份数
            $data[$k]['goods_number'] = $v['residueTotal'];//剩余可售数量
            $data[$k]['sale_total'] = $v['saleTotal'];//每日售卖限额
            $data[$k]['status'] = $v['clearStatus'] == 2 ? 3 : 1;
            $data[$k]['connector_status'] = $v['clearStatus'];
        }
        //print_r($res);
        return $this->returnMsg(1, 'dishMenuByIds成功', $data);
    }
}