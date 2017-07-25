<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageSubscriberTest extends WebTestCase
{
    public function testGetTokens_WhenCalled_ReturnsValidTokens()
    {
        $translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()
            ->getMock();

        $pageBuilderEvent = new PageBuilderEvent($translator);
        $pageBuilderEvent->addToken('{token_test}', 'TOKEN VALUE');
        $tokens = $pageBuilderEvent->getTokens();
        $this->assertArrayHasKey('{token_test}', $tokens);
        $this->assertEquals($tokens['{token_test}'], 'TOKEN VALUE');
    }
}
