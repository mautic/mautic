<?php

namespace Mautic\PluginBundle\Tests\Entity;

use Mautic\PluginBundle\Entity\Plugin;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyDescription(): void
    {
        $plugin = new Plugin();
        $this->assertNull($plugin->getDescription());
        $this->assertNull($plugin->getPrimaryDescription());
        $this->assertNull($plugin->getSecondaryDescription());
        $this->assertFalse($plugin->hasSecondaryDescription());
    }

    public function testSimpleDescription(): void
    {
        $description = 'This is the best plugin in the whole galaxy';
        $plugin      = new Plugin();
        $plugin->setDescription($description);
        $this->assertEquals($description, $plugin->getDescription());
        $this->assertEquals($description, $plugin->getPrimaryDescription());
        $this->assertNull($plugin->getSecondaryDescription());
        $this->assertFalse($plugin->hasSecondaryDescription());
    }

    public function testSecondaryDescriptionWithUnixLineEnding(): void
    {
        $description = "This is the best plugin in the whole galaxy\n---\nLearn more about it <a href=\"#\">here</a>";
        $plugin      = new Plugin();
        $plugin->setDescription($description);
        $this->assertEquals($description, $plugin->getDescription());
        $this->assertEquals('This is the best plugin in the whole galaxy', $plugin->getPrimaryDescription());
        $this->assertEquals('Learn more about it <a href="#">here</a>', $plugin->getSecondaryDescription());
        $this->assertTrue($plugin->hasSecondaryDescription());
    }

    public function testSecondaryDescriptionWithWinLineEnding(): void
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
