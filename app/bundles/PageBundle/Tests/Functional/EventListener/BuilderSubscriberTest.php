<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\EventListener;

use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BuilderSubscriberTest extends WebTestCase
{
    public function testOnPageDisplayCorrectlyWrapsForm(): void
    {
        $subscriber = static::$kernel->getContainer()->get('mautic.pagebuilder.subscriber');
        //$event      = new PageDisplayEvent($this->getPageContent(), new Page(), )
    }

    private function getPageContent(): string
    {
        return <<<PAGE
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <title>{pagetitle}</title>
</head>
<body>
    <div>
        <div data-slot="channelfrequency"></div>
    </div>
    <div>
        <div data-slot="saveprefsbutton">
            <a href="javascript:void(null)" class="button btn btn-default btn-save" style="display:inline-block;text-decoration:none;border-color:#4e5d9d;border-width: 10px 20px;border-style:solid;-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; background-color: #4e5d9d; font-size: 16px; color: #ffffff;">
                Save preferences
            </a>        
        </div>
    </div>
</body>
</html>
PAGE;
    }
}
