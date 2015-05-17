<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Swiftmailer\Message\MauticMessage;

/**
 * Class AbstractBatchHttpTransport
 */
abstract class AbstractBatchHttpTransport extends AbstractBatchArrayTransport implements \Swift_Transport, InterfaceBatchTransport
{

    /**
     * @var
     */
    private $dispatcher;

    /**
     * @var
     */
    private $username;

    /**
     * @var
     */
    private $password;

    /**
     * @var \Mautic\CoreBundle\Swiftmailer\Message\MauticMessage
     */
    protected $message;

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
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return int
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

                    return ($count - count($failed));
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
     * POST payload to API endpoint
     *
     * @param array $settings
     *
     * @return array of failed addresses
     * @throws \Swift_TransportException
     */
    protected function post($settings = array())
    {
        $payload  = $this->getPayload();
        $headers  = $this->getHeaders();
        $endpoint = $this->getApiEndpoint();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        foreach ($settings as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        $response = curl_exec($ch);
        $info     = curl_getinfo($ch);

        if(curl_error($ch)) {
            throw new \Swift_TransportException("API call to $endpoint failed: " . curl_error($ch));
        }

        curl_close($ch);

        return $this->handlePostResponse($response, $info);
    }

    /**
     * Return an array of headers for the POST
     *
     * @return array
     */
    abstract protected function getHeaders();

    /**
     * Return the payload for the POST
     *
     * @return mixed
     */
    abstract protected function getPayload();

    /**
     * Return the URL for the API endpoint
     *
     * @return string
     */
    abstract protected function getApiEndpoint();

    /**
     * Analyze the output of the API response and return any addresses that FAILED to send
     *
     * @param $response
     * @param $curlInfo
     *
     * @throws \Swift_TransportException
     * @return array
     */
    abstract protected function handlePostResponse($response, $curlInfo);

    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return false;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {

    }

    /**
     * Stop this Transport mechanism.
     */
    public function stop()
    {

    }

    /**
     * Register a plugin in the Transport.
     *
     * @param \Swift_Events_EventListener $plugin
     */
    public function registerPlugin(\Swift_Events_EventListener $plugin)
    {
        $this->getDispatcher()->bindEventListener($plugin);
    }

    /**
     * @return \Swift_Events_SimpleEventDispatcher
     */
    protected function getDispatcher()
    {
        if ($this->dispatcher == null) {
            $this->dispatcher = new \Swift_Events_SimpleEventDispatcher();
        }

        return $this->dispatcher;
    }

    /**
     * Get the metadata from a MauticMessage
     */
    public function getMetadata()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : array();
    }
}