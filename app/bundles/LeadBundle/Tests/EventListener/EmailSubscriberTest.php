<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\EmailSubscriber;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailSubscriberTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('onEmailAddressReplacementProvider')]
    public function testOnEmailAddressReplacement(string $value, string $expected): void
    {
        $contact = new Lead();
        $contact->setFields(['email2' => 'contact.a@email.address']);

        $event           = new TokenReplacementEvent($value, $contact);
        $emailSubscriber = new EmailSubscriber(
            new class extends BuilderTokenHelperFactory {
                public function __construct()
                {
                }
            },
            self::createStub(TranslatorInterface::class),
        );

        $emailSubscriber->onEmailAddressReplacement($event);

        Assert::assertSame($expected, $event->getContent());
    }

    /**
     * @return \Generator<string[]>
     */
    public static function onEmailAddressReplacementProvider(): \Generator
    {
        yield ['{contactfield=unicorn}', ''];
        yield ['{contactfield=unicorn|default@value.email}', 'default@value.email'];
        yield ['{contactfield=email2}', 'contact.a@email.address'];
    }
}
