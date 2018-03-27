<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @param array $item
     *
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
