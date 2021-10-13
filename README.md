## 介绍
- 基于 [overtrue/easy-sms](#https://github.com/overtrue/easy-sms) 短信发送扩展，增加发送缓存控制。
- 目前支持 `阿里云`、`创蓝`、`华为`、`螺丝帽`、`腾讯云`、`sendcloud`、`赛邮`、`云片`、`容联云通讯`、`网易云信`、`云之讯` 批量发送。

## 目录

- [安装](#%E5%AE%89%E8%A3%85)
- [简单使用](#%E7%AE%80%E5%8D%95%E4%BD%BF%E7%94%A8)
- [SMS 类方法介绍](#sms-%E7%B1%BB%E6%96%B9%E6%B3%95%E4%BB%8B%E7%BB%8D)
    - [setPhone](#setphone)
    - [setType](#settype)
    - [setCode](#setcode)
    - [setMessage](#setmessage)
    - [setGateways](#setgateways)
    - [setInterval](#setinterval)
    - [setValidTime](#setvalidtime)
    - [useCache](#usecache)
    - [send](#send)
    - [getEasySms](#geteasysms)
- [SmsCache 类方法介绍](#smscache-%E7%B1%BB%E6%96%B9%E6%B3%95%E4%BB%8B%E7%BB%8D)
    - [setPhone](#setphone-1)
    - [setType](#settype-1)
    - [setInterval](#setinterval-1)
    - [setValidTime](#setvalidtime-1)
    - [sendCheck](#sendcheck)
    - [send](#send-1)
    - [verify](#verify)
    - [sendInterval](#sendinterval)

- [SmsException 错误码](#smsexception-%E9%94%99%E8%AF%AF%E7%A0%81)


## 安装

```shell
$ composer require lswl/sms
```

## 简单使用

**注意：**

- 使用前需要自行实现一个基于 `Lswl\Sms\Contracts\CacheInterface` 的缓存类
- 使用时需要自行捕获以下异常：
    `Overtrue\EasySms\Exceptions\InvalidArgumentException` **easy-sms 无效参数异常**
    `Overtrue\EasySms\Exceptions\NoGatewayAvailableException` **easy-sms 网关异常**
    `Lswl\Sms\Exceptions\SmsException` **短信相关异常，[错误码](#smsexception-%E9%94%99%E8%AF%AF%E7%A0%81)**
- 关于配置、发送内容请参考 [overtrue/easy-sms](#https://github.com/overtrue/easy-sms)

**发送示例：**

```php
use Lswl\Sms\Sms;

// 实现了 Lswl\Sms\Contracts\CacheInterface 的缓存类
$cache = new xxxCache();

// 配置
$config = [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,

    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'yunpian', 'aliyun',
        ],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => '/tmp/easy-sms.log',
        ],
        'yunpian' => [
            'api_key' => '824f0ff2f71cab52936axxxxxxxxxx',
        ],
        'aliyun' => [
            'access_key_id' => '',
            'access_key_secret' => '',
            'sign_name' => '',
        ],
        //...
    ],
];

$sms = new Sms($cache, $config);

$res = $sms->setPhone(13188888888)
    ->setCode(6379)
    ->setMessage([
        'content'  => '您的验证码为: 6379',
    ])
    ->send();

// input
var_dump($res);
// 下次发送需要等待时间（手机号 => 等待秒数）
// [
//     13188888888 => 60,
// ]
```

**验证示例：**
```php
use Lswl\Sms\Utils\SmsCache;

// 实现了 Lswl\Sms\Contracts\CacheInterface 的缓存类
$cache = new xxxCache();
$smsCache = new SmsCache($cache);

// 验证验证码是否正确，验证失败将会抛出 Lswl\Sms\Exceptions\SmsException 异常
// 验证无类型的验证码
$smsCache->setPhone(13188888888)
    ->verify(123456);

// 验证登录类型的验证码
$smsCache->setPhone(13188888888)
	->setType('login')
    ->verify(123456);
```

**使用验证码示例：**

```php
use Lswl\Sms\Utils\SmsCache;

// 实现了 Lswl\Sms\Contracts\CacheInterface 的缓存类
$cache = new xxxCache();
$smsCache = new SmsCache($cache);

// 使用无类型的验证码
$smsCache->setPhone(13188888888)
    ->useCode();

// 使用登录类型的验证码
$smsCache->setPhone(13188888888)
	->setType('login')
    ->useCode();
```

## `SMS` 类方法介绍

### `setPhone`

> 设置发送短信的手机号（必须）

```js
// 发送给一个手机
setPhone(13188888888)
setPhone(new PhoneNumber(13188888888, 86))
setPhone([
    13188888888,
])

// 发送给多个个手机
setPhone('13188888888,13288888888')
setPhone('13188888888|13288888888', '|')
setPhone([
    13188888888,
    new PhoneNumber(13288888888, 86)
])
```

### `setType`

> 设置发送类型

```js
// 发送类型
setType('login')
```

### `setCode`

> 设置缓存的验证码

```js
// 发送验证码
setCode(123456)
```

### `setMessage`

> 设置发送消息（必须）

```js
// 发送消息
setMessage([
    'content' => 'msg'
])
setMessage(new Message([
    'content' => 'msg'
]))
```

### `setGateways`

> 设置发送网关，不设置使用配置的默认网关

```js
// 发送网关
setGateways([
    'yunpian', 'aliyun',
])
```

### `setInterval`

> 设置验证的发送间隔时间，不设置使用默认值
>
> 达到此次数或大于此次数需等待间隔时间后才能再次发送

```js
// 默认值（次数 => 间隔秒数）
[
    1 => 60,
    2 => 180,
    3 => 600,
]

// 发送间隔时间
setInterval([
    'content' => 'msg'
])
```

### `setValidTime`

> 设置验证码有效期（默认 1800 秒）

```js
// 验证码有效期（秒）
setValidTime(100)
```

### `useCache`

> 是否使用缓存（默认使用）
>
> 使用缓存时会验证发送间隔及记录验证码

```js
// 不使用缓存
useCache(false)
```

### `send`

> 发送消息（默认 false）

```js
// 发送短信
send()

// 使用调试模式（不会真正发送短信）
send(true)
```

### `getEasySms`

> 获取 easySms 实例

```js
// 注册自定义网关
$sms->getEasySms()->extend('mygateway', function($gatewayConfig){
    // $gatewayConfig 来自配置文件里的 `gateways.mygateway`
    return new MyGateway($gatewayConfig);
});
```

## `SmsCache` 类方法介绍



### `setPhone`

> 设置发送短信的手机号（必须）

```js
// 发送手机号
setPhone(13188888888)
```

### `setType`

> 设置发送类型

```js
// 发送类型
setType('login')
```

### `setInterval`

> 设置验证的发送间隔时间，不设置使用默认值
>
> 达到此次数或大于此次数需等待间隔时间后才能再次发送

```js
// 默认值（次数 => 间隔秒数）
[
    1 => 60,
    2 => 180,
    3 => 600,
]

// 发送间隔时间
setInterval([
    'content' => 'msg'
])
```

### `setValidTime`

> 设置验证码有效期（默认 1800 秒）

```js
// 验证码有效期（秒）
setValidTime(100)
```

### `sendCheck`

> 发送检测，不允许发送将会抛出 `Lswl\Sms\Exceptions\SmsException` 异常

```js
// 发送检测
sendCheck()
```

### `send`

> 设置验证码缓存

```js
// 设置验证码缓存
send(123456)
```

### `verify`

> 验证验证码是否正确，验证失败将会抛出 `Lswl\Sms\Exceptions\SmsException` 异常

```js
// 验证验证码是否正确
verify(123456)
```

### `sendInterval`

> 获取发送间隔时间

```js
// 获取发送间隔时间
sendInterval()
```

## `SmsException` 错误码

| 错误码 |       描述       | 数据 |
| :----: | :--------------: | :--: |
|  401   | 手机号格式不正确 |  ['phone' => 13188888888 ] |
| 402 | 发送消息必须 | --- |
| 403 | 发送间隔未到 | ['phone' => 13188888888 , 'interval' => 58] |
| 411 | 无效的验证码 | --- |
| 412 | 验证码不正确 | --- |
| 413 | 发送频繁 | --- |
