<?php
/**
 * ======================================================
 * Author: cc
 * Desc: 封装HTTP请求
 *  ======================================================
 */
namespace Imactool\Gjpzyx;

use GuzzleHttp\Client;
use Imactool\Gjpzyx\Exceptions\HttpException;
use Imactool\Gjpzyx\Exceptions\InvalidArgumentException;

class Http
{

    protected $guzzleOptions;
    protected $baseUri ;

    /**
     * get a Guzzle http client
     * @return Client
     */
    public function getHttpClient()
    {
        if (!isset($this->guzzleOptions['base_uri'])){
            $this->guzzleOptions['base_uri'] = $this->baseUri;
        }
        return new Client($this->guzzleOptions);
    }

    /**
     * 用户可以自定义 guzzle 实例的参数，比如超时时间等
     * @param array $options
     */
    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    public function setApiUrl($api){
        $this->baseUri = trim($api,'/').'/';
        return $this;
    }

    public function httpPost( array $postData,$uri=''){
        try {
            $httpClient = $this->getHttpClient()->request('POST',$uri,['form_params'=>$postData]);
            $response = $httpClient->getBody()->getContents();
            return \json_decode($response,true);
        }catch (\Exception $e){
            throw new HttpException($e->getMessage(),$e->getCode(),$e);
        }
    }

        function upload($post_data,$format=''){
        $post_data['appkey'] = $this->config['appkey'];
        $post_data['token'] = $this->token;//auto_code
        $post_data['timestamp'] = date('Y-m-d H:i:s',time());

        //print_r($post_data);
        //echo '<br/>'.'<br/>'.'获取业务参数：'.'<br/>';

        $post_data['shopkey'] = $this->config['shopkey'];

        //print_r($post_data['shopkey']);
        //echo '<br/>';
        //print_r($post_data['orders']);
        //echo '<br/>'.'<br/>'.'获取签名：';

        $post_data['sign'] =  $this->GetSign($this->config['sign_key'],$post_data);
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
        $output = file_get_contents($this->config['apiurl'], false, $context); //发送post请求

        //echo '<br/>'.'<br/>'.'接口返回：'.'<br/>';
        //print_r($output);

        $ret_json=json_decode($output,true,512,JSON_BIGINT_AS_STRING);
        //print_r($ret_json);
        return $ret_json;
    }

}