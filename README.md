## 前言

工作这些年接入过很多次大厂的API，使用过程中终会遇到点奇奇怪怪的问题。

在阅读了市面上现有的很多SDK源码之后给我一个启发，我想让更多大厂难用的API变得更简单易用。

于是EasySdk就在这样的设想下横空出世了，这是EasySdk支付宝的核心库，分开各个大分类的分支，各位同学可以直接fork本仓库实现自己的SDK。

QQ交流群：57914282

## 特点

- 简单易用使用
- 隐藏开发者不需要关注的细节
- 支持开发者替换自己的模块

## 运行环境

- PHP 7.2.5+
- composer

## 依赖库

- "pimple/pimple": "^3.4"
- "guzzlehttp/guzzle": "^7.3"
- "ext-mbstring": "*"

## 安装

```shell
composer require panyongwei/easysdk-alipay-kernel:~1.0.0 -vvv
```

## 基于 easysdk-alipay-kernel 开发自己的类库

### 1、修改 composer.json 文件家在支付宝核心库

```json
"require": {
  "sphynx/easysdk-alipay-kernel": "dev-master"
},
```

### 2、库目录

```
├── composer.json                   composer.json管理
├── src                             库源码
    ├── AlipayPay.php               入口文件通过这个文件去 Application.php 注册服务和调用
    ├── Application.php             服务提供者文件，负责注册和调用不同模块的服务对象             
    ├── F2fpay                      面对面支付实例
        ├── Client.php              面对面支付api对接代码
        └── ServiceProvider.php     面对面支付宝服务提供者注册代码
└── vendor
```

#### AlipayPay.php 代码

```php
namespace EasySDK\AlipayPay;
use EasySDK\AlipayKernel\ServiceContainer;
/**
 * @method static Application Foctory(array $config)
 */
class AlipayPay extends ServiceContainer
{
    public static function make(string $name, array $config)
    {
        // 这里的命名空间是 Application.php 所在的命名空间
        $application = "\\EasySDK\\AlipayPay\\Application";
        return new $application($config);
    }

    public static function __callStatic($name, $arguments)
    {
        return self::make($name, ...$arguments);
    }
}
```

#### Application.php 代码

```php
namespace EasySDK\AlipayPay;
use EasySDK\AlipayKernel\ServiceContainer;
/**
 * @property \EasySDK\AlipayPay\F2fpay\Client $f2fpay 付款码支付
 * @property \EasySDK\AlipayPay\Pc\Client $pc 电脑网站支付
 */
class Application extends ServiceContainer
{
    /**
     * 服务注册
     */
    protected array $providers = [
        \EasySDK\AlipayPay\F2fpay\ServiceProvider::class,
        \EasySDK\AlipayPay\Pc\ServiceProvider::class,
    ];

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this['base'], $name], $arguments);
    }
}
```

#### F2fpay/Client.php 代码

```php
namespace EasySDK\AlipayPay\F2fpay;
use EasySDK\AlipayKernel\Exceptions\InvalidArgumentException;
use EasySDK\AlipayKernel\Exceptions\InvalidSignException;
use EasySDK\AlipayKernel\Exceptions\NetworkException;
use EasySDK\AlipayKernel\BaseClient;

class Client extends BaseClient
{
    /**
     * 统一收单交易支付接口
     * 参数参考：https://opendocs.alipay.com/apis/api_1/alipay.trade.precreate
     * @param array $params
     * @return mixed
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     * @throws NetworkException
     */
    public function precreate(array $params): mixed
    {
        $method = "alipay.trade.precreate";
        return $this->post($method, $params);
    }
}
```

$params 参数是支付宝的请求参数数组，调用 BaseClient 里面的 post 方法即可，签名和验签都已经在 BaseClient 实现。

QQ交流群：57914282