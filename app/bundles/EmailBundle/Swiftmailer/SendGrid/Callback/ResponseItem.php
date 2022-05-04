<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Callback;

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

    private ?int $channel = null;

    /**
     * @throws ResponseItemException
     */
    public function __construct(array $item)
    {
        if (empty($item['email'])) {
            throw new ResponseItemException();
        }
        if (isset($item['mautic_metadata'])) {
            $item['mautic_metadata'] = unserialize($item['mautic_metadata']);
        }
        $this->email     = $item['email'];
        $this->reason    = !empty($item['reason']) ? $item['reason'] : null;
        $this->dncReason = CallbackEnum::convertEventToDncReason($item['event']);
        $this->channel   = $item['mautic_metadata'][$this->email]['emailId'] ?? null;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
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

    public function getChannel(): ?int
    {
        return $this->channel;
    }
}
