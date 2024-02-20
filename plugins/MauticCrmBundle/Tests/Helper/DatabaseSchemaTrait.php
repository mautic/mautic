<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait DatabaseSchemaTrait
{
    private EntityManager $entityManager;

    protected function getEntityManager(): EntityManager
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

    protected function tearDownDatabase(EntityManager $entityManager): void
    {
        $entityManager->close();
    }
}
