<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testCloneResetPublishDates(): void
    {
        $email = new Email();
        $email->setPublishUp(new \DateTime());
        $email->setPublishDown(new \DateTime());
        $emailClone = clone $email;
        $this->assertNull($emailClone->getPublishUp());
        $this->assertNull($emailClone->getPublishDown());
    }

    public function testCloneResetPlainText(): void
    {
        $email = new Email();
        $email->setPlainText('foo');
        $emailClone = clone $email;
        $this->assertNull($emailClone->getPlainText());
    }
}
