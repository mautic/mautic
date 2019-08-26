<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests\Entity;

use Mautic\PluginBundle\Entity\Plugin;

class PluginTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyDescription()
    {
        $plugin = new Plugin();
        $this->assertNull($plugin->getDescription());
        $this->assertNull($plugin->getPrimaryDescription());
        $this->assertNull($plugin->getSecondaryDescription());
        $this->assertFalse($plugin->hasSecondaryDescription());
    }

    public function testSimpleDescription()
    {
        $description = 'This is the best plugin in the whole galaxy';
        $plugin      = new Plugin();
        $plugin->setDescription($description);
        $this->assertEquals($description, $plugin->getDescription());
        $this->assertEquals($description, $plugin->getPrimaryDescription());
        $this->assertNull($plugin->getSecondaryDescription());
        $this->assertFalse($plugin->hasSecondaryDescription());
    }

    public function testSecondaryDescriptionWithUnixLineEnding()
    {
        $description = "This is the best plugin in the whole galaxy\n---\nLearn more about it <a href=\"#\">here</a>";
        $plugin      = new Plugin();
        $plugin->setDescription($description);
        $this->assertEquals($description, $plugin->getDescription());
        $this->assertEquals('This is the best plugin in the whole galaxy', $plugin->getPrimaryDescription());
        $this->assertEquals('Learn more about it <a href="#">here</a>', $plugin->getSecondaryDescription());
        $this->assertTrue($plugin->hasSecondaryDescription());
    }

    public function testSecondaryDescriptionWithWinLineEnding()
    {
        $description = "This is the best plugin in the whole galaxy\n\r---\n\rLearn more about it <a href=\"#\">here</a>";
        $plugin      = new Plugin();
        $plugin->setDescription($description);
        $this->assertEquals($description, $plugin->getDescription());
        $this->assertEquals('This is the best plugin in the whole galaxy', $plugin->getPrimaryDescription());
        $this->assertEquals('Learn more about it <a href="#">here</a>', $plugin->getSecondaryDescription());
        $this->assertTrue($plugin->hasSecondaryDescription());
    }
}
