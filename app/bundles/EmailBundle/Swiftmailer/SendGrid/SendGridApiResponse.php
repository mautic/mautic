<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Swiftmailer\Exception\SendGridBadLoginException;
use SendGrid\Response;

class SendGridApiResponse
{
    /**
     * @param Response $response
     *
     * @throws SendGridBadLoginException
     */
    public function checkResponse(Response $response)
    {
        $statusCode = $response->statusCode();

        if ($statusCode === 401) {
            throw new SendGridBadLoginException();
        }
    }
}
