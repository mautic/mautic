<?php

declare(strict_types=1);

    /*
     * @copyright   2020 Mautic Contributors. All rights reserved
     * @author      Mautic, Inc.
     *
     * @link        https://mautic.org
     *
     * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
     */

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\EmailSubscriber;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class EmailSubscriberTest extends TestCase
{
    /**
     * @dataProvider onEmailAddressReplacementProvider
     */
    public function testOnEmailAddressReplacement(string $value, string $expected): void
    {
        $contact = new Lead();
        $contact->setFields(['email2' => 'contact.a@email.address']);

        $event           = new TokenReplacementEvent($value, $contact);
        $emailSubscriber = new EmailSubscriber(
            new class() extends BuilderTokenHelperFactory {
                public function __construct()
                {
                }
            }
        );

        $emailSubscriber->onEmailAddressReplacement($event);

        Assert::assertSame($expected, $event->getContent());
    }

    /**
     * @return \Generator<string[]>
     */
    public function onEmailAddressReplacementProvider(): \Generator
    {
        yield ['{contactfield=unicorn}', ''];
        yield ['{contactfield=unicorn|default@value.email}', 'default@value.email'];
        yield ['{contactfield=email2}', 'contact.a@email.address'];
    }
}
