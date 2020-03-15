# 淘宝接口封装

## 安装

> composer require anqin/tao-request

## 使用

>$client = new TaoRequest();
>
>$client->appKey = '1000000';
>
>$client->secretKey = 'xxxxxx';
>
>$req = new TimeGet();
>
>$client->execute($req);
