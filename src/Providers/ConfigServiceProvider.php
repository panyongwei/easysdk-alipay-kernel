<?php
/**
 * +----------------------------------------------------------------------
 * | 注册配置服务到容器
 * +----------------------------------------------------------------------
 * | Copyright (c) 2021 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2021-08-31 03:46:42
 * | Author: Sunny (admin@mail.sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace EasySDK\AlipayKernel\Providers;

use EasySDK\AlipayKernel\Config;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{

    public function register(Container $pimple)
    {
        $pimple['config'] = function ($app) {
            return new Config($app->getConfig());
        };
    }
}