<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor\Bounce;

use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\DsnParser;

#[\PHPUnit\Framework\Attributes\CoversClass(DsnParser::class)]
class DsnParserTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that a BouncedEmail is returned from a dsn report')]
    #[\PHPUnit\Framework\Attributes\DataProvider('bouncedEmailProvider')]
    public function testBouncedEmailIsReturnedFromParsedDsnReport(
        string $dsnReport,
        string $expectedEmail,
        string $expectedCategory,
        string $expectedType,
        bool $expectedFinal,
        ?string $expectedRuleNumber = null,
    ): void {
        $message            = new Message();
        $message->dsnReport = $dsnReport;
        $parser             = new DsnParser();
        $bounce             = $parser->getBounce($message);

        $this->assertEquals($expectedEmail, $bounce->getContactEmail());
        $this->assertEquals($expectedCategory, $bounce->getRuleCategory());
        $this->assertEquals($expectedType, $bounce->getType());
        $this->assertEquals($expectedFinal, $bounce->isFinal());
        if (null !== $expectedRuleNumber) {
            $this->assertEquals($expectedRuleNumber, $bounce->getRuleNumber());
        }
    }

    /**
     * @return array<string, array{
     *     dsnReport: string,
     *     expectedEmail: string,
     *     expectedCategory: string,
     *     expectedType: string,
     *     expectedFinal: bool,
     *     expectedRuleNumber?: string|null
     * }>
     */
    public static function bouncedEmailProvider(): array
    {
        return [
            'basic DNS unknown - host not found' => [
                'dsnReport'   => <<<'DSN'
Original-Recipient: sdfgsdfg@seznan.cz
Final-Recipient: rfc822;sdfgsdfg@seznan.cz
Action: failed
Status: 5.4.4
Diagnostic-Code: DNS; Host not found
DSN,
                'expectedEmail'      => 'sdfgsdfg@seznan.cz',
                'expectedCategory'   => Category::DNS_UNKNOWN,
                'expectedType'       => Type::HARD,
                'expectedFinal'      => true,
                'expectedRuleNumber' => null,
            ],
            'postfix unknown user - user does not exist' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; aaaaaaaaaaaaa@yoursite.com
Original-Recipient: rfc822;aaaaaaaaaaaaa@yoursite.com
Action: failed
Status: 5.1.1
Remote-MTA: dns; mail-server.yoursite.com
Diagnostic-Code: smtp; 550 5.1.1 <aaaaaaaaaaaaa@yoursite.com> User doesn't
    exist: aaaaaaaaaaaaa@yoursite.com
DSN,
                'expectedEmail'      => 'aaaaaaaaaaaaa@yoursite.com',
                'expectedCategory'   => Category::UNKNOWN,
                'expectedType'       => Type::HARD,
                'expectedFinal'      => true,
                'expectedRuleNumber' => null,
            ],
            'unified group agent - sender not permitted to send to group' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; sender@example.com
Action: failed
Status: 5.7.193
Diagnostic-Code: smtp;550 5.7.193 UnifiedGroupAgent; Delivery failed because the sender isn't a group member or external senders aren't permitted to send to this group.
DSN,
                'expectedEmail'      => 'sender@example.com',
                'expectedCategory'   => Category::USER_REJECT,
                'expectedType'       => Type::HARD,
                'expectedFinal'      => true,
                'expectedRuleNumber' => '0230',
            ],
            'message expired - cannot connect to remote server' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; recipient@example.com
Action: failed
Status: 5.4.317
Diagnostic-Code: smtp;550 5.4.317 Message expired, cannot connect to remote server
DSN,
                'expectedEmail'      => 'recipient@example.com',
                'expectedCategory'   => Category::DNS_UNKNOWN,
                'expectedType'       => Type::HARD,
                'expectedFinal'      => true,
                'expectedRuleNumber' => '0232',
            ],
            'outlook 550 5.4.1 access denied' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; user@outlook.com
Action: failed
Status: 5.4.1
Diagnostic-Code: smtp;550 5.4.1 Recipient address rejected: Access denied
DSN,
                'expectedEmail'      => 'user@outlook.com',
                'expectedCategory'   => Category::USER_REJECT,
                'expectedType'       => Type::HARD,
                'expectedFinal'      => true,
                'expectedRuleNumber' => '0233',
            ],
            'outlook 550 5.1.10 recipient not found' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; user@outlook.com
Action: failed
Status: 5.1.10
Diagnostic-Code: smtp;550 5.1.10 RESOLVER.ADR.RecipientNotFound; Recipient not found by Exchange address book
DSN,
                'expectedEmail'      => 'user@outlook.com',
                'expectedCategory'   => Category::UNKNOWN,
                'expectedType'       => Type::HARD,
                'expectedFinal'      => true,
                'expectedRuleNumber' => '0136',
            ],
            'outlook 550 5.7.133 sender not authenticated for group' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; group@outlook.com
Action: failed
Status: 5.7.133
Diagnostic-Code: smtp;550 5.7.133 RESOLVER.RST.SenderNotAuthenticatedForGroup; Sender not authenticated for group
DSN,
                'expectedEmail'      => 'group@outlook.com',
                'expectedCategory'   => Category::USER_REJECT,
                'expectedType'       => Type::HARD,
                'expectedFinal'      => true,
                'expectedRuleNumber' => '0206',
            ],
            'outlook 550 5.7.1 client host blocked' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; user@outlook.com
Action: failed
Status: 5.7.1
Diagnostic-Code: smtp;550 5.7.1 Service unavailable, Client host [1.2.3.4] blocked using Spamhaus
DSN,
                'expectedEmail'      => 'user@outlook.com',
                'expectedCategory'   => Category::ANTISPAM,
                'expectedType'       => Type::BLOCKED,
                'expectedFinal'      => false,
                'expectedRuleNumber' => '0201',
            ],
            'outlook 550 5.7.606 banned sending IP' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; user@outlook.com
Action: failed
Status: 5.7.606
Diagnostic-Code: smtp;550 5.7.606 Access denied, banned sending IP [1.2.3.4]
DSN,
                'expectedEmail'      => 'user@outlook.com',
                'expectedCategory'   => Category::ANTISPAM,
                'expectedType'       => Type::BLOCKED,
                'expectedFinal'      => false,
                'expectedRuleNumber' => '0235',
            ],
            'outlook 550 5.7.511 banned sender' => [
                'dsnReport'   => <<<'DSN'
Final-Recipient: rfc822; user@outlook.com
Action: failed
Status: 5.7.511
Diagnostic-Code: smtp;550 5.7.511 Access denied, banned sender [user@outlook.com]
DSN,
                'expectedEmail'      => 'user@outlook.com',
                'expectedCategory'   => Category::ANTISPAM,
                'expectedType'       => Type::BLOCKED,
                'expectedFinal'      => false,
                'expectedRuleNumber' => '0234',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that an exception is thrown if a bounce cannot be found in a dsn report')]
    public function testBounceNotFoundFromBadDsnReport(): void
    {
        $this->expectException(BounceNotFound::class);

        $message            = new Message();
        $message->dsnReport = 'BAD';
        $parser             = new DsnParser();
        $parser->getBounce($message);
    }
}
