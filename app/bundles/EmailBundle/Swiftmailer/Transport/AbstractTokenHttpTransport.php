<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

/**
 * Class AbstractTokenHttpTransport.
 */
abstract class AbstractTokenHttpTransport extends AbstractTokenArrayTransport implements \Swift_Transport, TokenTransportInterface
{
    /**
     * @var
     */
    private $username;

    /**
     * @var
     */
    private $password;

    /**
     * @var
     */
    private $apiKey;

    /**
     * Return an array of headers for the POST.
     *
     * @return array
     */
    abstract protected function getHeaders();

    /**
     * Return the payload for the POST.
     *
     * @return mixed
     */
    abstract protected function getPayload();

    /**
     * Return the URL for the API endpoint.
     *
     * @return string
     */
    abstract protected function getApiEndpoint();

    /**
     * Analyze the output of the API response and return any addresses that FAILED to send.
     *
     * @param $response
     * @param $curlInfo
     *
     * @throws \Swift_TransportException
     *
     * @return array
     */
    abstract protected function handlePostResponse($response, $curlInfo);

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param mixed $apiKey
     *
     * @return AbstractTokenHttpTransport
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return int
     *
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->message = $message;

        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->getDispatcher()->createSendEvent($this, $message)) {
            $this->getDispatcher()->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        $count = (
            count((array) $this->message->getTo())
            + count((array) $this->message->getCc())
            + count((array) $this->message->getBcc())
        );

        // Post to API endpoint
        try {
            $failed = $this->post();

            if ($evt) {
                if (!empty($failed)) {
                    $failedRecipients = array_merge($failedRecipients, $failed);
                    $evt->setResult(\Swift_Events_SendEvent::RESULT_FAILED);
                    $evt->setFailedRecipients($failedRecipients);
                    $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');

                    $message->generateId();

                    return $count - count($failed);
                } else {
                    $evt->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
                    $evt->setFailedRecipients($failedRecipients);
                    $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
                }
            }
        } catch (\Swift_TransportException $e) {
            $failedRecipients = array_merge(
                $failedRecipients,
                array_keys((array) $this->message->getTo()),
                array_keys((array) $this->message->getCc()),
                array_keys((array) $this->message->getBcc())
            );

            if ($evt) {
                $evt->setResult(\Swift_Events_SendEvent::RESULT_FAILED);
                $evt->setFailedRecipients($failedRecipients);
                $this->getDispatcher()->dispatchEvent($evt, 'sendPerformed');
            }

            $message->generateId();

            throw $e;
        }

        return $count;
    }

    /**
     * POST payload to API endpoint.
     *
     * @param array $settings
     *
     * @return array of failed addresses
     *
     * @throws \Swift_TransportException
     */
    protected function post($settings = [])
    {
        $payload  = empty($settings['payload']) ? $this->getPayload() : $settings['payload'];
        $headers  = empty($settings['headers']) ? $this->getHeaders() : $settings['headers'];
        $endpoint = empty($settings['url']) ? $this->getApiEndpoint() : $settings['url'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($curl['curl_options'])) {
            foreach ($settings['curl_options'] as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }

        $response = curl_exec($ch);
        $info     = curl_getinfo($ch);

        if (curl_error($ch)) {
            $this->throwException("API call to $endpoint failed: ".curl_error($ch));
        }

        curl_close($ch);

        return $this->handlePostResponse($response, $info);
    }
}
