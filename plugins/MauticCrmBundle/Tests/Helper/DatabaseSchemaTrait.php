<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait DatabaseSchemaTrait
{
    /** @var EntityManager */
    private $entityManager;

    protected function getEntityManager()
    {
        if (is_null($this->entityManager)) {
            static::$kernel = static::createKernel();
            static::$kernel->boot();
            $container = static::$kernel->getContainer();

            //setup the request stack
            $request      = Request::createFromGlobals();
            $requestStack = new RequestStack();
            $requestStack->push($request);
            $container->set('request_stack', $requestStack);
            $this->entityManager = $container->get('doctrine')->getManager();
        }

        return $this->entityManager;
    }

    private function createFreshDatabaseSchema(EntityManager $entityManager): void
    {
        $metadata   = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();
        if (!empty($metadata)) {
            $schemaTool->createSchema($metadata);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDownDatabase(EntityManager $entityManager)
    {
        $entityManager->close();
    }
}
