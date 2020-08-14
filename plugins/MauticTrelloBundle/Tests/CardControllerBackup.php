<?php

declare(strict_types=1);
/**
 * @author      Mautic

 * @copyright   2020 Mautic Contributors. All rights reserved
 *
 * @see        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticTrelloBundle\Tests;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticTrelloBundle\Controller\CardController;
use MauticPlugin\MauticTrelloBundle\Openapi\lib\Api\DefaultApi;
use MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\TrelloList;
use MauticPlugin\MauticTrelloBundle\Service\TrelloApiService;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Test the Service providing the auto generated Trello API to the MauticTrelloBundle.
 */
class CardControllerTest extends TestCase
{
    /**
     * The Api to Trello.
     *
     * @var MauticPlugin\MauticTrelloBundle\Openapi\lib\Api\DefaultApi
     */
    private $api;

    /**
     * A service for the OpenAPI client.
     */
    private $integration;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    protected function setUp()
    {
        parent::setUp();

        // $this->integration = $this->getMockBuilder(CardController::class)
        // ->disableOriginalConstructor()
        // ->setMethods()
        // ->setConstructorArgs([
        //     $this->createMock(IntegrationHelper::class),
        //     $this->createMock(CoreParametersHelper::class),
        //     $this->createMock(Logger::class),
        // ])
        // ->getMock();

        // $this->api = $this->integration->getApi();
    }

    // public static function setUpBeforeClass(): void
    // {
    //     $lists = new TrelloList();
    // }

    // public function testIsApiAvailable()
    // {
    //     // $test = new TrelloApiService(
    //     //     $this->createMock(IntegrationHelper::class),
    //     //     $this->createMock(CoreParametersHelper::class),
    //     //     $this->createMock(Logger::class),
    //     // );
    //     $this->assertInstanceOf(DefaultApi::class, $this->api);
    // }

    public function testShowNewCardAction()
    {
        $cardController = new CardController();
        $view           = $cardController->showNewCardAction(1);
        echo '<pre>';
        echo '<h1>view</h1>';
        print_r($view);
        echo '</pre>';
        exit;

        return;
        // Create a stub for the DefaultApi class.
        $stub = $this->createMock(TrelloApiService::class);

        // Configure the stub.
        $stub->method('getListsOnBoard')
        ->willReturn([
            'id'   => '5e5c1f8f49c26f3ef826eba4',
            'name' => '1. Interesting Lead',
            'pos'  => 65535,
        ]);

        // Calling $stub->doSomething() will now return
        // 'foo'.
        $this->assertSame(['foo'], $stub->getListsOnBoard());
    }
}

// [
//     {
//         "id": "5e5c1f8f49c26f3ef8b6eba4",
//         "name": "1. Interesting Lead",
//         "pos": 65535
//     },
//     {
//         "id": "5e5c1f9aa8fe55462a918ceb",
//         "name": "2. Intro gesendet",
//         "pos": 131071
//     },
//     {
//         "id": "5e5c1f9e8ba0a9406069f3a8",
//         "name": "3. Lead Magnet heruntergeladen",
//         "pos": 196607
//     },
//     {
//         "id": "5e5c1fa1c959c2221c1d9bc2",
//         "name": "4. Call/Treffen scheduled",
//         "pos": 262143
//     },
//     {
//         "id": "5e5c1fa6f8a36f344a4942fe",
//         "name": "5. Angebot unterbreitet",
//         "pos": 327679
//     },
//     {
//         "id": "5ea7f6935b83963731cb9340",
//         "name": "6. Nachgefasst",
//         "pos": 393215
//     },
//     {
//         "id": "5ea7f6b2dca6c9842ed97cdc",
//         "name": "7. Gewonnen 🎉",
//         "pos": 458751
//     },
//     {
//         "id": "5ea7f6bc62945313ebab186b",
//         "name": "8. Verloren, weil?",
//         "pos": 524287
//     },
//     {
//         "id": "5ea7f6fbd59eb82730fc560f",
//         "name": "A. Nurture",
//         "pos": 589823
//     },
//     {
//         "id": "5ea7f913a1694626a66a5d71",
//         "name": "Linkliste",
//         "pos": 655359
//     }
// ]
