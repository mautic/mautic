<?php

namespace Mautic\ApiBundle\Entity\oAuth2;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<AccessToken>
 */
final class AccessTokenRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'at';
    }
}
