<?php
/**
 * Created by PhpStorm.
 * User: AnQin
 * Date: 014 16 3 14
 * Time: 17:21
 */

namespace an;

use an\tao\{ResultSet, TaoApiBase};

class TaoRequest {
    public int $appKey;
    public string $secretKey;
    public string $gatewayUrl = 'https://eco.taobao.com/router/rest';
    public string $format = 'json';
    public bool $simplify = false;
    public int $connectTimeout;

    protected string $signMethod = 'hmac';
    protected string $apiVersion = '2.0';
    protected string $sdkVersion = 'top-ios-sdk';

    public function setSignMethod(string $type = 'md5') {
        $this->signMethod = strtolower($type) != 'md5' ? 'hmac' : 'md5';

        return $this;
    }

    /**
     * @param TaoApiBase  $request
     * @param string|null $session
     * @param string|null $bestUrl
     * @return mixed|ResultSet
     * @throws \ErrorException
     */
    public function execute(TaoApiBase $request, ?string $session = null, ?string $bestUrl = null) {
        $result = new ResultSet();

        //组装系统参数
        $sysParams['app_key'] = $this->appKey;
        $sysParams['v'] = $this->apiVersion;
        $sysParams['format'] = $this->format;
        $sysParams['simplify'] = $this->simplify;
        $sysParams['sign_method'] = $this->signMethod;
        $sysParams['method'] = $request->getApiMethodName();
        $sysParams['timestamp'] = date('Y-m-d H:i:s');
        if (!is_null($session)) $sysParams['session'] = $session;

        //获取业务参数
        $apiParams = $request->getApiParas();

        //系统参数放入GET请求串
        $requestUrl = ($bestUrl ?? $this->gatewayUrl) . '?';
        $sysParams['partner_id'] = ($bestUrl ? $this->getClusterTag() : $this->sdkVersion);
        //签名
        $sysParams['sign'] = $this->generateSign(array_merge($apiParams, $sysParams));
        foreach ($sysParams as $sysParamKey => $sysParamValue) $requestUrl .= $sysParamKey . '=' . urlencode($sysParamValue) . '&';
        $requestUrl = substr($requestUrl, 0, -1);

        //发起HTTP请求
        $http = new HttpCurl();
        try {
            $resp = $http->post($requestUrl, $apiParams)->exec()->toString();
        } catch (\Exception $e) {
            $result->code = $e->getCode();
            $result->msg = $e->getMessage();

            return $result;
        } finally {
            $http->close();
        }

        //解析TOP返回结果
        $respWellFormed = false;
        $respObject = json_decode($resp, true, JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE);
        if (null !== $respObject) {
            $respWellFormed = true;
            foreach ($respObject as $propKey => $propValue) {
                $respObject = $propValue;
            }
        }

        //返回的HTTP文本不是标准JSON或者XML，记下错误日志
        if (false === $respWellFormed) {
            $result->code = 0;
            $result->msg = 'HTTP_RESPONSE_NOT_WELL_FORMED';

            return $result;
        }

        return $respObject;
    }

    private function getClusterTag(): string {
        return substr($this->sdkVersion, 0, 11) . '-cluster' . substr($this->sdkVersion, 11);
    }

    private function generateSign(array $params): string {
        ksort($params);
        $stringToBeSigned = '';
        foreach ($params as $k => $v) if (!is_object($v)) $stringToBeSigned .= $k . $v;

        unset($k, $v);
        if ($this->signMethod == 'hmac') $sign = hash_hmac('md5', $stringToBeSigned, $this->secretKey); else
            $sign = md5($this->secretKey . $stringToBeSigned . $this->secretKey);

        return strtoupper($sign);
    }

    public function unixTimeStamp(): int {
        return number_format(microtime(true), 3, '', '');
    }
}