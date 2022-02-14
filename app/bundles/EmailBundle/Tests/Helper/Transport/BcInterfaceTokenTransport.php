<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class BcInterfaceTokenTransport implements TransportInterface
{
    private $fromAddresses = [];
    private $metadatas     = [];
    private $validate      = false;
    private $numberToFail;
    private $message;

    /**
     * BatchTransport constructor.
     *
     * @param bool $validate
     */
    public function __construct($validate = false, $numberToFail = 1)
    {
        $this->validate      = $validate;
        $this->numberToFail  = (int) $numberToFail;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $address = null;
        if ($message instanceof Email) {
            $address = !empty($message->getFrom()) ? $message->getFrom()[0]->getAddress() : null;
        }

        $this->message         = $message;
        $this->fromAddresses[] = $address;
        $this->metadatas[]     = $this->getMetadata();

        return null;
    }

    public function getFromAddresses(): array
    {
        return $this->fromAddresses;
    }

    public function getMetadatas(): array
    {
        return $this->metadatas;
    }

    public function getMetadata(): array
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : [];
    }

    public function __toString(): string
    {
        return 'BcInterface';
    }
}
