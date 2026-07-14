<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticSocialBundle\Entity\Tweet;
use MauticPlugin\MauticSocialBundle\Entity\TweetRepository;
use MauticPlugin\MauticSocialBundle\Model\TweetModel;
use Symfony\Component\HttpFoundation\Request;

final class TweetControllerTest extends MauticMysqlTestCase
{
    private TweetRepository $tweetsRepo;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var TweetModel $tweetsModel */
        $tweetsModel      = static::getContainer()->get('mautic.social.model.tweet');
        $this->tweetsRepo = $tweetsModel->getRepository();

        $tweet = new Tweet();
        $tweet->setName('Tweet One')
            ->setText('Tweet One');

        $tweetsModel->saveEntity($tweet);
    }

    public function testTweetListPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/tweets');
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Tweet One', $response->getContent());
    }

    public function testCreateTweet(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/tweets/new');
        $saveButton = $crawler->selectButton('Save & Close');
        $form       = $saveButton->form();
        $name       = 'The first tweet';
        $form['twitter_tweet[name]']->setValue($name);
        $form['twitter_tweet[text]']->setValue('Here is the first tweet');

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertSame(1, $this->tweetsRepo->count(['name' => $name]));
    }

    public function testEditAction(): void
    {
        $tweet = $this->tweetsRepo->findOneBy([]);

        $crawler               = $this->client->request('GET', '/s/tweets/edit/'.$tweet->getId());
        $clientResponse        = $this->client->getResponse();
        $clientResponseContent = $clientResponse->getContent();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Edit tweet '.$tweet->getName(), $clientResponseContent, 'The return must contain \'Edit tweet\' text');

        $form = $crawler->selectButton('Save & Close')->form();
        $form['twitter_tweet[name]']->setValue('Updated tweet name');
        $this->client->submit($form);

        $this->assertSame(1, $this->tweetsRepo->count(['name' => 'Updated tweet name']));
    }
}
