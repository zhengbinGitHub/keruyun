<?php
if (!defined('IS_INITPHP')) exit('Access Denied!');
define("PATH_ROOT", dirname(__FILE__));

class keruyun extends BaseConnector
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var
     */
    private $driver;

    /**
     * @var
     */
    private $data;

    /**
     * @var array
     */
    private $maDaoOrderStatus = [];

    /**
     * @var array
     */
    private $callbackDatas = [];

    //申请退款返回状态
    const APPLAY_REFUND_STATUS = 13;

    //拒绝退款
    const REFUSE_REFUND_STATUS = 5;

    //关闭订单
    const CLOSE_STATUS = 8;

    const CACHE_PREFIX = 'madaoVendor:';

    /**
     * 获取Nosql对象
     * @param string $type
     */
    public function __construct()
    {
        require_once 'driver/keruyun.class.php';
    }

    public function init($param)
    {
        $this->data = $param;
        $this->driver = new keruyunInit();
        $this->driver->init($param);
    }

    /**
     *
     */
    public function callback()
    {
//        echo '我是客如云的回调，请告诉要干嘛。';
        $fun = $this->data['request']['model'];
        if (!$fun) return '';

        //订单状态推送 两个 ?? model解析
        // /notify/callback?model=order_status?shopIdenty=810488743&sign=71a487ac5e00608587bd9ac4c9001c18dbc4e94b5c0759408910c7b1a95e0d51&appKey=142cffba3ae26c26b5d081cf8ec6b9a7&version=1.0&timestamp=1586351444080
        if (strpos($fun, '?')) {
            $models = explode('?', $fun);
            $fun = $models[0];

            $params = explode('=', $models[1]);
            $this->data['request'][$params[0]] = $params[1];
        }
        $this->callbackDatas = json_decode($this->data['callback'], true);

        $this->setMaDaoOrderStatus();

        if ($this->checkSign() !== true) {
            return $this->returnMsg(0, '签名失败');
        }

        return $this->$fun();
    }

    /**
     * 码到订单状态字典
     */
    private function setMaDaoOrderStatus()
    {
        $this->maDaoOrderStatus = array_keys($this->data['vendor']['order']['order_status']);
    }

    /**
     * @param array|unknown $data
     * 合作方取消订单
     */
    /**
     * @return array
     */
    public function cancelOrder()
    {
        if (!$this->data['vendor']['order']['order_detail']['connector_order_sn']) {
            return $this->returnMsg(0, '第三方订单号不能为空');
        }
        $res = $this->driver->cancelOrder();
        if ($res['code'] == 1) {
            $res['vendor_order_status'] = 8888;//必须要有。跟订单状态，对应orderDao，影响客户退钱的。可退款的状态值3.
        }
        return $this->returnMsg($res['code'], $res['msg'], $res['data']);
    }

    public function parse($data, $type, $item)
    {
    }

    /**
     * @param unknown $data
     * 发送短信
     */
    public function sendSms($data)
    {
    }

    /**
     * @param unknown $content
     */
    public function getResponse($content)
    {

    }

    /**
     * 推送订单
     * @param $data
     */
    public function createOrder()
    {
        $original = $this->data['vendor']['order'] ?? array();
        if (!$original) {
            return $this->returnMsg(0, '订单详情不能为空');
        }
        $res = $this->driver->createOrder();
        if ($res['code'] == 1) {
            $this->_setOrderCache($res['data']);
            $res['data']['complete'] = 'success';
        }
        return $this->returnMsg($res['code'], $res['msg'], $res['data']);
    }

    /**
     * 订单详情
     * @return array
     */
    /**
     * @param string $connectorOrderSn
     * @return array
     */
    public function getOrderStatus($connectorOrderSn = '')
    {
        $connectorOrderSn = $connectorOrderSn ?: $this->data['vendor']['order']['order_detail']['connector_order_sn'];
        if (!$connectorOrderSn) {
            return $this->returnMsg(0, '第三方订单号不能为空');
        }
        $res = $this->driver->getOrderStatus($connectorOrderSn);
        return $this->returnMsg($res['code'], $res['msg'], $res['data']);
    }

    /**
     * TODO
     * @return array
     */
    public function getQrcode()
    {
        return $this->returnMsg(0, '暂无此功能');
    }

    /**
     * 合作方同意退款接口
     * @return array
     */
    public function refundCallback()
    {
        if (!$this->data['vendor']['order']['order_detail']['connector_order_sn']) {
            return $this->returnMsg(1, '未推送给第三方订单，同意退款');
        }
        $res = $this->driver->refundCallback();
        return $this->returnMsg($res['code'], $res['msg'], $res['data']);
    }

    /**
     * 合作申请退款接口
     * @return array
     */
    public function refundOrder()
    {
        if (!$this->data['vendor']['order']['order_detail']['connector_order_sn']) {
            return $this->returnMsg(1, '未推送给第三方订单，申请退款', [
                'vendor_order_status' => 2 //待退款状态
            ]);
        }
        //已申请退款
        if ($this->data['vendor']['order']['order_verify']['connector_order_status'] == self::APPLAY_REFUND_STATUS) {
            return $this->returnMsg(1, '申请退款成功', [
                'vendor_order_status' => self::APPLAY_REFUND_STATUS
            ]);
        }
        //商家拒绝退款
        if ($this->data['vendor']['order']['order_verify']['connector_order_status'] == self::REFUSE_REFUND_STATUS) {
            return $this->returnMsg(0, '商家拒绝退款', [
                'vendor_order_status' => self::REFUSE_REFUND_STATUS
            ]);
        }
        //商家关闭订单
        if ($this->data['vendor']['order']['order_verify']['connector_order_status'] == self::CLOSE_STATUS) {
            return $this->returnMsg(0, '商家已关闭订单', [
                'vendor_order_status' => self::CLOSE_STATUS
            ]);
        }
        $res = $this->driver->applyRefund();
        if ($res['code'] == 1) {//审核中
            //必须要有。申请退款--这个订单状态要提供过来，根据这个来判断是否可以退钱给客户，请看roderDao，里面的定妆状态。3的时候是可以退钱给客户的
            $res['data']['vendor_order_status'] = self::APPLAY_REFUND_STATUS;
            $res['data']['complete'] = 'success';
        }
        return $this->returnMsg($res['code'], $res['msg'], $res['data']);
    }

    /**
     * 只支持平台配送
     * 商家自配送/自取不支持修改状态
     * 推送配送信息
     * 订单状态(待接单＝1,待取货＝2,配送中＝3,已完成＝4,已取消＝5, 已过期＝7,指派单=8,妥投异常之物品返回中=9, 妥投异常之物品返回完成=10,骑士到店=100,创建达达运单失败=1000 可参考文末的状态说明）
     */
    public function pushDeliveryStatus()
    {
        if (!$this->data['vendor']['order']['order_detail']['connector_order_sn']) {
            return $this->returnMsg(0, '第三方订单号不能为空');
        }
        if (!isset($this->data['delivery_status'])) {
            return $this->returnMsg(0, '合作方推送配送状态不能为空');
        }
        if (!in_array($this->data['delivery_status'], range(1, 5))) {
            return $this->returnMsg(0, '超出推送配送状态范围区间');
        }
        $res = $this->driver->pushDeliveryStatus();
        return $this->returnMsg($res['code'], $res['msg'], $res['data']);
    }

    /**
     * 获取门店token
     * @return mixed
     */
    public function getOutletToken()
    {
        $this->driver->setStoreToken();
        return $this->driver->getStoreToken();
    }

    /**
     * 获取订单详情
     * @return array
     */
    public function getOrderDetail()
    {
        if (empty($this->data['trade_ids']) || !is_array($this->data['trade_ids'])) {
            return $this->returnMsg(0, '订单号不能为空或格式错误');
        }
        $res = $this->driver->getOrderDetail();
        return $this->returnMsg($res['code'], $res['msg'], $res['data']);
    }

    /**
     * 比对madao订单状态
     * @return mixed
     */
    public function confirmOrderStatus(int $orderStatus, array $maDaoOrderStatus)
    {
        switch ($orderStatus) {
            case 1:
            case 3:
                $status = $maDaoOrderStatus[0];
                break;
            case 21://反结账
            case 92://挂账
                $status = $maDaoOrderStatus[9];
                break;
            case 91://合单
            case 93://支付完成
                $status = $maDaoOrderStatus[1];
                break;
            case 19://退货
            case 20://退款
                $status = $maDaoOrderStatus[2];
                break;
            case 4:
                $status = $maDaoOrderStatus[1];
                break;
            case 7:
            case 14://拒绝订单
                $status = $maDaoOrderStatus[5];
                break;
            case 13://订单确认（接受订单)
                $status = $maDaoOrderStatus[6];
                break;
            case 26://订单完成
                $status = $maDaoOrderStatus[7];
                break;
            case 8:
            case 15://订单取消
            case 16://订单作废
                $status = $maDaoOrderStatus[8];
                break;
            default://不确定
                $status = $orderStatus;
                break;
        }
        return $status;
    }

    /**
     * @param $tradeId
     * @param array $options
     */
    private function _setOrderCache(array $options)
    {
        $key = self::CACHE_PREFIX . $options['vendor_trade_id'];
        if (!$this->getExists($key)) {
            $expireTime = mktime(23, 59, 59, date("m"), date("d"), date("Y")) - time();
            $this->getRedis()->set($key, json_encode($options), $expireTime);
        }
    }

    /**
     * 判断key是否存在
     */
    protected function getExists($key)
    {
        return $this->getRedis()->exists($key);
    }

    /**
     * @param $tradeId
     * @return mixed
     */
    public function getOrderInfo($tradeId)
    {
        return $this->getRedis()->get(self::CACHE_PREFIX . $tradeId);
    }

    /**
     * 验证签名
     * @return bool
     */
    public function checkSign()
    {
        $sign = $this->data['request']['sign'];
        $shaSign = $this->_sign(
            [
                'appKey' => $this->data['request']['appKey'],
                'shopIdenty' => $this->data['request']['shopIdenty'],
                'timestamp' => $this->data['request']['timestamp'],
                'version' => $this->data['request']['version']
            ],
            $this->getOutletToken()
        );
        if ($sign != $shaSign) return false;
        return true;
    }

    /**
     * 签名生成signature
     */
    private function _sign($data, $token)
    {
        //字符串拼接
        ksort($data);
        $args = "";
        foreach ($data as $key => $value) {
            $args .= $key . $value;
        }
        $sign = hash("sha256", $args . $token);

        return $sign;
    }

    /**
     * 订单信息状态
     * @return array
     */
    private function order_status()
    {
        return $this->returnMsg(1, 'ok', [
            'vendor_order_status' => $this->confirmOrderStatus(
                $this->callbackDatas['operation'],
                $this->maDaoOrderStatus),
            'content' => '第三方订单推送状态',
        ]);
    }

    /**
     * 接收订单通知
     */
    private function success()
    {
        return $this->returnMsg(1, 'ok', [
            'vendor_order_status' => $this->maDaoOrderStatus[6],
            'content' => '第三方已确认订单',
        ]);
    }

    /**
     * 商家客如云取消订单
     * @return array
     */
    private function cancel()
    {
        return $this->returnMsg(1, 'ok', [
            'vendor_order_status' => $this->maDaoOrderStatus[8],
            'content' => '第三方取消订单原因:' . $this->callbackDatas['reason'] ?? '第三方无填写取消原因',
        ]);
    }

    /**
     * 商家同意退款
     * @return array
     */
    private function agree_refund()
    {
        return $this->returnMsg(1, 'ok', [
            'vendor_order_status' => $this->maDaoOrderStatus[3],//已退货
            'content' => '第三方同意退款',
        ]);
    }

    /**
     * 商家拒绝退款
     * @return array
     */
    private function refuse_refund()
    {
        $orderInfo = $this->data['vendor']['order'];
        if ($orderInfo['status'] == $this->maDaoOrderStatus[3]) {
            return $this->returnMsg(0, '第三方商家已退款，无法再操作拒绝退款');
        }
        $this->data['vendor']['order_detail'] = [
            'connector_user' => [
                'id' => $orderInfo['order_verify']['connector_user_id']
            ]
        ];

        $callbackOrderInfos = $this->getOrderStatus($orderInfo['order_verify']['connector_order_sn']);
        if ($callbackOrderInfos['code'] == 0) {
            return $this->returnMsg(0, $callbackOrderInfos['msg']);
        }
        if (isset($callbackOrderInfos['data']['content']['status_code'])
            && $callbackOrderInfos['data']['content']['status_code'] == 4) {
            return $this->returnMsg(0, '第三方订单已取消，无法再操作拒绝退款');
        }

        return $this->returnMsg(1, 'ok', [
            'vendor_order_status' => $this->maDaoOrderStatus[5],//已拒绝
            'content' => '第三方拒绝退款',
        ]);
    }

    /**
     * 商户作废订单通知
     * @return array
     */
    private function invalidate_notice()
    {
        if (!$this->callbackDatas['reason']) {
            return $this->returnMsg(0, '第三方商家未填写商家接单后作废订单通知原因');
        }
        return $this->returnMsg(1, 'ok', [
            'vendor_order_status' => $this->maDaoOrderStatus[8],//关闭交易
            'content' => '第三方商家接单后作废订单通知：' . $this->callbackDatas['reason'],
        ]);
    }

    /**
     * 外卖退货通知
     * @return array
     */
    private function returned_purchase()
    {
        if (!$this->callbackDatas['reason']) {
            return $this->returnMsg(0, '第三方商家未填写收银后发起退货通知原因');
        }

        return $this->returnMsg(1, 'ok', [
            'vendor_order_status' => $this->maDaoOrderStatus[3],//已退货
            'content' => '第三方商家收银后发起退货通知：' . $this->callbackDatas['reason'],
        ]);
    }

    public function __call($func, $args)
    {
        return $this->driver->$func($args);
    }

    /**
     * 菜品变更
     * operation 1：品牌新增；2：品牌修改；3：门店修改；4：删除；5：启用；6：停用；9：估清；10：在售,和状态;
     * 13:门店商品剩余售卖量变更;14:门店商品新增（品牌通过销售范本给门店增加商品或门店有权限增加商品）;
     * 15:门店删除
     */
    public function product()
    {
        $connectorProductId = $this->data['vendor']['goods']['connector_product_id'];
        if(!$connectorProductId){
            return $this->returnMsg(0, 'product变更为空', ['connector_product_id' => $connectorProductId]);
        }

        $datas = $this->callbackDatas;

        $madaoStatus = 0;
        switch ($datas['operation']){
            case 4:
            case 6:
            case 15:
                $madaoStatus = 3;
                break;
            case 5:
            case 13:
                $madaoStatus = 1;
                break;
        }
        $shopIdenty = $datas['shopIdenty'];

        if(13 == $datas['operation'] || 1 == $madaoStatus){
            return $this->_getQueryDishMenuByIds([$connectorProductId], $datas['operation'], $madaoStatus);
        }

        if(3 == $madaoStatus){
            return $this->returnMsg(1, 'ok', [[
                'goods_id' => $this->data['vendor']['goods']['id'],
                'shop_identy' => $shopIdenty,
                'connector_product_id' => $connectorProductId,
                'status' => $madaoStatus,
                'operation' => $datas['operation']
            ]]);
        }

        return $this->returnMsg(0, 'product变更为空');
    }

    /**
     * @param $dishBrandIds
     */
    private function _getQueryDishMenuByIds($dishBrandIds, $operation, $madaoStatus)
    {
        $result = $this->driver->dishMenuByIds($dishBrandIds);
        if(0 == $result['status'])  return $this->returnMsg(0, 'product变更查询菜品为空');

        $data = [];

        foreach ($result['data'] as $k=>$v){
            $data[$k]['goods_id'] = $this->data['vendor']['goods']['id'];
            $data[$k]['shop_identy'] = $v['shop_identy'];
            $data[$k]['connector_product_id'] = $v['id'];
            $data[$k]['goods_number'] = $v['goods_number'];//剩余可售数量
            $data[$k]['sale_total'] = $v['sale_total'];
            $data[$k]['status'] = $v['status'];
            $data[$k]['operation'] = $operation;
            $data[$k]['connector_status'] = $v['connector_status'];
        }

        return $this->returnMsg(1, 'ok', $data);
    }

}