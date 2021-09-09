<?php
/**
 * +----------------------------------------------------------------------
 * | 证书操作服务提供
 * +----------------------------------------------------------------------
 * | Copyright (c) 2021 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2021-09-06 03:37:27
 * | Author: Sunny (admin@mail.sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace EasySDK\AlipayKernel\Providers;


use EasySDK\AlipayKernel\Support\CertClient;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class CertClientProvider implements ServiceProviderInterface
{

    public function register(Container $pimple)
    {
        $pimple['cert_client'] = function ($app) {
            return new CertClient();
        };
    }
}