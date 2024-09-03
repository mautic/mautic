<?php

namespace Mautic\CoreBundle\Tests\Unit\IpLookup;

use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Exception\FileNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList;

class MaxMindDoNotSellListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CoreParametersHelper
     */
    private \PHPUnit\Framework\MockObject\MockObject $coreParamsHelperMock;

    private $badFilePath = 'bad_list.json';

    private $badData     = 'bad data';

    private $goodFilePath = 'good_list.json';

    private $goodData     = '{
                                "exclusions": [
                                    {
                                      "exclusion_type": "ccpa_do_not_sell",
                                      "data_type": "network",
                                      "value": "108.208.26.166/32",
                                      "last_updated": "2020-01-08T18:58:38Z"
                                    }
                                ]
                              }';

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreParamsHelperMock = $this->createMock(CoreParametersHelper::class);

        file_put_contents($this->badFilePath, $this->badData);
        file_put_contents($this->goodFilePath, $this->goodData);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_file($this->goodFilePath)) {
            unlink($this->goodFilePath);
        }

        if (is_file($this->badFilePath)) {
            unlink($this->badFilePath);
        }
    }

    /**
     * Test trying to load the list when the list file path hasn't been configured.
     */
    public function testListPathNotConfigured(): void
    {
        $coreParamsHelperMock = $this->coreParamsHelperMock;
        $coreParamsHelperMock->method('get')
            ->with('maxmind_do_not_sell_list_path')
            ->willReturn('');

        $this->expectException(BadConfigurationException::class);

        $doNotSellList = new MaxMindDoNotSellList($this->coreParamsHelperMock);
        $doNotSellList->loadList();
    }

    /**
     * Test trying to load the list when the list file path hasn't been configured.
     */
    public function testListFileNotDownloaded(): void
    {
        $coreParamsHelperMock = $this->coreParamsHelperMock;
        $coreParamsHelperMock->method('get')
            ->with('maxmind_do_not_sell_list_path')
            ->willReturn('path_to_missing_file.json');

        $this->expectException(FileNotFoundException::class);

        $doNotSellList = new MaxMindDoNotSellList($this->coreParamsHelperMock);
        $doNotSellList->loadList();
    }

    /**
     * Test loading a Do Not Sell List file that is not properly formatted.
     */
    public function testFileWithBadData(): void
    {
        $coreParamsHelperMock = $this->coreParamsHelperMock;
        $coreParamsHelperMock->method('get')
            ->with('maxmind_do_not_sell_list_path')
            ->willReturn($this->badFilePath);

        $doNotSellList = new MaxMindDoNotSellList($this->coreParamsHelperMock);

        $this->assertEquals($this->badFilePath, $doNotSellList->getListPath());
        $this->assertFalse($doNotSellList->loadList());
        $this->assertEquals([], $doNotSellList->getList());
    }

    /**
     * Test loading the Do Not Sell List file when everything goes right.
     */
    public function testSuccessfulFileLoad(): void
    {
        $coreParamsHelperMock = $this->coreParamsHelperMock;
        $coreParamsHelperMock->method('get')
            ->with('maxmind_do_not_sell_list_path')
            ->willReturn($this->goodFilePath);

        $doNotSellList = new MaxMindDoNotSellList($this->coreParamsHelperMock);
        $doNotSellList->loadList();

        $this->assertEquals($this->goodFilePath, $doNotSellList->getListPath());

        $goodData = json_decode($this->goodData, true)['exclusions'];
        $this->assertEquals($goodData, $doNotSellList->getList());
    }
}
