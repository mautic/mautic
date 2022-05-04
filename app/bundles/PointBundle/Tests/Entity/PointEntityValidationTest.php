<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Entity;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;

const MIN_INTEGER_VALUE = -2147483648;
const MAX_INTEGER_VALUE = 2147483647;

class PointEntityValidationTest extends AbstractMauticTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider deltaScenariosProvider
     */
    public function testDeltaValidation(int $delta, int $status, string $errorMessage = '')
    {
        $this->client->request(
            'POST',
            '/s/points/new',
            [
                'name'        => 'Point1',
                'delta'       => $delta,
                'isPublished' => true,
                'type'        => 'form.submit',
            ]
        );

        //tried $response = $this->client->getInternalResponse(); this function too, same results
        $response = $this->client->getResponse();

        //can't differentiate using status code, I get 200 in all cases, this assertion doesn't add any value. This will be removed
        self::assertSame($status, $response->getStatusCode());

        //trying to assert the error string in the response.
        //The error string can be seen in inspect->network tab inside response and preview sections
        //but I can't find it in the responses here, causing the last two data sets to fail
        self::assertStringContainsString($errorMessage, (string) $response);

        self::markTestIncomplete();
    }

    public function deltaScenariosProvider(): iterable
    {
        $acceptableDelta = random_int(MIN_INTEGER_VALUE, MAX_INTEGER_VALUE);
        yield 'within range' => [$acceptableDelta, 200, ''];
        yield 'upper limit' => [MAX_INTEGER_VALUE, 200, ''];
        yield 'lower limit' => [MIN_INTEGER_VALUE, 200, ''];
        yield 'above upper limit' => [MAX_INTEGER_VALUE - 10, 200, 'This value should be between -2147483648 and 2147483647.'];
        yield 'below lower limit' => [MIN_INTEGER_VALUE - 10, 200, 'This value should be between -2147483648 and 2147483647.'];
    }
}
