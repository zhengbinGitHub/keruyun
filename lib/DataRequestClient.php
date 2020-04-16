<?php
require_once PATH_ROOT . '/lib/DataResponseClient.php';
/**
 * Created by PhpStorm.
 * User: maczheng
 * Date: 19/6/2
 * Time: 下午12:07
 */
class DataRequestClient
{
    /**
     * http request timeout;
     */
    private $httpTimeout = 10;

    /**
     * 配置项
     */
    private $config;

    /**
     * 接口类
     */
    private $api;

    private $url;

    const FAIL = "fail";

    const SUCCESS = "success";

    const FAIL_MSG = "接口请求超时或失败";

    const FAIL_CODE = -2;

    /**
     * 构造函数
     */
    public function __construct($config, $api, string $url){
        $this->config = $config;
        $this->api = $api;
        $this->url = $url;
    }

    /**
     * 请求调用api
     * @return
     */
    public function makeRequest($isGet = false){
        $urlStr = $this->bulidRequestParams();
        if(!$isGet) {
            $reqParams = $this->getApi()->getBusinessParams();
            $resp = $this->getHttpRequestWithPost($urlStr, $reqParams);
        }
        else{
            $resp = $this->getHttpRequestWithGet($urlStr);
        }
        return $this->parseResponseData($resp);
    }

    /**
     * 构造请求数据
     * data:业务参数，json字符串
     */
    public function bulidRequestParams(){
        $config = $this->getConfig();
        $requestParams = array();
        $requestParams['appKey'] = $config->getAppKey();
        $requestParams['version'] = $config->getV();
        $requestParams['shopIdenty'] = $config->getShopIdenty();
        $requestParams['timestamp'] = time();
        $requestParams['sign'] = $this->_sign($requestParams, $config->getToken(), $config->getAppSecret());
        return http_build_query($requestParams);
    }

    /**
     * 签名生成signature
     */
    public function _sign($data, $token, $appSecret = ''){
        //字符串拼接
        ksort($data);
        $args = "";
        foreach ($data as $key => $value) {
            $args.=$key.$value;
        }
        $sign = hash("sha256", $args . ($token ? $token : $appSecret));

        return $sign;
    }


    /**
     * 发送请求,POST
     * @param $url 指定URL完整路径地址
     * @param $data 请求的数据
     */
    public function getHttpRequestWithPost($urlStr, array $data){
        try {
            $config = $this->config;
            $url = $config->getHost() . $this->getUrl() . '?' . $urlStr;
            if(is_array($data)){
                $data = json_encode($data);
            }
            InitPHP::log('keruyun getHttpRequestWithPost'.$url. $data, 'INFO');
            // json
            $headers = array(
                'Content-Type: application/json',
            );

            $ssl = stripos($url,'https://') === 0 ? true : false;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            if ($ssl) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
            }
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->httpTimeout);
            if (is_array($headers))
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); //设置请求的Header

            $result = curl_exec($curl);
            InitPHP::log('keruyun api post result'.$result, 'INFO');
            //var_dump( curl_error($curl) );//如果在执行curl的过程中出现异常，可以打开此开关查看异常内容。
            $info = curl_getinfo($curl);
            curl_close($curl);
            if (isset($info['http_code']) && $info['http_code'] == 200) {
                return $result;
            }
            InitPHP::log('keruyun api post info'. json_encode($info, JSON_UNESCAPED_UNICODE), 'INFO');
            return '';
        }
        catch (Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $urlStr
     * @return mixed|string
     * @throws Exception
     */
    public function getHttpRequestWithGet($urlStr){
        try {

            $config = $this->config;
            $url = $config->getHost() . $this->getUrl() . '?' . $urlStr;

            InitPHP::log('keruyun getHttpRequestWithGet'.$url, 'INFO');
            $headers = array(
                'Content-Type: application/json',
            );

            $ssl = stripos($url,'https://') === 0 ? true : false;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            if ($ssl) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
            }
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); //在HTTP请求中包含一个"User-Agent: "头的字符串。
            curl_setopt($curl, CURLOPT_HEADER, 0); //启用时会将头文件的信息作为数据流输出。
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //文件流形式
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->httpTimeout); //设置cURL允许执行的最长秒数。
            if (is_array($headers))
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); //设置请求的Header

            $result = curl_exec($curl);
            InitPHP::log('keruyun api get result'.$result, 'INFO');
            $info = curl_getinfo($curl);
            curl_close($curl);
            if (isset($info['http_code']) && $info['http_code'] == 200) {
                return $result;
            }
            InitPHP::log('keruyun api get info'.json_encode($info, JSON_UNESCAPED_UNICODE), 'INFO');
            return '';
        }
        catch (Exception $e){
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 解析响应数据
     * @param $arr返回的数据
     * 响应数据格式：{"status":"success","result":{},"code":0,"msg":"成功"}
     */
    public function parseResponseData($arr){
        $resp = new DataResponseClient();
        if (empty($arr)) {
            $resp->setMsg(self::FAIL_MSG);
            $resp->setCode(self::FAIL_CODE);
        }else{
            $data = json_decode($arr, true);
            $resp->setMessageUuid($data['messageUuid']);
            $resp->setMsg($data['message'] . ($data['apiMessage']??''));
            $resp->setCode($data['code']);
            $resp->setResult($data['result']);
            $resp->setApiMessage($data['apiMessage']??'');
        }
        return $resp;
    }

    public function getConfig(){
        return $this->config;
    }

    public function getApi(){
        return $this->api;
    }

    public function getUrl()
    {
        return $this->url;
    }
}