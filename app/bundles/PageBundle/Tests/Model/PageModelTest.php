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

use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Tests\PageTestAbstract;

class PageModelTest extends PageTestAbstract
{
    public function testCreate()
    {
        $page      = new Page();
        $pageModel = $this->getPageModel();
    }
}
