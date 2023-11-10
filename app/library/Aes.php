<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Aes
{
     
    // string $method 加解密方法，可通过openssl_get_cipher_methods()获得
    protected $method;

    // string $secret_key 加解密的密钥
    protected $secret_key;

    // string $iv 加解密的向量，有些方法需要设置比如CBC
    protected $iv;

    // string $options 默认为0
    protected $options;

    /**
     * 构造函数
     *
     * @param string $key 密钥
     * @param string $method 加密方式
     * @param string $iv iv向量
     * @param mixed $options 默认为0 
     *
     */
    public function __construct($key = '128bitslength*@#', $method = 'AES-128-ECB', $iv = '', $options = 0) {
        // key是必须要设置的
        $this->secret_key = $key;

        $this->method = $method;

        $this->iv = $iv;

        $this->options = $options;
    }

    /**
     * 加密方法，对数据进行加密，返回加密后的数据
     *
     * @param string $data 要加密的数据
     *
     * @return string
     *
     */
    public function encrypt($data) {
        return openssl_encrypt($data, $this->method, $this->secret_key, $this->options, $this->iv);
    }

    /**
     * 解密方法，对数据进行解密，返回解密后的数据
     *
     * @param string $data 要解密的数据
     *
     * @return string
     *
     */
    public function decrypt($data) {
        return openssl_decrypt($data, $this->method, $this->secret_key, $this->options, $this->iv);
    }

    /**
     * [pic_decrypt  图片aes解密]
     * @param  string $url  [图片链接]
     * @return [str]        [返回base64格式图片]
     */
    public function pic_decrypt($url = '') {
        $suffix = strtolower(substr($url,strripos($url,'.')+1,1));
        $suffix = ($suffix == 'p') ? 'data:image/png;base64,' : 'data:image/jpg;base64,';
        $data = @file_get_contents($url);
        if ($data) {
            $data   = base64_encode($data);
            $data   = $suffix.base64_encode($this->decrypt($data));
        }
        return $data;
    }

}