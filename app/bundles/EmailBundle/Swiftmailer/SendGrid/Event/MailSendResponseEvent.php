<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Event;

use SendGrid\Mail;
use SendGrid\Response;
use Swift_Mime_SimpleMessage;

class MailSendResponseEvent extends GetMailMessageEvent
{
    /** @var Response */
    private $response;

    /**
     * Constructor.
     */
    public function __construct(Response $response, Mail $mail, Swift_Mime_SimpleMessage $message)
    {
        $this->response = $response;

        parent::__construct($mail, $message);
    }

    /**
     * Get Response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
