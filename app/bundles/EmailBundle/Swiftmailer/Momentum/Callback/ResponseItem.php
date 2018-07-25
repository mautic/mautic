<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @param array $item
     *
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
     * @return null|string
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
     * @return null|string
     */
    public function getStatHash()
    {
        return $this->statHash;
    }
}
