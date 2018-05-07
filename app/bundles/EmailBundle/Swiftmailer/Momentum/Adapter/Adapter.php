<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Adapter;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use SparkPost\SparkPost;
use SparkPost\SparkPostPromise;

/**
 * Class Adapter.
 */
final class Adapter implements AdapterInterface
{
    /**
     * @var SparkPost
     */
    private $momentumSparkpost;

    /**
     * Adapter constructor.
     *
     * @param SparkPost $momentumSparkpost
     */
    public function __construct(SparkPost $momentumSparkpost)
    {
        $this->momentumSparkpost   = $momentumSparkpost;
    }

    /**
     * @param TransmissionDTO $transmissionDTO
     *
     * @return SparkPostPromise
     */
    public function createTransmission(TransmissionDTO $transmissionDTO)
    {
        return $this->momentumSparkpost->transmissions->post(json_decode(json_encode($transmissionDTO), true));
        /*$curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($transmissionDTO));
        curl_setopt($curl, CURLOPT_URL, 'http://'.$this->host.'/v1/transmissions');
        $headers   = [];
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: '.$this->apiKey;
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($curl);
        curl_close($curl);
        echo $result;
        exit;*/
    }
}
