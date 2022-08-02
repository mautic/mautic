<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\DoNotContact;

class DoNotContactTest extends \PHPUnit\Framework\TestCase
{
    public function testDoNotContactComments()
    {
        $doNotContact = new DoNotContact();
        $doNotContact->setComments(null);
        $this->assertSame('', $doNotContact->getComments());

        $comment      = '<script>alert(\'x\')</script>';
        $doNotContact->setComments($comment);
        $this->assertNotSame($comment, $doNotContact->getComments());
        $this->assertSame('alert(\'x\')', $doNotContact->getComments());
    }
}
