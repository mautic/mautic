<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\TokenHelper;

class TokenHelperTest extends \PHPUnit_Framework_TestCase
{
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
                        'companyzip' => '77008',
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

    public function testDateFormatValue()
    {
        $lead = [
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

        $token = '{contactfield=date|date_format|d. m. Y}';

        $tokenList = TokenHelper::findLeadTokens($token, $lead);
        $this->assertEquals([$token => '05. 05. 2000'], $tokenList);
    }

    public function testDateTimeFormatValue()
    {
        $lead = [
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

        $token = '{contactfield=date|date_format|d. m. Y H:i}';

        $tokenList = TokenHelper::findLeadTokens($token, $lead);
        $this->assertEquals([$token => '05. 05. 2000 12:45'], $tokenList);
    }
}
