<?php
/**
 * ======================================================
 * Author: cc
 * Created by PhpStorm.
 * Copyright (c)  cc Inc. All rights reserved.
 * Desc: 由于管家婆和章鱼侠 很多接口是共用的，只是请求接口不一样
 *  ======================================================
 */
namespace Imactool\Gjpzyx\Base;

use Imactool\Gjpzyx\Gjpzyx as GjpzyxClass;
use Imactool\Gjpzyx\Http;
use Imactool\Gjpzyx\Exceptions\HttpException;


class Base extends GjpzyxClass
{
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * 接口获取授权认证码 [废弃]
     * @return mixed
     * @throws HttpException
     */
    public function getAuthCode()
    {
        echo "how r u ";
        return "123";
        // $timenow = date('Y-m-d H:i:s',time());
        // //第一步:组装P参数
        // $params = [
        //     'CompanyName'=>$this->config['CompanyName'],
        //     'UserId'=>$this->config['UserId'],
        //     'Password'=>$this->config['Password'],
        //     'TimeStamp'=>$timenow
        // ];
        // $param = $this->makeSecretData($params);

        // $postData = [];
        // $postData['p']      = $param['p'];
        // $postData['sign']   = $param['sign'];
        // $postData['appkey'] = $this->config['appKey'];//key

        // try {

        //     $result = (new Http())->setApiUrl($this->config['apiUrl'])->httpPost($postData,'login');

        //     if (!$result['iserror']){
        //         return $result['response']['authcode'];
        //     }else{
        //         return $result;
        //     }
        // }catch (\Exception $e){
        //     throw new HttpException($e->getMessage(),$e->getCode(),$e);
        // }
    }

    /**
     * 步骤2 利用授权认证码获取token信息
     * 接口获取授权认证码 token
     * @param string $auth_code
     * @return array|mixed
     * @throws HttpException
     * @author cc
     */
    public function getTokenInfo(string $auth_code)
    {

        $params = [
            'TimeStamp'=>date('Y-m-d H:i:s'),
            'GrantType'=>'auth_token',
            'AuthParam'=>trim($auth_code)
        ];
        $param = $this->makeSecretData($params);

        $postData = [];
        $postData['p']      = $param['p'];
        $postData['sign']   = $param['sign'];
        $postData['appkey'] = $this->config['appKey'];//key

        try {
            $result = (new Http())->setApiUrl($this->config['apiUrl'])->httpPost($postData,'token');
            if ($result['iserror']){
                return ['code'=>$result['errorcode'],'msg'=>$result['errormessage'].'，requestid : '.$result['requestid']];
            }
            $response = trim($result['response']['response']);
            $tokenInfo = $this->aesFace()->decrypt($response);
            $tokenInfo = trim($tokenInfo);
            preg_match('/^\{("\w+":"(.*?)",?)+\}/', $tokenInfo,$match);
            $result = json_decode($match[0],true);
            $this->config['token'] = $result["auth_token"];
            $this->config["refreshToken"] = $result["refresh_token"];
            return  \json_decode($match[0],true);
        }catch (\Exception $e){
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }

    }

    /**
     * 刷新 token
     * @param $refresh_token
     * @return array|mixed
     * @throws HttpException
     */
    public function refreshToken(string $refresh_token){

        $params = [
            'TimeStamp'=>date('Y-m-d H:i:s'),
            'GrantType'=>'refresh_token',
            'AuthParam'=>trim($refresh_token)
        ];

        $param = $this->makeSecretData($params);

        $postData = [];
        $postData['p']      = $param['p'];
        $postData['sign']   = $param['sign'];
        $postData['appkey'] = $this->config['appKey'];//key

        try {
            $result = (new Http())->setApiUrl($this->config['apiUrl'])->httpPost($postData,'token');
            if ($result['iserror']){
                return ['code'=>$result['errorcode'],'msg'=>$result['errormessage'].'，requestid : '.$result['requestid']];
            }
            $response = trim($result['response']['response']);
            $tokenInfo = $this->aesFace()->decrypt($response);
            preg_match('/^\{("\w+":"(.*?)",?)+\}/', $tokenInfo,$match);
            return  \json_decode($match[0],true);
        }catch (\Exception $e){
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }
    }

