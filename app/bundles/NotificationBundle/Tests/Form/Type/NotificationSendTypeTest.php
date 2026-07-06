<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Form\Type;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Mautic\NotificationBundle\Form\Type\NotificationSendType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NotificationSendTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        parent::setup();
    }

    /**
     * @return array<mixed>
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([
                new NotificationSendType($this->createStub(RouterInterface::class)),
                new EntityLookupType($this->createStub(ModelFactory::class), $this->createStub(TranslatorInterface::class), $this->createStub(Connection::class), $this->createStub(RouterInterface::class)),
            ], []),
        ];
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(NotificationSendType::class);

        $expected = [
            'notification' => '1',
        ];

        $form->submit([
            'notification' => '1',
        ]);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());

        // check that $model was modified as expected when the form was submitted
        $this->assertEquals($expected, $form->getData());
    }
}
