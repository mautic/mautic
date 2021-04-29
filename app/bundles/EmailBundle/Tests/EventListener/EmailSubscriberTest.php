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
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\EmailSubscriber;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\TranslatorInterface;

class EmailSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $ipLookupHelper;
    private $auditLogModel;
    private $translator;
    private $emailModel;
    private $entityManager;
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel            = $this->createMock(AuditLogModel::class);
        $this->translator               = $this->createMock(TranslatorInterface::class);
        $this->emailModel               = $this->createMock(EmailModel::class);
        $this->entityManager            = $this->createMock(EntityManager::class);

        $this->subscriber =  new EmailSubscriber($this->ipLookupHelper, $this->auditLogModel, $this->emailModel, $this->translator, $this->entityManager);
    }

    public function testOnEmailSendAddPreheaderText()
    {
        $mockFactory = $this->getMockBuilder(MauticFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $swiftMailer = $this->getMockBuilder(\Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokens = [
            '{test}' => 'value',
        ];

        $mailHelper = new MailHelper($mockFactory, $swiftMailer);
        //$mailHelper->setTokens($tokens);

        $preheaderText = 'this is a nice preheader text';

        $email = new Email();
        $email->setCustomHtml(
            <<<'CONTENT'
<html xmlns="http://www.w3.org/1999/xhtml">
    <body style="margin: 0px; cursor: auto;" class="ui-sortable">
        <div data-section-wrapper="1">
            <center>
                <table data-section="1" style="width: 600;" width="600" cellpadding="0" cellspacing="0">
                    <tbody>
                        <tr>
                            <td>
                                <div data-slot-container="1" style="min-height: 30px">
                                    <div data-slot="text"><br /><h2>Hello there!</h2><br />{test} test We haven't heard from you for a while...<a href="https://google.com">check this link</a><br /><br />{unsubscribe_text} | {webview_text}</div>{dynamiccontent="Dynamic Content 2"}<div data-slot="codemode">
                                    <div id="codemodeHtmlContainer">
    <p>Place your content here {test}</p></div>

                                </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </center>
        </div>
</body></html>
CONTENT
        );

        $email->setPreheaderText($preheaderText);
        $mailHelper->setEmail($email);

        $dispatcher           = new EventDispatcher();
        $dispatcher->addSubscriber($this->subscriber);

        $event = new EmailSendEvent($mailHelper);

        $this->subscriber->onEmailSendAddPreheaderText($event);

        $preheaderTextHtml = EmailSubscriber::PREHEADER_HTML_ELEMENT_BEFORE.$preheaderText.EmailSubscriber::PREHEADER_HTML_ELEMENT_AFTER;

        $this->assertStringContainsString($preheaderTextHtml, $event->getContent());
        $this->assertMatchesRegularExpression(EmailSubscriber::PREHEADER_HTML_SEARCH_PATTERN, $event->getContent());
    }
}
