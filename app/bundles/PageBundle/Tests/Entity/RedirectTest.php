<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\PageBundle\Entity\Redirect;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase
{
    public function testGetUrlRemovesWhitespace(): void
    {
        $redirect  = new Redirect();
        $reflected = new \ReflectionClass(Redirect::class);
        $property  = $reflected->getProperty('url');

        $property->setValue($redirect, 'https://example.com '); // trailing whitespace

        Assert::assertSame('https://example.com', $redirect->getUrl());
    }

    public function testSetUrlRemovesWhitespace(): void
    {
        $redirect  = new Redirect();
        $reflected = new \ReflectionClass(Redirect::class);
        $property  = $reflected->getProperty('url');

        $redirect->setUrl('https://example.com '); // trailing whitespace

        Assert::assertSame('https://example.com', $property->getValue($redirect));
    }
}
