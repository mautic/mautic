<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\DoNotContact;

final class DoNotContactTest extends \PHPUnit\Framework\TestCase
{
    public function testDoNotContactComments(): void
    {
        $doNotContact = new DoNotContact();
        $doNotContact->setComments('');
        $this->assertSame('', $doNotContact->getComments());

        $comment      = '<script>alert(\'x\')</script>';
        $doNotContact->setComments($comment);
        $this->assertNotSame($comment, $doNotContact->getComments());
        $this->assertSame('alert(\'x\')', $doNotContact->getComments());
    }
}
