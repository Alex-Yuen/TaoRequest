<?php
/*
* 饭粒科技
* Author: AnQin <an-qin@qq.com>
* Copyright © 2019-2021. Hangzhou FanLi Technology Co., Ltd All rights reserved.
* Create Date: 2021-04-17 09:47
*/
declare(strict_types=1);

namespace an\middleware\guzzle;

use an\request\TaoApiBase;
use an\TaoRequest;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\MimeType;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;

class TaoApiGuzzleMiddleware
{
    public function __construct(private array $options, private TaoApiBase $request, private ?string $session = null) { }

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            //组装系统参数
            $sysParams['app_key']     = $this->options['app_key'];
            $sysParams['v']           = $this->options['api_version'];
            $sysParams['format']      = 'json';
            $sysParams['simplify']    = (string)$this->options['simplify'];
            $sysParams['sign_method'] = $this->options['sign_method'];
            $sysParams['method']      = $this->request->getApiMethodName();
            $sysParams['timestamp']   = date('Y-m-d H:i:s');
            if (!is_null($this->session)) $sysParams['session'] = $this->session;
            //获取业务参数
            $apiParams = $this->request->getApiParas();

            //系统参数放入GET请求串
            $sysParams['partner_id'] = 'TaoApiGuzzleMiddleware';

            //签名

            $signParams = array_merge($apiParams, $sysParams);
            ksort($signParams);
            $stringToBeSigned = '';
            foreach ($signParams as $k => $v) {
                if (!is_array($v) && !str_starts_with((string)$v, '@'))
                    $stringToBeSigned .= $k . $v;
            }
            unset($k, $v);

            $sysParams['sign'] = strtoupper(match ($this->options['sign_method']) {
                TaoRequest::SignMethod_MD5 => md5($this->options['secret_key'] . $stringToBeSigned . $this->options['secret_key']),
                TaoRequest::SignMethod_HMAC => hash_hmac('md5', $stringToBeSigned, $this->options['secret_key']),
                TaoRequest::SignMethod_SHA256 => hash_hmac('sha256', $stringToBeSigned, $this->options['secret_key']),
                default => ''
            });

            //组参
            $uriQuery = '';
            foreach ($sysParams as $sysParamKey => $sysParamValue)
                $uriQuery .= $sysParamKey . '=' . urlencode($sysParamValue) . '&';
            unset($sysParamKey, $sysParamValue);
            $uriQuery = http_build_query($sysParams);

            $gateway = TaoRequest::ApiGatewayUrl[$this->options['mode']];
            $gateway = is_array($gateway) ? $gateway[$this->options['https'] ? 1 : 0] : $gateway;
            $gateway = match ($this->options['mode']) {
                TaoRequest::MessageService => $gateway,
                default => ($this->options['https'] ? 'https' : 'http') . '://' . $gateway
            };

            $postMultipart = false;
            foreach ($apiParams as $v) {
                if (str_starts_with((string)$v, '@'))//判断是不是文件上传
                    $postMultipart = true;
            }
            unset($v);

            if ($postMultipart) {
                $params = [];
                array_walk($apiParams, function ($value, $key) use (&$params) {
                    if (is_string($value) && str_starts_with($value, '@'))//判断是不是文件上传
                    {
                        $fileName = ltrim($value, '@');

                        $params[] = [
                            'name'     => $key,
                            'contents' => Utils::tryFopen($fileName, 'r'),
                            'headers'  => ['Content-Type' => MimeType::fromFilename($fileName)],
                        ];
                    } else {
                        $params[] = [
                            'name'     => $key,
                            'contents' => $value,
                        ];
                    }
                });
                $body    = new MultipartStream($params);
                $request = $request->withHeader('content-type', 'multipart/form-data; boundary=' . $body->getBoundary());
            } else {
                $body = Utils::streamFor(http_build_query($apiParams));

                $request = $request->withHeader('content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
            }

            $uri     = new Uri($gateway);
            $uri     = $uri->withQuery($uriQuery);
            $request = $request->withHeader('user-agent', 'TaoApi Request GuzzleMiddleware v1.0 (see https://github.com/Alex-Yuen/TaoRequest)')
                ->withUri($uri)
                ->withBody($body);
            return $handler($request, $options);
        };
    }
}