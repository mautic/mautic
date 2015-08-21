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
 * Class AbstractBatchTransport
 */
abstract class AbstractTokenSmtpTransport extends \Swift_SmtpTransport implements InterfaceTokenTransport
{
    /**
     * @var \Swift_Mime_Message
     */
    protected $message;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return int
     * @throws \Exception
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->message = $message;

        $this->prepareMessage();

        return parent::send($message, $failedRecipients);
    }

    /**
     * Do whatever is necessary to $this->message in order to deliver a batched payload. i.e. add custom headers, etc
     *
     * @return void
     */
    abstract protected function prepareMessage();

    /**
     * Get the metadata from a MauticMessage
     */
    public function getMetadata()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : array();
    }

    /**
     * @param MauticFactory $factory
     */
    public function setMauticFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }
}