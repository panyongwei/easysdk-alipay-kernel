<?php
/**
 * +----------------------------------------------------------------------
 * | 证书处理
 * +----------------------------------------------------------------------
 * | Copyright (c) 2021 http://www.sunnyos.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Date：2021-09-01 12:36:36
 * | Author: Sunny (admin@mail.sunnyos.com) QQ：327388905
 * +----------------------------------------------------------------------
 */

namespace EasySDK\AlipayKernel\Support;

class CertClient
{
    /**
     * 从证书中提取序列号
     * @param string $certPath
     * @return string
     */
    public function getCertSN(string $certPath): string
    {
        $cert = file_get_contents($certPath);
        $ssl = openssl_x509_parse($cert);
        $SN = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
        return $SN;
    }

    /**
     * 提取根证书序列号
     * @param string $certPath
     * @return string|null
     */
    public function getRootCertSN(string $certPath): ?string
    {
        $cert = file_get_contents($certPath);
        $array = explode("-----END CERTIFICATE-----", $cert);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            $strWithPos = phpversion() < 8 == true ? 'strpos' : 'str_starts_with';
            if ($strWithPos($ssl[$i]['serialNumber'], '0x') === 0) {
                $ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumberHex']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($SN == null) {
                    $SN = md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $SN = $SN . "_" . md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
    }

    /**
     * 0x转高精度数字
     * @param $hex
     * @return int|string
     */
    public function hex2dec($hex): int|string
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    /**
     * 从证书中提取公钥
     * @param string $certPath
     * @return mixed
     */
    public function getPublicKey(string $certPath): mixed
    {
        $cert = file_get_contents($certPath);
        $pkey = openssl_pkey_get_public($cert);
        $keyData = openssl_pkey_get_details($pkey);
        $public_key = str_replace('-----BEGIN PUBLIC KEY-----', '', $keyData['key']);
        return trim(str_replace('-----END PUBLIC KEY-----', '', $public_key));
    }

    /**
     * 数组转字符串
     * @param array $array
     * @return string
     */
    public function array2string(array $array): string
    {
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }
}