<?php
/**
 * +----------------------------------------------------------------------
 * |
 * +----------------------------------------------------------------------
 * | Copyright (c) 2021 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2021-08-31 04:22:43
 * | Author: Sunny (admin@mail.sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace EasySDK\AlipayKernel\Providers;


use EasySDK\AlipayKernel\Support\Sign;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class SignServiceProvider implements ServiceProviderInterface
{

    public function register(Container $pimple)
    {
        $pimple['sign'] = function ($app) {
            return new Sign();
        };
    }
}