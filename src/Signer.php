<?php
namespace STS\Sdk;

/**
 * Class Signer
 * @package RC\Sdk
 */
class Signer
{
    /**
     * @param $method
     * @param $path
     * @param $payload
     *
     * @return string
     */
    public function prepareSignatureData($method, $path, $payload)
    {
        $hashed = hash("sha256", $payload);
        return $method . "\n" . $path . "\n" . $hashed . "\n" . time();
    }

    /**
     * @param $signingKey
     * @param $data
     *
     * @return string
     */
    public function getSignature($signingKey, $data)
    {
        return base64_encode(hash_hmac('sha256', (string)$data, $signingKey, true));
    }

    /**
     * @param     $signature
     * @param     $signingKey
     * @param     $method
     * @param     $path
     * @param     $payload
     * @param int $timeframe
     *
     * @return bool
     */
    public function isSignatureValid($signature, $signingKey, $method, $path, $payload, $timeframe = 300)
    {
        $start = time();
        $hashed = hash("sha256", $payload);
        for ($time = 0; $time < $timeframe; $time++) {
            $data = $method . "\n" . $path . "\n" . $hashed . "\n" . ($start + $time);
            if ($signature == $this->getSignature($signingKey, $data)) {
                return true;
            }
            $data = $method . "\n" . $path . "\n" . $hashed . "\n" . ($start - $time);
            if ($signature == $this->getSignature($signingKey, $data)) {
                return true;
            }
        }
        return false;
    }
}