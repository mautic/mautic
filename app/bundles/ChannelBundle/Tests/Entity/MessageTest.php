<?php

namespace Mautic\ChannelBundle\Tests\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MessageTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @var MockObject|EntityManagerInterface
     */
    protected $em;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testMessageUpdatesReflectsInChanges()
    {
        $category = new Category();
        $category->setTitle('New Category');
        $category->setAlias('category');
        $category->setBundle('bundle');
        $this->em->persist($category);

        $message = new Message();
        $message->setName('New Message');
        $message->setDescription('random text string for description');
        $message->setCategory($category);
        $message->setPublishDown(new \DateTime());
        $message->setPublishUp(new \DateTime());

        $this->em->persist($message);
        $this->assertIsArray($message->getChanges());
        $this->assertNotEmpty($message->getChanges());
        $this->em->flush();
    }
}
