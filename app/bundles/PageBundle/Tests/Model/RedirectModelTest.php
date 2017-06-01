<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\Model;

use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Tests\PageTestAbstract;

class RedirectModelTest extends PageTestAbstract
{
    public function testGetUrl()
    {
        $redirect      = new Redirect();
        $redirectModel = $this->getRedirectModel();
        $redirect->setUrl('http://some-url.com');
        $this->assertEquals('http://some-url.com', $redirect->getUrl());
    }
}
