<?php
/**
 * +----------------------------------------------------------------------
 * | 签名助手类
 * +----------------------------------------------------------------------
 * | Copyright (c) 2021 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2021-08-31 04:22:13
 * | Author: Sunny (admin@mail.sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace EasySDK\AlipayKernel\Support;

use EasySDK\AlipayKernel\Exceptions\InvalidSignException;

class Sign
{
    /**
     * 生成签名
     * @param array $params 参与签名参数
     * @param string $privateKey
     * @param string $signType 签名方式
     * @return string
     * @throws InvalidSignException
     */
    public function generateSign(array $params, string $privateKey, string $signType = 'RSA2'): string
    {
        if ($this->checkEmpty($privateKey)) {
            throw new InvalidSignException("请传递生成签名的私钥");
        }
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        return $this->sign($this->getSignContent($params), $signType, $privateKey);
    }

    /**
     * 进行签名
     * @param $data
     * @param string $signType
     * @param string $privateKey
     * @return string
     */
    public function sign($data, string $signType, string $privateKey): string
    {
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $privateKey);
        }
        return base64_encode($sign);
    }

    /**
     * 验证签名
     * @param array $data
     * @param string $sign
     * @param string $alipayPublicKey
     * @param string $signType
     * @return bool
     * @throws InvalidSignException
     */
    public function verify(array $data, string $sign, string $alipayPublicKey, string $signType = 'RSA2'): bool
    {
        if ($this->checkEmpty($alipayPublicKey)){
            throw new InvalidSignException("请传递支付宝的公钥");
        }
        $alipayPublicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($alipayPublicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $data = json_encode($data);
        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $alipayPublicKey, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $alipayPublicKey);
        }
        return $result;
    }

    /**
     * 获取签名内容
     * @param $params
     * @return string
     */
    public function getSignContent($params): string
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value): bool
    {
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
}