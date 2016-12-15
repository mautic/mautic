<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Test;

use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrackableModelTest extends WebTestCase
{
    /**
     * @testdox Test that content is detected as HTML
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testHtmlIsDetectedInContent()
    {
        $mockRedirectModel = $this->getMockBuilder('Mautic\PageBundle\Model\RedirectModel')
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder('Mautic\PageBundle\Model\TrackableModel')
            ->setConstructorArgs([$mockRedirectModel])
            ->setMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromHtml'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromHtml')
            ->willReturn(
                [
                    '',
                    [],
                ]
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn([]);

        list($content, $trackables) = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'html'),
            [],
            'email',
            1
        );
    }

    /**
     * @testdox Test that content is detected as plain text
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromText
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testPlainTextIsDetectedInContent()
    {
        $mockRedirectModel = $this->getMockBuilder('Mautic\PageBundle\Model\RedirectModel')
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder('Mautic\PageBundle\Model\TrackableModel')
            ->setConstructorArgs([$mockRedirectModel])
            ->setMethods(['getDoNotTrackList', 'getEntitiesFromUrls', 'createTrackingTokens',  'extractTrackablesFromText'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getEntitiesFromUrls')
            ->willReturn([]);

        $mockModel->expects($this->once())
            ->method('extractTrackablesFromText')
            ->willReturn(
                [
                    '',
                    [],
                ]
            );

        $mockModel->expects($this->once())
            ->method('createTrackingTokens')
            ->willReturn([]);

        list($content, $trackables) = $mockModel->parseContentForTrackables(
            $this->generateContent('https://foo-bar.com', 'text'),
            [],
            'email',
            1
        );
    }

    /**
     * @testdox Test that a standard link with a standard query is parsed correctly
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithStandardQuery()
    {
        $url   = 'https://foo-bar.com?foo=bar';
        $model = $this->getModel($url);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals($url, $redirect->getUrl());
    }

    /**
     * @testdox Test that a standard link without a query parses correctly
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithoutQuery()
    {
        $url   = 'https://foo-bar.com';
        $model = $this->getModel($url);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey($match[0], $trackables);

        // Assert that the URL redirect equals $url
        $redirect = $trackables[$match[0]]->getRedirect();
        $this->assertEquals($url, $redirect->getUrl());
    }

    /**
     * @testdox Test that a standard link with a tokenized query parses correctly
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testStandardLinkWithTokenizedQuery()
    {
        $url   = 'https://foo-bar.com?foo={contactfield=bar}&bar=foo';
        $model = $this->getModel($url, 'https://foo-bar.com?bar=foo');

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=bar}' => '',
            ],
            'email',
            1
        );

        $tokenFound = preg_match('/\{trackable=(.*?)\}&foo=\{contactfield=bar\}/', $content, $match);

        // Assert that a trackable token exists
        $this->assertTrue((bool) $tokenFound, $content);

        // Assert the Trackable exists
        $this->assertArrayHasKey('{trackable='.$match[1].'}', $trackables);
    }

    /**
     * @testdox Test that a token used in place of a URL is not parsed
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testTokenizedHostIsIgnored()
    {
        $url   = 'http://{contactfield=foo}.com';
        $model = $this->getModel($url, 'http://{contactfield=foo}.com');

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{contactfield=foo}' => '',
            ],
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
    }

    /**
     * @testdox Test that tokens that are supposed to be ignored are
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testIgnoredTokensAreNotConverted()
    {
        $url   = 'https://{unsubscribe_url}';
        $model = $this->getModel($url, null, ['{unsubscribe_url}']);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{unsubscribe_url}' => 'https://domain.com/email/unsubscribe/xxxxxxx',
            ],
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
        $this->assertFalse(strpos($url, $content), 'https:// should have been stripped from the token URL');
    }

    /**
     * @testdox Test that tokens that are supposed to be ignored are
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testUnsupportedTokensAreNotConverted()
    {
        $url   = '{random_token}';
        $model = $this->getModel($url);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'text'),
            [
                '{unsubscribe_url}' => 'https://domain.com/email/unsubscribe/xxxxxxx',
            ],
            'email',
            1
        );

        $this->assertEmpty($trackables, $content);
    }

    /**
     * @testdox Test that a URL injected into the do not track list is not converted
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testIgnoredUrlDoesNotCrash()
    {
        $url   = 'https://domain.com';
        $model = $this->getModel($url, null, [$url]);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [],
            'email',
            1
        );

        $this->assertTrue((strpos($content, $url) !== false), $content);
    }

    /**
     * @testdox Test that a token used in place of a URL is not parsed
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::validateTokenIsTrackable
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     */
    public function testTokenAsHostIsConvertedToTrackableToken()
    {
        $url   = 'http://{pagelink=1}';
        $model = $this->getModel($url, 'http://foo-bar.com');

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($url, 'html'),
            [
                '{pagelink=1}' => 'http://foo-bar.com',
            ],
            'email',
            1
        );

        $this->assertNotEmpty($trackables, $content);
    }

    /**
     * @testdox Test that a URLs with same base or correctly replaced
     *
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareContentWithTrackableTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::parseContentForTrackables
     * @covers \Mautic\PageBundle\Model\TrackableModel::extractTrackablesFromHtml
     * @covers \Mautic\PageBundle\Model\TrackableModel::createTrackingTokens
     * @covers \Mautic\PageBundle\Model\TrackableModel::prepareUrlForTracking
     */
    public function testUrlsWithSameBaseAreReplacedCorrectly()
    {
        $urls = [
            'https://foo-bar.com',
            'https://foo-bar.com?foo=bar',
        ];

        $model = $this->getModel($urls);

        list($content, $trackables) = $model->parseContentForTrackables(
            $this->generateContent($urls, 'html'),
            [],
            'email',
            1
        );

        foreach ($trackables as $redirectId => $trackable) {
            // If the shared base was correctly parsed, all generated tokens will be in the content
            $this->assertNotFalse(strpos($content, $redirectId), $content);
        }
    }

    /**
     * @param       $urls
     * @param null  $tokenUrls
     * @param array $doNotTrack
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getModel($urls, $tokenUrls = null, $doNotTrack = [])
    {
        if (!is_array($urls)) {
            $urls = [$urls];
        }
        if (null === $tokenUrls) {
            $tokenUrls = $urls;
        } elseif (!is_array($tokenUrls)) {
            $tokenUrls = [$tokenUrls];
        }

        $mockRedirectModel = $this->getMockBuilder('Mautic\PageBundle\Model\RedirectModel')
            ->disableOriginalConstructor()
            ->getMock();

        $mockModel = $this->getMockBuilder('Mautic\PageBundle\Model\TrackableModel')
            ->setConstructorArgs([$mockRedirectModel])
            ->setMethods(['getDoNotTrackList', 'getEntitiesFromUrls'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getDoNotTrackList')
            ->willReturn($doNotTrack);

        $entities = [];
        foreach ($urls as $k => $url) {
            $entities[$url] = $this->getTrackableEntity($tokenUrls[$k]);
        }

        $mockModel->expects($this->any())
            ->method('getEntitiesFromUrls')
            ->willReturn(
                $entities
            );

        return $mockModel;
    }

    /**
     * @param $url
     *
     * @return Trackable
     */
    protected function getTrackableEntity($url)
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $trackable = new Trackable();
        $trackable->setChannel('email')
            ->setChannelId(1)
            ->setRedirect($redirect)
            ->setHits(rand(1, 10))
            ->setUniqueHits(rand(1, 10));

        return $trackable;
    }

    /**
     * @param      $urls
     * @param      $type
     * @param bool $doNotTrack
     *
     * @return string
     */
    protected function generateContent($urls, $type, $doNotTrack = false)
    {
        $content = '';
        if (!is_array($urls)) {
            $urls = [$urls];
        }

        foreach ($urls as $url) {
            if ($type == 'html') {
                $dnc = ($doNotTrack) ? ' mautic:disable-tracking' : '';

                $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 <a href="$url"$dnc>$url</a> 321ABC
CONTENT;
            } else {
                $content .= <<<CONTENT
    ABC123 321ABC
    ABC123 $url 321ABC
CONTENT;
            }
        }

        return $content;
    }
}
