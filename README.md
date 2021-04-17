# 淘宝接口封装

## 安装

> composer require anqin/tao-request

## 使用
```
$client = new TaoRequest([
    'app_key'         => 'YourAppKey',
    'secret_key'      => 'YourSecretKey',
]);
$req    = new TbkScOrderDetailsGet();
$req->setStartTime('2021-04-05 12:18:22');
$req->setEndTime('2021-04-06 12:19:22');
$res = $client->execute($req, 'xxxxxx');`
```