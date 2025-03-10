<?php

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\ThemeHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\TokenSubscriber;
use Mautic\EmailBundle\Helper\FromEmailHelper;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\Tests\Helper\Transport\SmtpTransport;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class TokenSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testDynamicContentCustomTokens(): void
    {
        /** @var MockObject&FromEmailHelper $fromEmailHelper */
        $fromEmailHelper = $this->createMock(FromEmailHelper::class);

        /** @var MockObject&CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        /** @var MockObject&Mailbox $mailbox */
        $mailbox = $this->createMock(Mailbox::class);

        /** @var MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        /** @var MockObject&RouterInterface $router */
        $router = $this->createMock(RouterInterface::class);

        /** @var MockObject&Environment $twig */
        $twig = $this->createMock(Environment::class);

        $requestStack = new RequestStack();
        $themeHelper  = $this->createMock(ThemeHelper::class);
        $themeHelper->expects(self::never())
            ->method('checkForTwigTemplate');

        $mailHashHelper = new MailHashHelper($coreParametersHelper);

        $coreParametersHelper->method('get')
            ->willReturnMap(
                [
                    ['mailer_from_email', null, 'nobody@nowhere.com'],
                    ['mailer_from_name', null, 'No Body'],
                ]
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never()) // Never to make sure that the mock is properly tested if needed.
            ->method('getReference');

        $tokens = ['{test}' => 'value'];

        $mailHelper = new MailHelper(
            new Mailer(new SmtpTransport()),
            $fromEmailHelper,
            $coreParametersHelper,
            $mailbox,
            $logger,
            $mailHashHelper,
            $router,
            $twig,
            $themeHelper,
            $this->createMock(PathsHelper::class),
            $this->createMock(EventDispatcherInterface::class),
            $requestStack,
            $entityManager,
            $this->createMock(ModelFactory::class),
            $this->createMock(AssetModel::class),
            $this->createMock(TrackableModel::class),
            $this->createMock(RedirectModel::class),
        );
        $mailHelper->setTokens($tokens);

        $email = new Email();
        $email->setSubject('Test subject');
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
        )
            ->setDynamicContent(
                [
                    [
                        'tokenName' => 'Dynamic Content 1',
                        'content'   => 'Default Dynamic Content',
                        'filters'   => [
                            [
                                'content' => null,
                                'filters' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'tokenName' => 'Dynamic Content 2',
                        'content'   => 'DEC {test}',
                        'filters'   => [
                        ],
                    ],
                ]
            );
        $mailHelper->setEmail($email);

        $lead = new Lead();
        $lead->setEmail('hello@someone.com');
        $mailHelper->setLead($lead);

        $dispatcher           = new EventDispatcher();
        $primaryCompanyHelper = $this->createMock(PrimaryCompanyHelper::class);
        $primaryCompanyHelper->method('getProfileFieldsWithPrimaryCompany')
            ->willReturn(['email' => 'hello@someone.com']);
        $segmentRepository    = $this->createMock(LeadListRepository::class);

        /** @var TokenSubscriber $subscriber */
        $subscriber = $this->getMockBuilder(TokenSubscriber::class)
            ->setConstructorArgs([$dispatcher, $primaryCompanyHelper, $segmentRepository])
            ->onlyMethods([])
            ->getMock();

        $dispatcher->addSubscriber($subscriber);

        $event = new EmailSendEvent($mailHelper);

        $subscriber->decodeTokens($event);

        $eventTokens = $event->getTokens(false);
        $this->assertEquals(
            $eventTokens,
            [
                '{dynamiccontent="Dynamic Content 1"}' => 'Default Dynamic Content',
                '{dynamiccontent="Dynamic Content 2"}' => 'DEC value',
            ]
        );
        $mailHelper->addTokens($eventTokens);
        $mailerTokens = $mailHelper->getTokens();
        $mailHelper->message->html($email->getCustomHtml());
        $mailHelper->message->subject($email->getSubject());

        MailHelper::searchReplaceTokens(array_keys($mailerTokens), $mailerTokens, $mailHelper->message);
        $parsedBody = $mailHelper->message->getHtmlBody();

        $this->assertNotFalse(strpos($parsedBody, 'DEC value'));
        $this->assertNotFalse(strpos($parsedBody, 'value test We'));
        $this->assertNotFalse(strpos($parsedBody, 'Place your content here value'));
    }
}
