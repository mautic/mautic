<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\LeadBundle\Model\TagModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class TagControllerTest extends MauticMysqlTestCase
{
    public const USERNAME = 'jhony';
    /**
     * @var TagRepository
     */
    private $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];

        /** @var TagModel $tagModel */
        $tagModel            = static::getContainer()->get('mautic.lead.model.tag');
        $this->tagRepository = $tagModel->getRepository();

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setTag($tagName);
            $tagModel->saveEntity($tag);
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

        $this->assertResponseIsSuccessful();
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('tag1', $clientResponseContent, 'The return must contain tag1');
        $this->assertStringNotContainsString('tag2', $clientResponseContent, 'The return must not contain tag2');
    }

    public function testIndexActionWhenFilteredByDescription(): void
    {
        $matchingTag = $this->tagRepository->findOneBy(['tag' => 'tag1']);
        \assert($matchingTag instanceof Tag);
        $matchingTag->setDescription('Contains the test keyword.');
        $this->tagRepository->saveEntity($matchingTag, false);

        $otherTag = $this->tagRepository->findOneBy(['tag' => 'tag2']);
        \assert($otherTag instanceof Tag);
        $otherTag->setDescription('No related content.');
        $this->tagRepository->saveEntity($otherTag);

        $this->client->request('GET', '/s/tags?search=test');
        $clientResponse        = $this->client->getResponse();
        $clientResponseContent = $clientResponse->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('tag1', $clientResponseContent, 'The return must contain the tag whose description matches.');
        $this->assertStringNotContainsString('tag2', $clientResponseContent, 'The return must not contain unrelated tags.');
    }

    public function testTagDeletion(): void
    {
        $tagId = $this->tagRepository->findOneBy([])->getId();
        $this->client->request('POST', '/s/tags/delete/'.$tagId);
        $this->assertResponseIsSuccessful();
        $this->assertSame($this->tagRepository->find($tagId), null, 'Assert that tag is deleted');
    }

    public function testTagDeletionRemovesContactAssociations(): void
    {
        $tag = $this->tagRepository->findOneBy([]);
        \assert($tag instanceof Tag);

        $contact = new Lead();
        $contact->setEmail('tagged-contact@example.com');
        $contact->addTag($tag);
        $this->em->persist($contact);
        $this->em->flush();

        $tagId = (int) $tag->getId();

        Assert::assertSame(1, $this->countLeadTagAssociations($tagId));

        $this->client->request('POST', '/s/tags/delete/'.$tagId);
        $this->assertResponseIsSuccessful();
        $this->assertSame($this->tagRepository->find($tagId), null, 'Assert that tag is deleted');
        Assert::assertSame(0, $this->countLeadTagAssociations($tagId));
    }

    /**
     * Get tag's view page.
     */
    public function testViewAction(): void
    {
        $tag = $this->tagRepository->findOneBy([]);

        $this->client->request('GET', '/s/tags/view/'.$tag->getId());
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($tag->getTag(), $clientResponseContent, 'The return must contain tag');
    }

    public function testViewActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', '/s/tags/view/99999');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    private function countLeadTagAssociations(int $tagId): int
    {
        return (int) $this->em->getConnection()->createQueryBuilder()
            ->select('COUNT(*)')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref')
            ->where('tag_id = :tagId')
            ->setParameter('tagId', $tagId)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Get tag's edit page.
     */
    public function testEditAction(): void
    {
        $TagName = 'Test tag';
        $tag     = $this->tagRepository->findOneBy([]);

        $crawler                = $this->client->request('GET', '/s/tags/edit/'.$tag->getId());
        $clientResponse         = $this->client->getResponse();
        $clientResponseContent  = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Edit tag: '.$tag->getTag(), $clientResponseContent, 'The return must contain \'Edit tag\' text');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['tag_entity[tag]']->setValue($TagName);
        $this->client->submit($form);

        $this->assertSame(1, $this->tagRepository->count(['tag' => $TagName]));
    }

    public function testEditActionNotFound(): void
    {
        $this->client->followRedirects(false);
        $this->client->request('GET', '/s/tags/edit/99999');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isRedirection(), 'Must be redirect response.');
    }

    /**
     * Get tag's create page.
     */
    public function testNewAction(): void
    {
        $TagName        = 'Test tag';
        $crawler        = $this->client->request('GET', '/s/tags/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();
        $form['tag_entity[tag]']->setValue($TagName);
        $this->client->submit($form);

        $this->assertSame(1, $this->tagRepository->count(['tag' => $TagName]));
    }

    public function testNewActionDuplicateTag(): void
    {
        $TagName        = $this->tagRepository->findOneBy([])->getTag();
        $crawler        = $this->client->request('GET', '/s/tags/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form();
        $form['tag_entity[tag]']->setValue($TagName);
        $crawler = $this->client->submit($form);

        $this->assertStringContainsString($TagName.' has been updated!', strip_tags($crawler->text(null, false)), 'Must contain already exist.');
    }

    public function testBatchDeleteAction(): void
    {
        $tags   = $this->tagRepository->findAll();
        $tagsId = array_map(fn (Tag $tag) => $tag->getId(), $tags);
        $this->client->request('POST', '/s/tags/batchDelete?ids='.json_encode($tagsId));
        $this->assertResponseIsSuccessful();
        $this->assertEmpty($this->tagRepository->count([]), 'All tags must be deleted.');
    }

    public function testEmptyTagShouldThrowValidationError(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/tags/new');
        self::assertResponseIsSuccessful();

        $buttonCrawler  = $crawler->selectButton('Save & Close');
        $form           = $buttonCrawler->form();
        $form->setValues(['tag_entity[tag]' => '']);
        $this->client->submit($form);
        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('A value is required.', $this->client->getResponse()->getContent());
    }

    public function testEditTagWithNoPermission(): void
    {
        $this->createAndLoginUser();
        $tag     = $this->tagRepository->findOneBy([]);
        $this->client->request(Request::METHOD_GET, '/s/tags/edit/'.$tag->getId());
        $this->assertResponseStatusCodeSame(403, (string) $this->client->getResponse()->getStatusCode());
    }

    private function createAndLoginUser(): User
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->detach($role);

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', self::USERNAME);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        return $user;
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('Jhon');
        $user->setLastName('Doe');
        $user->setUsername(self::USERNAME);
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }
}
