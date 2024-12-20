<?php

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Form\Type\HtmlType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class HtmlTypeTest extends TestCase
{
    private HtmlType $htmlType;

    public function setUp(): void
    {
        parent::setUp();
        $this->htmlType = new HtmlType();
    }

    public function testGetParent(): void
    {
        $this->assertSame(TextareaType::class, $this->htmlType->getParent());
    }

    public function testGetName(): void
    {
        $this->assertSame('html', $this->htmlType->getBlockPrefix());
    }
}
