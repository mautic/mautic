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

    /**
     * @throws ResponseItemException
     */
    public function __construct(array $item)
    {
        if (empty($item['email'])) {
            throw new ResponseItemException();
        }
        $this->email     = $item['email'];
        $this->reason    = !empty($item['reason']) ? $item['reason'] : null;
        $this->dncReason = CallbackEnum::convertEventToDncReason($item['event']);
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
}
