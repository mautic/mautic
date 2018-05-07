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

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class EmailHeaderRepository.
 */
final class EmailHeaderRepository extends CommonRepository implements EmailHeaderRepositoryInterface
{
    /**
     * @param Email  $email
     * @param string $name
     *
     * @return EmailHeader|null
     */
    public function getHeader(Email $email, $name)
    {
        /** @var EmailHeader|null $header */
        $header = $this->findOneBy([
            'email_id' => $email->getId(),
            'name'     => $name,
        ]);

        return $header;
    }

    /**
     * @param Email $email
     *
     * @return EmailHeader[]
     */
    public function getEmailHeaders(Email $email)
    {
        return $this->findBy([
            'email_id' => $email->getId(),
        ]);
    }
}
