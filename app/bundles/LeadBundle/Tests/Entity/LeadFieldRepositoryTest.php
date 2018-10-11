<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\LeadFieldRepository;

class LeadFieldRepositoryTest extends \PHPUnit_Framework_TestCase
{
    private $mockConnection;
    private $em;
    private $cm;

    /**
     * @var EmailRepository
     */
    private $repo;

    protected function setUp()
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->mockConnection = $this->createMock(Connection::class);
        $this->em             = $this->createMock(EntityManager::class);
        $this->cm             = $this->createMock(ClassMetadata::class);
        $this->repo           = new LeadFieldRepository($this->em, $this->cm);

        $this->mockConnection->method('createQueryBuilder')
            ->willReturnCallback(
                function () {
                    return new QueryBuilder($this->mockConnection);
                }
            );

        $this->mockConnection->method('getExpressionBuilder')
            ->willReturnCallback(
                function () {
                    return new ExpressionBuilder($this->mockConnection);
                }
            );

        $this->mockConnection->method('quote')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $this->em->method('getConnection')
            ->willReturn($this->mockConnection);
    }
}
