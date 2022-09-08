<?php

namespace Mautic\ChannelBundle\Tests\Entity;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\ChannelBundle\Entity\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testMessageUpdatesReflectsInChanges(): void
    {
        $category = new Category();
        $category->setTitle('New Category');
        $category->setAlias('category');
        $category->setBundle('bundle');

        $message = new Message();
        $message->setName('New Message');
        $message->setDescription('random text string for description');
        $message->setCategory($category);
        $message->setPublishDown(new \DateTime());
        $message->setPublishUp(new \DateTime());

        $this->assertIsArray($message->getChanges());
        $this->assertNotEmpty($message->getChanges());
    }
}
