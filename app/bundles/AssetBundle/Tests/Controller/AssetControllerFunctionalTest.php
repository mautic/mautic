<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;

class AssetControllerFunctionalTest extends MauticMysqlTestCase
{
    use ControllerTrait;

    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $asset = new Asset();
        $asset->setTitle('test');
        $asset->setAlias('test');
        $asset->setDateAdded(new \DateTime('2020-02-07 20:29:02'));
        $asset->setDateModified(new \DateTime('2020-03-21 20:29:02'));
        $asset->setCreatedByUser('Test User');

        $this->em->persist($asset);
        $this->em->flush();
        $this->em->clear();

        $urlAlias   = 'assets';
        $routeAlias = 'asset';
        $column     = 'dateModified';
        $column2    = 'title';
        $tableAlias = 'a.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }

    public function testNotValidTagsInDescriptionField(): void
    {
        $crawlerGet          = $this->client->request('GET', '/s/assets/new');
        $form                = $crawlerGet->filter('form[name="asset"]')->form();
        $text['tagP']        = '<p>Test Mautic Strict Html Description</p>';
        $text['script']      = "<span><script>alert('2222')</script></span>";
        $text['onmouseover'] = '<span><a onmouseover="alert(document.cookie)" href="https://www.w3schools.com">Visit W3Schools.com!</a></span>';
        $text['tagMarquee']  = '<span><marquee>Lorem ipsum</marquee> is the most popular filler text in history.</span>';
        $text['div']         = '<span><div>Lorem ipsum</div> is the most popular filler text in history.</p>';
        $htmlText            = implode('', $text);
        $form->setValues(
            [
                'asset[title]'           => 'Test Asset',
                'asset[alias]'           => 'test-asset',
                'asset[description]'     => $htmlText,
                'asset[remotePath]'      => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'asset[storageLocation]' => 'remote',
            ]
        );

        $crawlerPost = $this->client->submit($form);
        $htmlDecode  = htmlspecialchars_decode($crawlerPost->html());
        $this->assertStringContainsString('Edit Asset |', $htmlDecode);
        $this->assertStringNotContainsString($text['tagP'], $htmlDecode);
        $this->assertStringNotContainsString($text['script'], $htmlDecode);
        $this->assertStringNotContainsString($text['onmouseover'], $htmlDecode);
        $this->assertStringNotContainsString($text['tagMarquee'], $htmlDecode);
        $this->assertStringNotContainsString($text['div'], $htmlDecode);
        $this->assertStringContainsString('Test Mautic Strict Html Description', $htmlDecode);
        $this->assertStringContainsString('<span><a href="https://www.w3schools.com">Visit W3Schools.com!</a></span>', $htmlDecode);
        $this->assertStringContainsString('<span>Lorem ipsum is the most popular filler text in history.</span>', $htmlDecode);
    }

    public function testValidTagsInDescriptionField(): void
    {
        $crawlerGet          = $this->client->request('GET', '/s/assets/new');
        $form                = $crawlerGet->filter('form[name="asset"]')->form();
        $text['tagA']        = '<span><a href="https://www.w3schools.com">Visit W3Schools.com!</a></span>';
        $text['tagI']        = '<span><i>Lorem ipsum</i> is the most popular filler text in history.</span>';
        $text['tagB']        = '<span><b>Lorem ipsum</b> is the most popular filler text in history.</span>';
        $text['tagU']        = '<span><u>Lorem ipsum</u> is the most popular filler text in history.</span>';
        $text['tagEm']       = '<span><em>Lorem ipsum</em> is the most popular filler text in history.</span>';
        $text['tagStrong']   = '<span><strong>Lorem ipsum</strong> is the most popular filler text in history.</span>';
        $text['tagSpan']     = '<span><span>Lorem ipsum</span> is the most popular filler text in history.</span>';
        $htmlText            = implode('', $text);
        $form->setValues(
            [
                'asset[title]'           => 'Test Asset',
                'asset[alias]'           => 'test-asset',
                'asset[description]'     => $htmlText,
                'asset[remotePath]'      => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'asset[storageLocation]' => 'remote',
            ]
        );

        $crawlerPost = $this->client->submit($form);
        $htmlDecode  = htmlspecialchars_decode($crawlerPost->html());
        $this->assertStringContainsString('Edit Asset |', $htmlDecode);
        $this->assertStringContainsString($text['tagA'], $htmlDecode);
        $this->assertStringContainsString($text['tagI'], $htmlDecode);
        $this->assertStringContainsString($text['tagB'], $htmlDecode);
        $this->assertStringContainsString($text['tagU'], $htmlDecode);
        $this->assertStringContainsString($text['tagEm'], $htmlDecode);
        $this->assertStringContainsString($text['tagStrong'], $htmlDecode);
        $this->assertStringContainsString($text['tagSpan'], $htmlDecode);
    }
}
