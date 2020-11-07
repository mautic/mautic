<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\RoleData;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\TagModel;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;

class TagControllerTest extends MauticMysqlTestCase
{
    /**
     * @var TagModel
     */
    private $tagModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(
            [RoleData::class, LoadRoleData::class, LoadUserData::class]
        );

        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];

        $this->tagModel = $this->container->get('mautic.lead.model.tag');

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setTag($tagName);
            $this->tagModel->saveEntity($tag);
        }
    }

    /**
     * Get all results without filtering.
     */
    public function testIndexActionWhenNotFiltered(): void
    {
        $this->client->request('GET', '/s/tags');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('tag1', $clientResponseContent, 'The return must contain tag1');
        $this->assertStringContainsString('tag2', $clientResponseContent, 'The return must contain tag2');
    }

    /**
     * Get results with filtering.
     */
    public function testIndexActionWhenFiltered(): void
    {
        $this->client->request('GET', '/s/tags?search=tag1');
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringContainsString('tag1', $clientResponseContent, 'The return must contain tag1');
        $this->assertStringNotContainsString('tag2', $clientResponseContent, 'The return must not contain tag2');
    }

    public function testTagDeletion(): void
    {
        $this->client->request('POST', '/s/tags/delete/1');
        $clientResponse         = $this->client->getResponse();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertSame($this->tagModel->getRepository()->find(1), null, 'Assert that tag is deleted');
    }
}
