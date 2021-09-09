<?php
/**
 * +----------------------------------------------------------------------
 * | 支付宝服务容器
 * +----------------------------------------------------------------------
 * | Copyright (c) 2021 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2021-08-31 03:34:45
 * | Author: Sunny (admin@mail.sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace EasySDK\AlipayKernel;

use EasySDK\AlipayKernel\Exceptions\RuntimeException;
use EasySDK\AlipayKernel\Providers\CertClientProvider;
use EasySDK\AlipayKernel\Providers\ConfigServiceProvider;
use EasySDK\AlipayKernel\Providers\HttpClientServiceProvider;
use EasySDK\AlipayKernel\Providers\SignServiceProvider;
use Pimple\Container;

class ServiceContainer extends Container
{
    /**
     * 服务提供者
     * @var array
     */
    protected array $providers = [];

    /**
     * 用户配置
     * @var array
     */
    protected array $config = [];

    /**
     * 默认配置
     * @var array
     */
    protected array $defaultConfig = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->registerProviders($this->getProviders());
        parent::__construct($config);
    }

    /**
     * 获取服务提供者
     * @return array
     */
    public function getProviders(): array
    {
        return array_merge(
            $this->providers,
            [
                ConfigServiceProvider::class,
                HttpClientServiceProvider::class,
                SignServiceProvider::class,
                CertClientProvider::class,
            ]
        );
    }

    /**
     * 注册服务提供者到容器
     * @param array $providers
     */
    public function registerProviders(array $providers)
    {
        foreach ($providers as $provider) {
            $this->register(new $provider);
        }
    }

    /**
     * 获取配置
     * @return array
     */
    public function getConfig(): array
    {
        return array_merge($this->config, $this->defaultConfig);
    }

    /**
     * 重新绑定对象到容器
     * @param string $key
     * @param $val
     */
    public function rebind(string $key, $val)
    {
        $this->offsetUnset($key);
        $this->offsetSet($key, $val);
    }

    /**
     * 从容器获取内容
     * @param $name
     * @return mixed
     * @throws RuntimeException
     */
    public function __get($name)
    {
        if (isset($this[$name])) {
            return $this[$name];
        }
        throw new RuntimeException(sprintf("%s does not exist", $name));
    }
}