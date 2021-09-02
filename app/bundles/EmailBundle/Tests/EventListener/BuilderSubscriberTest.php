<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\BuilderSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use function RectorPrefix20210720\Stringy\create;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuilderSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider fixEmailAccessibilityContent
     */
    public function testFixEmailAccessibility(string $content, string $expectedContent)
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $emailModel           = $this->createMock(EmailModel::class);
        $trackableModel       = $this->createMock(TrackableModel::class);
        $redirectModel        = $this->createMock(RedirectModel::class);
        $translator           = $this->createMock(\Symfony\Component\Translation\TranslatorInterface::class);
        $entityManager        = $this->createMock(EntityManager::class);
        $builderSubscriber    = new BuilderSubscriber(
            $coreParametersHelper,
            $emailModel,
            $trackableModel,
            $redirectModel,
            $translator,
            $entityManager
        );

        $email = new Email();
        $email->setLanguage('en');
        $emailSendEvent = new EmailSendEvent(null, ['email'=>$email]);
        $emailSendEvent->setContent($content);
        $builderSubscriber->fixEmailAccessibility($emailSendEvent);
        $this->assertSame($emailSendEvent->getContent(), $expectedContent);
    }

    public function fixEmailAccessibilityContent(): iterable
    {
        yield ['<html><head></head></html>', '<html lang="en"><head><title>{subject}</title></head></html>'];
        yield ['<html lang="en"><head></head></html>', '<html lang="en"><head><title>{subject}</title></head></html>'];
        yield ['<html lang="en"><head><title>Existed Title</title></head></html>', '<html lang="en"><head><title>Existed Title</title></head></html>'];
        yield ['<head><title>Existed Title</title></head>', '<head><title>Existed Title</title></head>'];
        yield ['<html><body>xxx</body></html>', '<html lang="en"><body>xxx</body></html>'];
    }
}