    /**
     * 步骤1 获取授权认证码
     * 站点登录获取授权认证码
     * 组装授权登录参数，访问授权登录地址，输入erp账号,认证成功后 ，在回调地址返回auth_code
     * @param $redirect_url 授权成功后跳转的地址,主域名必须和申请应用时的主域名一致
     * @param string $keyword 维持应用的状态，传入值与返回值保持一致
     * @return string
     */
    public function getAuthUrl($redirect_url,$keyword='gjpzyx')
    {
        $url = $this->config['loginUrl'].'appkey='.$this->config['appKey'].'&redirect_url='.$redirect_url.'&keyword='.$keyword;
        return $url;
    }

    //---------------------------------------------------
    // 商品 start
    //---------------------------------------------------
    /**
     * 商品上载，将商城的商品推送到章鱼侠云erp中。
     * @param array $params
     */
    public function pushProduct(array $params){
        $post['method']     = $this->config['methodPefix'].'.selfbuiltmall.uploadproducts';
        $post['shopkey']   = $this->config['shopKey'];
        $post['products']   = json_encode(array($params));

        return $this->httpUpload($post);
    }

        //---------------------------------------------------
    // 查询仓库信息接口 start
    //---------------------------------------------------
    /**
     * 商品上载，将商城的商品推送到章鱼侠云erp中。
     * @param array $params
     */
    public function queryKtypelist(array $params){
        $post['shopkey']   = $this->config['shopKey'];
        $post['ktypeids']   = json_encode($params['ktypeids']);
        $post['pagesize']   = $params['pagesize'];
        $post['pageno']   = $params['pageno'];
        $post['method']     = $this->config['methodPefix'].'.selfbuiltmall.queryktypelist';
        return $this->httpUpload($post);
    }

    /**
     * 商品详情查询 ERP商品基本资料获取，可通过商品id进行筛选条件
     * @param array $params
     * @return mixed
     */
    public function queryProduct(array $params){
        $post['method']     = $this->config['methodPefix'].'.selfbuiltmall.queryproductinfo';
        if (empty($params)){
            throw new \InvalidArgumentException('缺少业务参数');
        }
        $post = array_merge($post,$params);
        return $this->httpRequest($post);
    }

    //---------------------------------------------------
    // 交易 start
    //---------------------------------------------------
    /**
     * 查询交易列表信息
     * @param array $params
     * @return mixed
     */
    public function querySaleOrder(array $params){
        $post['method'] = $this->config['methodPefix'].'.selfbuiltmall.querysaleorder';
        $post['shopkey']    = $this->config['shopKey'];
        $post = array_merge($post,$params);
        return $this->httpRequest($post);
    }

    /**
     * 订单批量上载/更新接口
     * @param array $params
     * @return mixed
     */
    public function pushOrder(array $params){
        $post['method']     = 'beefun'.'.selfbuiltmall.uploadsaleorders';
        $post['shopkey']    = $this->config['shopKey'];
        $post['orders']   = \json_encode(array($params));
        return $this->httpRequest($post);
    }

    /**
     * 查询订单处理状态
     * @param array $params
     * @return mixed
     */
    public function queryOrderStatus(array $params){
        $post['method']     = $this->config['methodPefix'].'.selfbuiltmall.querytradestatus';
        $post = array_merge($post,$params);
        return $this->httpRequest($post);
    }


    //---------------------------------------------------
    // 库存 start
    //---------------------------------------------------
    /**
     * 描述：获取ERP商品基本资料的库存信息
     * @param array $params
     * [
     * numid 商品数字id (同商品上载接口中的请求参数numid)
     * ktypeids  array 仓库ID
     * iscalcsaleqty 是否查询可销售库存（true时接口才会返回可销售库存数量）
     * pagesize 每页结果数量,默认为1，最大100
     * pageno 页数，从1开始
     * ]
     * @return mixed
     */
    public function querySaleQty(array $params){
        $post['method'] = $this->config['methodPefix'].'.selfbuiltmall.querysaleqty';
        $post['shopkey']    = $this->config['shopKey'];
	$post = array_merge($post,$params);
        return  $this->httpRequest($post);
    }

    /**
     * 描述：系统仓库信息查询接口
     * @param array $params
     * @return mixed
     */
    public function queryQtyInfo(array $params){
        $post['method'] = $this->config['methodPefix'].'.selfbuiltmall.queryktypelist';
        $post = array_merge($post,$params);
        return $this->httpRequest($post);
    }

    /**
     * 批量获取ERP商品基本资料的库存信息
     * @param array $params
     * [
     *  numids 商品数字id数组(同商品上载接口中的请求参数numid)
     *  ktypeids 仓库ID(仓库信息查询接口返回的ktypeid。不传此参数时默认查询所有仓库的库存)
     * ]
     * @return mixed
     */
    public function queryMoreSaleQty(array $params){
        $post['method'] = $this->config['methodPefix'].'.selfbuiltmall.batchquerysaleqty';
        $post = array_merge($post,$params);
        return $this->httpRequest($post);
    }

