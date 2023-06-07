<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FocusApiControllerTest extends MauticMysqlTestCase
{
    /**
     * @var array<string,mixed>
     */
    private array $testPayload = [
        'name'       => 'test',
        'type'       => 'notice',
        'website'    => 'http://',
        'style'      => 'bar',
        'htmlMode'   => 1,
        'html'       => '<div><strong style="color:red">html mode enabled</strong></div>',
        'properties' => [
            'bar' => [
                'allow_hide' => 1,
                'sticky'     => 1,
                'size'       => 'large',
                'placement'  => 'top',
            ],
            'modal' => [
                'placement' => 'top',
            ],
            'notification' => [
                'placement' => 'top_left',
            ],
            'animate'         => 1,
            'link_activation' => 1,
            'colors'          => [
                'primary' => '27184e',
            ],
            'content' => [
                'headline' => '',
                'font'     => 'Arial, Helvetica, sans-serif',
            ],
            'when'                  => 'immediately',
            'frequency'             => 'everypage',
            'stop_after_conversion' => 1,
        ],
    ];

    public function testFocusApiNew(): void
    {
        // Create a focus item.
        $this->client->request(Request::METHOD_POST, '/api/focus/new', $this->testPayload);
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());

        $createdItem = json_decode($response->getContent(), true)['focus'];

        Assert::assertNotEmpty($createdItem['id'], $response->getContent());
        Assert::assertSame($this->testPayload['name'], $createdItem['name'], $response->getContent());
    }
}
