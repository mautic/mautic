<?php

namespace MauticPlugin\MauticTagManagerBundle\Tests\Controller;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\RoleData;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\TagModel;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

class TagControllerTest extends MauticMysqlTestCase
{
    /**
     * @var TagModel
     */
    private $tagModel;

    protected function setUp(): void
    {
        parent::setUp();

        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];

        $this->tagModel = self::$container->get('mautic.lead.model.tag');

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
        $tagId = $this->tagModel->getRepository()->getRows(1)['results'][0]['id'];
        $this->client->request('POST', '/s/tags/delete/'.$tagId);
        $clientResponse         = $this->client->getResponse();

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertSame($this->tagModel->getRepository()->find($tagId), null, 'Assert that tag is deleted');
    }

    /**
     * Get tag's edit page.
     */
    public function testEditActionCompany(): void
    {
        $tag = $this->tagModel->getRepository()->getRows(1)['results'][0];

        $this->client->request('GET', '/s/tags/edit/'.$tag['id']);
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertStringContainsString('Edit tag: '.$tag['tag'], $clientResponseContent, 'The return must contain \'Edit tag\' text');
    }

    /**
     * Get tag's create page.
     */
    public function testNewActionCompany(): void
    {
        $this->client->request('GET', '/s/tags/new/');
        $clientResponse         = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
    }
}
