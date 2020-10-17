<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Mailgun;

use Mailgun\Exception\HttpClientException;
use Mautic\EmailBundle\Swiftmailer\SwiftmailerFacadeInterface;
use Monolog\Logger;

class MailgunFacade implements SwiftmailerFacadeInterface
{
    /**
     * @var MailgunWrapper
     */
    private $mailgunWrapper;

    /**
     * @var MailgunMessage
     */
    private $mailgunMessage;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        MailgunWrapper $mailgunWrapper,
        MailgunMessage $mailgunMessage,
        Logger $logger
    ) {
        $this->mailgunWrapper     = $mailgunWrapper;
        $this->mailgunMessage     = $mailgunMessage;
        $this->logger             = $logger;
    }

    /**
     * @throws \Swift_TransportException
     */
    public function send(\Swift_Mime_SimpleMessage $message)
    {
        $mail     = $this->mailgunApiMessage->getMessage($message);
        $response = $this->mailgunWrapper->send($mail);
    }

    public function checkConnection(string $domain)
    {
        try {
            $response = $this->mailgunWrapper->checkConnection($domain);

            return 'active' == $response->getDomain()->getState();
        } catch (HttpClientException $e) {
            $this->logger->addError($e->getMessage());
            throw new \Swift_TransportException($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
            throw new \Swift_TransportException($e->getMessage());
        }
    }
}
