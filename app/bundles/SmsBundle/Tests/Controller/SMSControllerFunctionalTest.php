<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\SmsBundle\Entity\Sms;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class SMSControllerFunctionalTest extends MauticMysqlTestCase
{
    private const EDIT_SMS_PATH       = '/s/sms/edit/';

    private const DEFAULT_SMS_MESSAGE = 'sms body';

    private const SAVE_AND_CLOSE      = 'Save & Close';

    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://localhost';
        parent::setUp();
    }

    public function testSmsWithProject(): void
    {
        $sms = $this->createSms();

        $project = new Project();
        $project->setName('Test Project');
        $this->em->persist($project);

        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request('GET', self::EDIT_SMS_PATH.$sms->getId());
        $form    = $crawler->selectButton('Save')->form();
        $form['sms[projects]']->setValue((string) $project->getId());

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();

        $savedSms = $this->em->find(Sms::class, $sms->getId());
        Assert::assertSame($project->getId(), $savedSms->getProjects()->first()->getId());
    }

    public function testListPageMMSIndicator(): void
    {
        $mediaSmsName = 'Media attached sms';
        $this->createSms($mediaSmsName, self::DEFAULT_SMS_MESSAGE, true, range(1, 3));
        $this->createSms('sms', 'sms body2');
        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms');
        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('.sms-list tbody tr'));
        $this->assertCount(1, $crawler->filter('.sms-list tbody i.ri-file-image-line'));
        $this->assertStringContainsString($mediaSmsName, $crawler->filter('.sms-list tbody i.ri-file-image-line')->closest('tr')->text());
    }

    public function testPreviewMMS(): void
    {
        $this->createAndVerifyMedia('preview');
    }

    public function testDetailPage(): void
    {
        $this->createAndVerifyMedia('view');
    }

    public function testSmsWithMediaLimitError(): void
    {
        $sms            = $this->createSms('sms', self::DEFAULT_SMS_MESSAGE, true, range(1, 11));
        $crawler        = $this->client->request(Request::METHOD_GET, self::EDIT_SMS_PATH.$sms->getId());
        $buttonCrawler  = $crawler->selectButton(self::SAVE_AND_CLOSE);
        $form           = $buttonCrawler->form();

        $form->setValues([
            'sms[name]'    => 'sms',
            'sms[message]' => self::DEFAULT_SMS_MESSAGE,
            'sms[isMms]'   => 1,
            'sms[media]'   => range(1, 11),
        ]);

        $crawler  = $this->client->submit($form);
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());
        Assert::assertStringContainsString('Maximum 10 media could be attached with 1 MMS', $crawler->filter('#media_div .has-error')->text());
    }

    public function testCloneSmsWithMediaSuccessfully(): void
    {
        $clonedName    = 'sms clone';
        $clonedMessage = 'sms body clone';
        $media         = ['a.png', 'b.jpg'];

        $sms = $this->createSms('sms', self::DEFAULT_SMS_MESSAGE, true, $media);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/sms/clone/'.$sms->getId());

        $buttonCrawler  = $crawler->selectButton(self::SAVE_AND_CLOSE);
        $form           = $buttonCrawler->form();
        $form->setValues([
            'sms[name]'    => 'sms clone',
            'sms[message]' => 'sms body clone',
        ]);

        $this->client->submit($form);
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());
        $savedSms = $this->em->getRepository(Sms::class)->findOneBy(['name' => $clonedName]);
        Assert::assertInstanceOf(Sms::class, $savedSms);
        Assert::assertSame($clonedMessage, $savedSms->getMessage());
        Assert::assertSame($media, $savedSms->getMedia());
        Assert::assertTrue($savedSms->getIsMms());
    }

    public function testSaveSmsWithMediaFalse(): void
    {
        $media   = ['a.png', 'b.jpg'];
        $sms     = $this->createSms('sms', self::DEFAULT_SMS_MESSAGE, true, $media);
        $crawler = $this->client->request(Request::METHOD_GET, self::EDIT_SMS_PATH.$sms->getId());

        $buttonCrawler  = $crawler->selectButton(self::SAVE_AND_CLOSE);
        $form           = $buttonCrawler->form();
        $form->setValues([
            'sms[isMms]'    => 0,
        ]);

        $this->client->submit($form);
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());
        $savedSms = $this->em->getRepository(Sms::class)->find($sms->getId());
        Assert::assertInstanceOf(Sms::class, $savedSms);
        Assert::assertSame([], $savedSms->getMedia());
        Assert::assertFalse($savedSms->getIsMms());
    }

    /**
     * @param array<mixed> $media
     */
    private function createSms(string $name = 'sms', string $message = self::DEFAULT_SMS_MESSAGE, bool $isMms = false, ?array $media = null): Sms
    {
        $sms = new Sms();
        $sms->setName($name);
        $sms->setMessage($message);
        if (!empty($media)) {
            $sms->setMedia($media);
        }
        $sms->setIsMms($isMms);
        $sms->setSmsType('template');
        $this->em->persist($sms);
        $this->em->flush();

        return $sms;
    }

    private function createAndVerifyMedia(string $page): void
    {
        $media    = range(1, 3);
        $sms      = $this->createSms('Media attached sms', self::DEFAULT_SMS_MESSAGE, true, $media);
        $crawler  = $this->client->request(Request::METHOD_GET, '/s/sms/'.$page.'/'.$sms->getId());
        $this->assertResponseIsSuccessful();
        $mediaDivs = $crawler->filter('div.phone-preview__message--media');
        $this->assertCount(3, $mediaDivs);
        foreach ($media as $key => $value) {
            $img = $mediaDivs->eq($key)->filter('img');
            $this->assertSame((string) $value, $img->attr('src'));
        }
    }
}
