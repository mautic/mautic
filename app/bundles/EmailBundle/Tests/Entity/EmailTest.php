<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testCloneResetPlainText(): void
    {
        $email = new Email();
        $email->setPlainText('foo');
        $emailClone = clone $email;
        $this->assertNull($emailClone->getPlainText());
    }
}
