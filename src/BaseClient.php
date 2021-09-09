<?php
/**
 * +----------------------------------------------------------------------
 * | 客户端基类
 * +----------------------------------------------------------------------
 * | Copyright (c) 2021 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2021-08-31 04:11:59
 * | Author: Sunny (admin@mail.sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace EasySDK\AlipayKernel;

use EasySDK\AlipayKernel\Exceptions\InvalidArgumentException;
use EasySDK\AlipayKernel\Exceptions\InvalidSignException;
use EasySDK\AlipayKernel\Exceptions\NetworkException;
use EasySDK\AlipayKernel\Exceptions\RuntimeException;
use EasySDK\AlipayKernel\Support\CertClient;
use EasySDK\AlipayKernel\Support\Sign;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class BaseClient
{
    /**
     * 配置信息
     * @var Config
     */
    protected Config $config;

    /**
     * 正式环境网关
     * @var string
     */
    protected string $gatewayUrl = "https://openapi.alipay.com/gateway.do";

    /**
     * 沙箱环境网关
     * @var string
     */
    protected string $gatewayDevUrl = "https://openapi.alipaydev.com/gateway.do";

    /**
     * 应用公钥证书序列号
     * @var string
     */
    protected string $appCertSN;

    /**
     * 支付宝根证书序列号
     * @var string
     */
    protected string $alipayRootCertSN;

    /**
     * 支付宝公钥证书序列号
     * @var string
     */
    protected string $alipayCertSN;

    /**
     * 签名工具类
     * @var Sign
     */
    protected Sign $sign;

    /**
     * 证书工具类
     * @var CertClient
     */
    protected CertClient $certClient;

    /**
     * 容器
     * @var Container
     */
    protected Container $app;

    /**
     * BaseClient constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->config = $app->config;
        $this->sign = $app->sign;
        $this->certClient = $app->cert_client;
        $this->setCertSN();;
    }

    /**
     * 获取签名
     * @param array $params
     * @return string
     * @throws InvalidSignException
     */
    protected function getSign(array $params): string
    {
        $signType = $params['sign_type'] ?? 'RSA2';
        if (!isset($this->config['app_private_key_file']) || empty($this->config['app_private_key_file'])) {
            $privateKey = $this->config['app_private_key'];
        } else {
            $priKey = file_get_contents($this->config['app_private_key_file']);
            $privateKey = openssl_get_privatekey($priKey);
        }
        return $this->sign->generateSign($params, $privateKey, $signType);
    }

    /**
     * 系统参数拼接网关地址
     * @param array $params
     * @return string
     */
    protected function getRequestUrl(array $params): string
    {
        //系统参数放入GET请求串
        $requestUrl = $this->getGatewayUrl() . "?";
        foreach ($params as $key => $val) {
            if ($val != null) {
                $requestUrl .= "$key=" . urlencode($val) . "&";
            }
        }
        return substr($requestUrl, 0, -1);
    }

    /**
     * 获取公共请求参数
     * @param string $method
     * @return array
     */
    protected function getSysParams(string $method = ""): array
    {
        $params = [
            'app_id' => $this->config['app_id'] ?? '',
            'method' => $method,
            'format' => 'json',
            'charset' => $this->config['charset'] ?? 'utf-8',
            'sign_type' => $this->config['sign_type'] ?? 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        ];
        if (isset($this->config['app_auth_token'])) {
            $params['app_auth_token'] = $this->config['app_auth_token'];
        }
        if (isset($this->config['notify_url'])) {
            $params['notify_url'] = $this->config['notify_url'];
        }
        if (isset($this->config['gatewayUrl'])) {
            $this->gatewayUrl = $this->config['gatewayUrl'] ?? $this->getGatewayUrl();
        }
        if (!empty($this->appCertSN)) {
            $params['app_cert_sn'] = $this->appCertSN;
        }
        if ($this->alipayRootCertSN) {
            $params['alipay_root_cert_sn'] = $this->alipayRootCertSN;
        }
        return $params;
    }

    /**
     * 获取证书序列号
     */
    protected function setCertSN()
    {
        // 获取证书序列号
        if (
            isset($this->config['app_public_cert_file']) && !empty($this->config['app_public_cert_file'])
            &&
            isset($this->config['alipay_root_cert_file']) && !empty($this->config['alipay_root_cert_file'])
        ) {
            $this->appCertSN = $this->certClient->getCertSN($this->config['app_public_cert_file']);
            $this->alipayCertSN = $this->certClient->getCertSN($this->config['alipay_public_cert_file']);
            $this->alipayRootCertSN = $this->certClient->getRootCertSN($this->config['alipay_root_cert_file']);
        }
    }

    /**
     * 获取网关地址
     */
    protected function getGatewayUrl(): string
    {
        if ($this->config['debug'] == true) {
            return $this->gatewayDevUrl;
        }
        return $this->gatewayUrl;
    }

    /**
     * 网络请求
     * @throws NetworkException
     */
    protected function request(string $method, array $sysParams, array $apiParams = []): ResponseInterface
    {
        /** @var ClientInterface $httpClient */
        $httpClient = $this->app['http_client'];
        $requestUrl = $this->getRequestUrl($sysParams);
        try {
            return $httpClient->request($method, $requestUrl, array_merge($sysParams, $apiParams));
        } catch (GuzzleException $e) {
            throw new NetworkException(sprintf("网络请求失败：%s", $e->getMessage()));
        }
    }

    /**
     * 应用支付宝公钥证书下载
     */
    protected function getAlipayCert(): string
    {
        $method = "alipay.open.app.alipaycert.download";
        $params['alipay_cert_sn'] = $this->app['cert_client']->getCertSN($this->config['alipay_public_cert_file']);
        $res = $this->post($method, $params);
        $cert = base64_decode($res['alipay_cert_content']);
        return $this->parsePublicCert($cert);
    }

    /**
     * 解析证书获取公钥
     * @param $pubKey
     * @return string
     */
    protected function parsePublicCert($pubKey): string
    {
        $pkey = openssl_pkey_get_public($pubKey);
        $keyData = openssl_pkey_get_details($pkey);
        $publicKey = str_replace('-----BEGIN PUBLIC KEY-----', '', $keyData['key']);
        return trim(str_replace('-----END PUBLIC KEY-----', '', $publicKey));
    }

    /**
     * 验证响应签名
     * @param string $params
     * @param array $sysParams
     * @return mixed
     * @throws InvalidSignException
     * @throws RuntimeException
     */
    protected function checkResponseSign(string $params, array $sysParams): mixed
    {
        // 1、序列化返回数据
        $result = json_decode($params, true);
        $resultKey = sprintf("%s_response", str_replace(".", "_", $sysParams['method']));

        // 2、从获取对应需要的数据
        $res = $result[$resultKey];

        // 3、判断错误码
        if ($res['code'] != 10000) {
            $msg = sprintf(
                "code：%s，msg：%s，sub_code：%s，sub_msg：%s",
                $res['code'], $res['msg'], $res['sub_code'], $res['sub_msg']
            );
            throw new RuntimeException($msg);
        }

        // 4、获取公钥key
        if (!isset($this->config['alipay_public_cert_file']) || empty($this->config['alipay_public_cert_file'])) {
            $publicKey = $this->config['alipay_public_key'];
        } else {
            $pkey = file_get_contents($this->config['alipay_public_cert_file']);
            $pkey = openssl_get_publickey($pkey);
            $publicKey = $this->parsePublicCert($pkey);
        }

        // 5、判断证书是否一致，不一致请求支付宝获取证书
        if (isset($result['alipay_cert_sn'])) {
            if ($this->alipayCertSN != $result['alipay_cert_sn']) {
                $publicKey = $this->getAlipayCert();
            }
        }

        // 6、签名验证
        if ($resultKey != "alipay_open_app_alipaycert_download_response") {
            if (!$this->app['sign']->verify($res, $result['sign'], $publicKey, $sysParams['sign_type'])) {
                throw new InvalidSignException("签名验证失败");
            }
        }
        return $res;
    }

    /**
     * POST请求
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     * @throws NetworkException
     * @throws RuntimeException
     */
    protected function post(string $method, array $params = []): mixed
    {
        if (empty($params) || count($params) <= 0) {
            throw new InvalidArgumentException("请传递参数");
        }

        // 1、获取公共请求参数
        $sysParams = $this->getSysParams($method);
        $sysParams['biz_content'] = json_encode($params);

        // 2、计算签名
        $sysParams['sign'] = $this->getSign($sysParams);

        // 3、进行网络请求
        $response = $this->request("POST", $sysParams, $params);
        $json = $response->getBody();

        // 4、同步返回验签
        return $this->checkResponseSign($json, $sysParams);
    }
}