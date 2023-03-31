<?php

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\DateTime\DateTimeLocalization;
use Mautic\LeadBundle\Helper\TokenHelper;
use ReflectionProperty;

class TokenHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DateTimeLocalization|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dateTimeLocalizationMock;

    private $lead = [
        'firstname' => 'Bob',
        'lastname'  => 'Smith',
        'country'   => '',
        'date'      => '2000-05-05 12:45:50',
        'companies' => [
            [
                'companyzip' => '77008',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $reflectionProperty = new ReflectionProperty(TokenHelper::class, 'parameters');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue([
            'date_format_dateonly' => 'F j, Y',
            'date_format_timeonly' => 'g:i a',
        ]);

        $this->dateTimeLocalizationMock = $this->createMock(DateTimeLocalization::class);
        $this->dateTimeLocalizationMock->expects($this->any())->method('localize')->willReturnCallback(function ($value) {
            return $value; // Just returning the same value in this example, but you can modify the behavior as needed
        });

        $reflectionProperty = new ReflectionProperty(DateTimeLocalization::class, 'service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->dateTimeLocalizationMock);
        parent::setUp();
    }

    public function testContactTokensAreReplaced()
    {
        $lead = [
            'firstname' => 'Bob',
            'lastname'  => 'Smith',
            'country'   => 'USA',
            'companies' => [
                [
                    'companyzip' => '77008',
                ],
            ],
        ];

        $token = '{contactfield=country}';

        $tokenList = TokenHelper::findLeadTokens($token, $lead);
        $this->assertEquals([$token => 'USA'], $tokenList);
    }

    public function testCompanyTokensAreReplaced()
    {
        $leads = [
            [
                'firstname' => 'Bob',
                'lastname'  => 'Smith',
                'companies' => [
                    [
                        'companyzip' => '77009',
                        'is_primary' => 0,
                    ],
                    [
                        'companyzip' => '77008',
                        'is_primary' => 1,
                    ],
                ],
            ],
            [
                'firstname' => 'Jane',
                'lastname'  => 'Smith',
            ],
            [
                'firstname' => 'Joey',
                'lastname'  => 'Smith',
                'companies' => [],
            ],
        ];

        $token = '{contactfield=companyzip}';

        $tokenList = TokenHelper::findLeadTokens($token, $leads[0]);
        $this->assertEquals([$token => '77008'], $tokenList);

        $tokenList = TokenHelper::findLeadTokens($token, $leads[1]);
        $this->assertEquals([$token => ''], $tokenList);

        $tokenList = TokenHelper::findLeadTokens($token, $leads[2]);
        $this->assertEquals([$token => ''], $tokenList);
    }

    public function testDefaultValueIsUsed()
    {
        $lead = [
            'firstname' => 'Bob',
            'lastname'  => 'Smith',
            'country'   => '',
            'companies' => [
                [
                    'companyzip' => '77008',
                ],
            ],
        ];

        $token = '{contactfield=country|USA}';

        $tokenList = TokenHelper::findLeadTokens($token, $lead);
        $this->assertEquals([$token => 'USA'], $tokenList);
    }

    public function testValueIsUrlEncoded()
    {
        $lead = [
            'firstname' => 'Bob',
            'lastname'  => 'Smith',
            'country'   => 'Somewhere&Else',
            'companies' => [
                [
                    'companyzip' => '77008',
                ],
            ],
        ];

        $token = '{contactfield=country|true}';

        $tokenList = TokenHelper::findLeadTokens($token, $lead);
        $this->assertEquals([$token => 'Somewhere%26Else'], $tokenList);
    }

    public function testGetValueFromTokensWhenSomeValue()
    {
        $token  = '{contactfield=website}';
        $tokens = [
            '{contactfield=website}' => 'https://mautic.org',
        ];
        $this->assertEquals(
            'https://mautic.org',
            TokenHelper::getValueFromTokens($tokens, $token)
        );
    }

    public function testGetValueFromTokensWhenSomeValueWithDefaultValue()
    {
        $token  = '{contactfield=website|ftp://default.url}';
        $tokens = [
            '{contactfield=website}' => 'https://mautic.org',
        ];
        $this->assertEquals(
            'https://mautic.org',
            TokenHelper::getValueFromTokens($tokens, $token)
        );
    }

    public function testGetValueFromTokensWhenNoValueWithDefaultValue()
    {
        $token  = '{contactfield=website|ftp://default.url}';
        $tokens = [
            '{contactfield=website}' => '',
        ];
        $this->assertEquals(
            'ftp://default.url',
            TokenHelper::getValueFromTokens($tokens, $token)
        );
    }

    public function testGetValueFromTokensWhenNoValueWithoutDefaultValue()
    {
        $token  = '{contactfield=website}';
        $tokens = [
            '{contactfield=website}' => '',
        ];
        $this->assertEquals(
            '',
            TokenHelper::getValueFromTokens($tokens, $token)
        );
    }

    public function testDateTimeFormatValue()
    {
        $token     = '{contactfield=date|datetime}';
        $tokenList = TokenHelper::findLeadTokens($token, $this->lead);
        $this->assertNotSame($this->lead['date'], $tokenList[$token]);
    }

    public function testDateFormatValue()
    {
        $token     = '{contactfield=date|date}';
        $tokenList = TokenHelper::findLeadTokens($token, $this->lead);
        $this->assertNotSame($this->lead['date'], $tokenList[$token]);
    }

    public function testTimeFormatValue()
    {
        $token     = '{contactfield=date|time}';
        $tokenList = TokenHelper::findLeadTokens($token, $this->lead);
        $this->assertNotSame($this->lead['date'], $tokenList[$token]);
    }

    public function testDateFormatForEmptyValue()
    {
        $lead         = $this->lead;
        $lead['date'] = '';

        $token     = '{contactfield=date|time}';
        $tokenList = TokenHelper::findLeadTokens($token, $lead);
        $this->assertEmpty($tokenList[$token]);
    }

    public function testDateTimeLocalization(): void
    {
        $content = 'The event starts on {contactfield=event_date|datetime}';

        $lead = [
            'first_name'       => 'John',
            'last_name'        => 'Doe',
            'email'            => 'john.doe@example.com',
            'preferred_locale' => 'en',
            'event_date'       => '2023-04-01 15:00:00',
        ];

        $expectedResult = [
            '{contactfield=event_date|datetime}' => 'April 1, 2023 3:00 pm',
        ];

        $result = TokenHelper::findLeadTokens($content, $lead);
        $this->assertSame($expectedResult, $result);

        $replacedContent = TokenHelper::findLeadTokens($content, $lead, true);
        $this->assertSame('The event starts on April 1, 2023 3:00 pm', $replacedContent);
    }
}
