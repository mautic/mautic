<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity\EmailHeader;

use Mautic\EmailBundle\Entity\Email;

/**
 * Interface EmailHeaderRepositoryInterface.
 */
interface EmailHeaderRepositoryInterface
{
    /**
     * @param Email  $email
     * @param string $name
     *
     * @return EmailHeader|null
     */
    public function getHeader(Email $email, $name);

    /**
     * @param Email $email
     *
     * @return EmailHeader[]
     */
    public function getEmailHeaders(Email $email);
}
