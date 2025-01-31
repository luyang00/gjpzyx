<?php
/**
 * ======================================================
 * Author: cc
 * Desc: AES
 *  ======================================================
 */

namespace Imactool\Gjpzyx;

class Aes
{
    protected $iv = '1200kds000001233fd'; //密钥偏移量IV，可自定义
    protected $encryptKey = '7e4e615af165fe63cbf40e52abbc79e8';//AESkey，可自定义

    function hexToStr($hex)  
    {  
  
        $string='';  
  
        for ($i=0; $i < strlen($hex)-1; $i+=2)  
  
        {  
  
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));  
  
        }  
  
        return $string;  
    }  
    /**
     * 设置 密钥偏移量IV
     * @param $iv
     */
    public function setAesIv($iv){
        $this->iv = $iv;
    }

    /**
     * 设置 AESkey
     * @param $key
     */
    public function setAesKey($key){
        $this->encryptKey = $key;
    }

    //加密
    public function encrypt($encryptStr){
        $data = openssl_encrypt($encryptStr, 'AES-256-CBC', $this->encryptKey, OPENSSL_RAW_DATA, $this->iv);  
        $data = base64_encode($data);  
        return $data;  
    }

    //解密
    public function decrypt($encryptStr) {
        $decrypted = openssl_decrypt(base64_decode($encryptStr), 'AES-256-CBC', $this->encryptKey, OPENSSL_RAW_DATA, $this->iv);  
        return $decrypted;  
        // error_reporting(E_ALL & ~E_DEPRECATED); //兼容管家婆使用过时 ASE 加密方式
        // $localIV = trim($this->iv);
        // $encryptKey = trim($this->encryptKey);
        // $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        // mcrypt_generic_init($module, $encryptKey, $localIV);
        // $encryptedData = base64_decode(trim($encryptStr));
        // $encryptedData = mdecrypt_generic($module, $encryptedData);
        // return $encryptedData;
    }

}