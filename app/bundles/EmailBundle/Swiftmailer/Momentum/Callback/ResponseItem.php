<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Callback;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Exception\ResponseItemException;

class ResponseItem
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var int
     */
    private $dncReason;

    /**
     * @var string
     */
    private $statHash;

    /**
     * @throws ResponseItemException
     */
    public function __construct(array $item)
    {
        if (empty($item['rcpt_to'])) {
            throw new ResponseItemException();
        }
        $this->email     = $item['rcpt_to'];
        $this->dncReason = CallbackEnum::convertEventToDncReason($item['type']);
        $this->reason    = CallbackEnum::getDncComments($item['type'], $item);
        $this->statHash  = (!empty($item['rcpt_meta']['hashId'])) ? $item['rcpt_meta']['hashId'] : null;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return int
     */
    public function getDncReason()
    {
        return $this->dncReason;
    }

    /**
     * @return string|null
     */
    public function getStatHash()
    {
        return $this->statHash;
    }
}