    //---------------------------------------------------
    // 售后 start
    //---------------------------------------------------
    /**
     * 上载售后单
     * @param array $params
     * @return mixed
     */
    public function pushOrderRefund(array $params){
        $post['method']     = $this->config['methodPefix'].'.selfbuiltmall.updateeshoprefund';
        $post['shopkey']    = $this->config['shopKey'];
        $post['refunds']   = \json_encode($params);
        echo $post['refunds'];
        return $this->httpRequest($post);
    }

    /**
     * erp售后单信息查询
     * @param array $params
     * @return mixed
     */
    public function queryOrderRefund(array $params){
        $post['method']     = $this->config['methodPefix'].'.selfbuiltmall.queryeshoprefund';
        $post['shopkey']    = $this->config['shopKey'];
        if (empty($params)){
            throw new \InvalidArgumentException('缺少业务参数');
        }
        $post = array_merge($post,$params);
        return $this->httpRequest($post);
    }

    //---------------------------------------------------
    // 其他 start
    //---------------------------------------------------
    /**
     * 描述：上载门店信息
     * @param array $params
     * [
     * Id 门店数字id
     * storecode 门店编码
     * storename 门店名称
     * storetype 门店类型（1,自提2，配送3,自提+配送）
     * image 门店图片
     * storephonenumber  门店电话
     * storeaddress  门店地址
     * ]
     * @return mixed
     */
    public function pushStoreInfo(array $storeInfo){
        $params['method'] = $this->config['methodPefix'].'.selfbuiltmall.uploadstoreinfos';
        $params['shopkey'] = $this->config['shopKey'];
        $params['storelist'] = \json_encode(array($storeInfo));
        return $this->httpRequest($params);
    }

    public function httpRequest(array $post){

        $post['appkey']     = $this->config['appKey'];
        $post['token']      = $this->config['token'];
        $post['timestamp']  = date('Y-m-d H:i:s');
        $post['sign']       = $this->getSign($post);
        // $test['foo']=1;
        // $test['bar']=2;
        // $test['foo_bar']=3;
        // $test['foobar']=4;
        // echo "http request\n";
        // echo  $this->getSign($test);
        // var_dump($post);
        $result =  (new Http())->setApiUrl($this->config['apiUrl'])->httpPost($post);
       
        // var_dump($this->config);
        return $result;
    }

    function httpUpload($post_data,$format=''){
        $post_data['appkey'] =  $this->config['appKey'];
        $post_data['token'] = $this->config['token'];
        $post_data['timestamp'] = date('Y-m-d H:i:s',time());
        // $post_data['shopkey'] = $this->config['shopkey'];

        $post_data['sign'] =  $this->getSign($post_data);
        $request = $post_data;

        $str = '';
        foreach($request as $k=>$v){
            $str .= $k.'='.urlencode($v).'&';
        }

        //echo '<br/>'.'<br/>'.'最终post的body数据：'.'<br/>';
        //print_r($str);

        $str = trim($str,'&');

        if(empty($format)) $format = "application/x-www-form-urlencoded";
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:'.$format,
                'content' => $str,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $output = file_get_contents($this->config['apiUrl'], false, $context); //发送post请求

        //echo '<br/>'.'<br/>'.'接口返回：'.'<br/>';
        //print_r($output);

        $ret_json=json_decode($output,true,512,JSON_BIGINT_AS_STRING);
        //print_r($ret_json);
        return $ret_json;
    }
    function dumpConfigToJson()
    {

        $file_name = 'gjp_token.json';
        // 写入方式打开文件，fopen有多种打开方式
        $text_file = fopen($file_name, "w");
        // //这里一定要用"/r/n"才能正确换行
        
        fwrite($text_file, json_encode($this->config));
        // 关闭文件
        fclose($text_file);
        echo '管家婆token写入文件成功<br/>';
    }

    function loadConfigFromJson()
    {
    
        $file_name = 'gjp_token.json';

        // 判断文件是否存在
        if(!file_exists($file_name)){
            echo '管家婆没有读取到json<br/>';
            return false;
        }

        $json_string = file_get_contents($file_name);

  
        $this->config = json_decode($json_string, true);
        echo '管家婆token加载成功, configs: <br/>';
        var_dump($this->config);
        echo '<br/>';
        return true;
    }

}
