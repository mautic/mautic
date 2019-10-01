<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\DAO;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

class ObjectIdsDAOTest extends \PHPUnit_Framework_TestCase
{
    public function testWorkflow(): void
    {
        $objectIdsDAO = ObjectIdsDAO::createFromCliOptions(
            [
                'contact:123',
                'contact:124',
                'company:12',
                'company:13',
                'Lead:sowiern',
                'Lead:sowie4n',
            ]
        );

        $objectIdsDAO->addObjectId('company', '234');

        $this->assertSame(['123', '124'], $objectIdsDAO->getObjectIdsFor('contact'));
        $this->assertSame(['12', '13', '234'], $objectIdsDAO->getObjectIdsFor('company'));
        $this->assertSame(['sowiern', 'sowie4n'], $objectIdsDAO->getObjectIdsFor('Lead'));

        $this->expectException(ObjectNotFoundException::class);
        $objectIdsDAO->getObjectIdsFor('Unicorn');
    }
}
