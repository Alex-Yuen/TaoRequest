<?php
/*
 * 饭粒科技
 * Author: AnQin <an-qin@qq.com>
 * Copyright © 2019-2021. Hangzhou FanLi Technology Co., Ltd All rights reserved.
 * Create Date: 2021-04-17 09:47
 */

namespace an;

use an\middleware\guzzle\TaoApiGuzzleMiddleware;
use an\request\TaoApiBase;
use JetBrains\PhpStorm\Pure;
use JsonException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;

class TaoRequest
{
    const Online            = 1;
    const MessageService    = 2;
    const AbroadOnline      = 4;
    const SignMethod_MD5    = 'md5';
    const SignMethod_HMAC   = 'hmac';
    const SignMethod_SHA256 = 'hmac-sha256';

    const HttpMehod_GET  = 'GET';
    const HttpMehod_POST = 'POST';
    const ApiGatewayUrl  = [
        1 => [
            'gw.api.taobao.com/router/rest',
            'eco.taobao.com/router/rest',
        ],
        2 => 'ws://mc.api.taobao.com/',
        4 => 'api.taobao.com/router/rest',
    ];

    protected array $payload = [
        'mode'            => self::Online,
        'https'           => false,
        'app_key'         => '',
        'secret_key'      => '',
        'sign_method'     => self::SignMethod_SHA256,
        'api_version'     => '2.0',
        'sdk_version'     => '',
        'proxy'           => '',
        'connect_timeout' => 3,
        'read_timeout'    => 10,
        'simplify'        => true,
        'debug'           => false,
        'http_errors'     => false,
        'http_method'     => self::HttpMehod_POST,
    ];

    public function __construct(array $config)
    {
        $this->payload = array_merge($this->payload, $config);
    }

    public static function instance(array $config): static
    {
        return new static($config);
    }

    public function execute(TaoApiBase $request, ?string $session = null): array
    {
        $stack = HandlerStack::create();
        $stack->push(new TaoApiGuzzleMiddleware($this->payload, $request, $session), 'TaoApi');

        $client = new Client([
            'handler'         => $stack,
            'proxy'           => $this->payload['proxy'],
            'http_errors'     => $this->payload['http_errors'],
            'verify'          => $this->payload['https'],
            'connect_timeout' => $this->payload['connect_timeout'],
            'read_timeout'    => $this->payload['read_timeout'],
            'debug'           => $this->payload['debug'],
        ]);


        //发起HTTP请求
        try {
            $resp = $client->request(self::HttpMehod_POST)?->getBody()->getContents();
            //解析TOP返回结果
            $respWellFormed = false;
            $respObject     = json_decode($resp, true, JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (is_array($respObject)) {
                $respWellFormed = true;
                foreach ($respObject as $propValue)
                    $respObject = $propValue;
            }

            if (false === $respWellFormed) {
                return [
                    'code'       => 9999,
                    'msg'        => 'HTTP_RESPONSE_NOT_WELL_FORMED',
                    'sub_code'   => 9999,
                    'sub_msg'    => '返回数据解析失败',
                    'request_id' => '',
                ];
            }

            return $respObject;
        } catch (GuzzleException $e) {
            return [
                'code'       => 9999,
                'msg'        => 'GuzzleException',
                'sub_code'   => $e->getCode(),
                'sub_msg'    => $e->getMessage(),
                'request_id' => '',
            ];
        } catch (JsonException $e) {
            return [
                'code'       => 9999,
                'msg'        => 'JsonException',
                'sub_code'   => $e->getCode(),
                'sub_msg'    => $e->getMessage(),
                'request_id' => '',
            ];
        } catch (Exception $e) {
            return [
                'code'       => 9999,
                'msg'        => 'Exception',
                'sub_code'   => $e->getCode(),
                'sub_msg'    => $e->getMessage(),
                'request_id' => '',
            ];
        } finally {
            $client = null;
        }
    }
}