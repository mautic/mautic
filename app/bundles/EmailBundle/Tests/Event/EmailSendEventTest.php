<?php

namespace Mautic\EmailBundle\Tests\Event;

use Mautic\EmailBundle\Event\EmailSendEvent;

class EmailSendEventTest extends \PHPUnit\Framework\TestCase
{
    private EmailSendEvent $emailSendEvent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailSendEvent = new EmailSendEvent();
    }

    /**
     * Firstly set HTML content, then set empty plain content. Plain text should be generated.
     */
    public function testSetPlainTextWhenNeedGeneratedPlainText(): void
    {
        $this->emailSendEvent->setContent('<h1>HTML content</h1>');
        $this->emailSendEvent->setPlainText('');
        $this->assertSame('HTML CONTENT', $this->emailSendEvent->getPlainText());
    }

    /**
     * Firstly set HTML content, then set plain content. Plain text should not be generated.
     */
    public function testSetPlainTextWhenNotNeedGeneratedPlainText(): void
    {
        $this->emailSendEvent->setContent('<h1>HTML content</h1>');
        $this->emailSendEvent->setPlainText('plain content');
        $this->assertSame('plain content', $this->emailSendEvent->getPlainText());
    }

    /**
     * Firstly set empty plain content, then set HTML content. Plain text should be generated.
     */
    public function testSetContentWhenNeedGeneratedPlainText(): void
    {
        $this->emailSendEvent->setPlainText('');
        $this->emailSendEvent->setContent('<h1>HTML content</h1>');
        $this->assertSame('HTML CONTENT', $this->emailSendEvent->getPlainText());
    }

    /**
     * Firstly set plain content, then set HTML content. Plain text should not be generated.
     */
    public function testSetContentWhenNotNeedGeneratedPlainText(): void
    {
        $this->emailSendEvent->setPlainText('plain content');
        $this->emailSendEvent->setContent('<h1>HTML content</h1>');
        $this->assertSame('plain content', $this->emailSendEvent->getPlainText());
    }
}
