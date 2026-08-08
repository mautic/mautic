<?php

declare(strict_types=1);

namespace MauticPlugin\MauticGmailBundle\Tests\Unit\Form\Type;

use MauticPlugin\MauticGmailBundle\Form\Type\GmailKeysType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class GmailKeysTypeTest extends TypeTestCase
{
    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([new GmailKeysType()], []),
        ];
    }

    public function testFormExposesSecretField(): void
    {
        $form = $this->factory->create(GmailKeysType::class);

        $this->assertTrue($form->has('secret'));
    }

    public function testSecretIsRequired(): void
    {
        $form = $this->factory->create(GmailKeysType::class);

        $form->submit(['secret' => '']);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, count($form->get('secret')->getErrors()));
    }

    public function testValidSubmissionPasses(): void
    {
        $form = $this->factory->create(GmailKeysType::class);

        $form->submit(['secret' => 'my-gmail-extension-secret']);

        $this->assertTrue($form->isValid());
        $this->assertSame(['secret' => 'my-gmail-extension-secret'], $form->getData());
    }
}
