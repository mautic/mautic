<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\BuilderSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Component\Translation\TranslatorInterface;

class BuilderSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider fixEmailAccessibilityContent
     */
    public function testFixEmailAccessibility(string $content, string $expectedContent, ?string $emailLocale): void
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $emailModel           = $this->createMock(EmailModel::class);
        $trackableModel       = $this->createMock(TrackableModel::class);
        $redirectModel        = $this->createMock(RedirectModel::class);
        $translator           = $this->createMock(TranslatorInterface::class);
        $entityManager        = $this->createMock(EntityManager::class);
        $builderSubscriber    = new BuilderSubscriber(
            $coreParametersHelper,
            $emailModel,
            $trackableModel,
            $redirectModel,
            $translator,
            $entityManager
        );

        $coreParametersHelper->method('get')->willReturnCallback(function ($key) {
            if ('locale' === $key) {
                return 'default_locale';
            }

            return false;
        });

        $email = new Email();
        $email->setSubject('A unicorn spotted in Alaska');
        $email->setLanguage($emailLocale);

        $emailSendEvent = new EmailSendEvent(null, ['email' => $email]);
        $emailSendEvent->setContent($content);
        $builderSubscriber->fixEmailAccessibility($emailSendEvent);
        $builderSubscriber->onEmailGenerate($emailSendEvent);
        $this->assertSame($expectedContent, $emailSendEvent->getContent());
    }

    /**
     * @return iterable<array<int,string>>
     */
    public function fixEmailAccessibilityContent(): iterable
    {
        yield [
            '<html><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html><head></head></html>',
            '<html lang="es"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'es',
        ];
        yield [
            '<html><head></head></html>',
            '<html lang="default_locale"><head><title>A unicorn spotted in Alaska</title></head></html>',
            '',
        ];
        yield [
            "<html>\n\n<head>\n</head>\n</html>",
            "<html lang=\"en\">\n\n<head>\n<title>A unicorn spotted in Alaska</title></head>\n</html>",
            'en',
        ];
        yield [
            '<html lang="en"><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html lang="en"><head></head></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'es',
        ];
        yield [
            '<html lang="cs_CZ"><head></head></html>',
            '<html lang="cs_CZ"><head><title>A unicorn spotted in Alaska</title></head></html>',
            'en',
        ];
        yield [
            '<html lang="en"><head><title>Existed Title</title></head></html>',
            '<html lang="en"><head><title>Existed Title</title></head></html>',
            'en',
        ];
        yield [
            '<head><title>Existed Title</title></head>',
            '<head><title>Existed Title</title></head>',
            'en',
        ];
        yield [
            '<html><body>xxx</body></html>',
            '<html lang="en"><head><title>A unicorn spotted in Alaska</title></head><body>xxx</body></html>',
            'en',
        ];
    }
}
