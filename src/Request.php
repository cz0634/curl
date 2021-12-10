<?php
/**
 * Created by PhpStorm.
 * User: cz
 * Date: 2020/5/23
 * Time: 13:47
 */

namespace CzCurl;

class Request {

    private static $instance = null;

    private $curl;
    private $timeout = 30; // 默认超时时间
    private $needHeader = false; // 是否获取响应头
    private $cookiePath = false; // 是否保存cookie到文件

    //构造方法私有化
    private function __construct()
    {

    }

    //禁止克隆
    private function __clone()
    {

    }

    /**
     * 获取curl资源
     * @return Request
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {
                echo $e->getMessage().'<br/>';
            }
        }
        return self::$instance->init();
    }

    /**
     * 初始化方法
     * @return $this
     */
    private function init()
    {
        $this->curl = curl_init();
        return $this;
    }

    /**
     * 设置一个cURL传输选项。
     * @param $opt
     * @param $value
     * @return $this
     */
    public function setOpt($opt, $value)
    {
        curl_setopt($this->curl, $opt , $value );
        return $this;
    }

    /**
     * 设置请求头
     * @param array $header
     * @return $this
     */
    public function setHeader(array $header = [])
    {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        return $this;
    }

    /**
     * 获取响应头信息
     * @return $this
     */
    public function getResponseHeader()
    {
        $this->needHeader = true;
        return $this;
    }

    /**
     * 设置超时时间
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = is_int($timeout) ? $timeout : $this->timeout;
        return $this;
    }

    /**
     * 设置cookie保存路径
     * @param $path
     * @return $this
     */
    public function setCookiePath($path)
    {
        $this->cookiePath = $path;
        return $this;
    }

    /**
     * get请求
     * @param $url
     * @param array $data
     * @return bool|false|string
     */
    public function get($url, $data = [])
    {
        $data = $this->handleData($data);
        $url = $data ? $url.'?'.$data : $url;

        return $this->send($url);
    }

    /**
     * post请求
     * @param $url
     * @param array $data
     * @return bool|false|string
     */
    public function post($url, $data = [])
    {
        curl_setopt($this->curl , CURLOPT_POST , true );
        curl_setopt($this->curl , CURLOPT_POSTFIELDS , $data );

        return $this->send($url);
    }

    /**
     * 发送请求
     * @param $url
     * @return bool|false|string
     */
    private function send($url)
    {
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER , true );
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);

        // 设置超时时间
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT , $this->timeout);
        curl_setopt($this->curl, CURLOPT_TIMEOUT , $this->timeout);

        if (is_numeric(strpos($url,'https'))) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        // 设置cookie
        if ($this->cookiePath) {
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookiePath);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookiePath);
        }

        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
        curl_setopt($this->curl, CURLOPT_HEADER, $this->needHeader);

        // 设置请求域名
        curl_setopt($this->curl , CURLOPT_URL , $url );

        $response = curl_exec($this->curl );

        if ($response === FALSE) {
            echo "cURL Error: " . curl_error($this->curl);
            return false;
        }

        if ($this->needHeader) {
            // 获得响应结果里的：头大小
            $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
            // 根据头大小去获取头信息内容
            $header = substr($response, 0, $headerSize);
            $response = substr($response, $headerSize);
            return json_encode([
                'response' => $response,
                'header'   => $header
            ]);
        }
        curl_close($this->curl);
        return $response;
    }

    /**
     * 处理请求数据
     * @param null $data
     * @return bool|string|null
     */
    private function handleData($data = null)
    {
        $returnData = $data;

        if (!$data) {
            $returnData = false;
        }

        if (is_array($data)) {
            $returnData = http_build_query($data);
        }

        return $returnData;
    }
}