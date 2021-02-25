<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;

class FieldModelTest extends TestCase
{
    public function testGenerateAlias()
    {
        $connection = $this->createMock(Connection::class);

        $platform = new class() {
            public function getReservedKeywordsList()
            {
                return new class() {
                    public function isKeyword()
                    {
                    }
                };
            }

            public function isKeyword()
            {
            }
        };

        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $leadFieldModel = $this->createMock(\Mautic\LeadBundle\Model\FieldModel::class);
        $fieldModel     = new FieldModel($leadFieldModel);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $fieldModel->setEntityManager($entityManager);

        $aliases = [
            'existed_alias',
            'existed_alias_with_space',
        ];

        $strings = [
            'existed_alias1'            => 'existed alias',
            'not_existed'               => 'not existed',
            'existed_alias_with_space1' => 'existed alias with space',
            'alias_test'                => 'alias test',
        ];

        foreach ($strings as $expected => $string) {
            $alias = $fieldModel->generateAlias($string, $aliases);
            $this->assertEquals($expected, $alias);
        }
    }
}
