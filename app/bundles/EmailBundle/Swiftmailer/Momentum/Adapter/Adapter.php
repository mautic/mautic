<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Adapter;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use SparkPost\SparkPostPromise;

/**
 * Class Adapter.
 */
final class Adapter implements AdapterInterface
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * Adapter constructor.
     *
     * @param string $host
     * @param string $apiKey
     */
    public function __construct($host, $apiKey)
    {
        $this->host   = $host;
        $this->apiKey = $apiKey;
    }

    /**
     * @param TransmissionDTO $transmissionDTO
     *
     * @return SparkPostPromise
     */
    public function createTransmission(TransmissionDTO $transmissionDTO)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($transmissionDTO));
        curl_setopt($curl, CURLOPT_URL, 'http://'.$this->host.'/v1/transmissions');
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => $this->apiKey,
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($curl);
        curl_close($curl);
        echo $result;
        exit;
    }
}
