<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Form\Type\NotificationType;
use PHPUnit\Framework\Assert;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NotificationTypeTest extends TypeTestCase
{
    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $validatorBuilder = Validation::createValidatorBuilder();
        $validatorBuilder->addMethodMapping('loadValidatorMetadata');

        return [
            new ValidatorExtension($validatorBuilder->getValidator()),
            new PreloadedExtension([
                new CategoryListType(
                    $this->createStub(EntityManager::class),
                    $this->createStub(TranslatorInterface::class),
                    $this->createStub(CategoryModel::class),
                    $this->createStub(RouterInterface::class),
                ),
            ], []),
        ];
    }

    public function testSubmitInvalidData(): void
    {
        $form = $this->factory->create(NotificationType::class);

        $expected = new Notification();
        $expected->setLanguage('en');
        $expected->setUtmTags([
            'utmSource'   => null,
            'utmMedium'   => null,
            'utmCampaign' => null,
            'utmContent'  => null,
        ]);
        $expected->setIsPublished(false);

        $form->submit([
            'language' => 'en',
        ]);

        Assert::assertTrue($form->isSynchronized());

        $formData = $form->getData();
        $this->assertInstanceOf(Notification::class, $formData);

        $expected->setChanges($formData->getChanges());
        Assert::assertEquals($expected, $formData);

        Assert::assertFalse($form->isValid());

        $view          = $form->createView();
        $invalidFields = ['name', 'heading', 'message'];
        $errorCount    = 0;
        foreach ($view->children as $fieldName => $child) {
            $errors = $view->children[$fieldName]->vars['errors'];
            $this->assertInstanceOf(FormErrorIterator::class, $errors);

            if (in_array($fieldName, $invalidFields, true)) {
                ++$errorCount;
                self::assertCount(1, $errors);
                continue;
            }

            self::assertCount(0, $errors);
        }

        self::assertCount($errorCount, $invalidFields);
        self::assertCount(0, $view->vars['errors']);
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(NotificationType::class);

        $expected = new Notification();
        $expected->setLanguage('en');
        $expected->setName('The name');
        $expected->setHeading('The heading');
        $expected->setMessage('The message');
        $expected->setUtmTags([
            'utmSource'   => null,
            'utmMedium'   => null,
            'utmCampaign' => null,
            'utmContent'  => null,
        ]);
        $expected->setIsPublished(false);

        $form->submit([
            'name'     => 'The name',
            'heading'  => 'The heading',
            'message'  => 'The message',
            'language' => 'en',
        ]);

        Assert::assertTrue($form->isSynchronized());

        $formData = $form->getData();
        $this->assertInstanceOf(Notification::class, $formData);

        $expected->setChanges($formData->getChanges());
        Assert::assertEquals($expected, $formData);

        Assert::assertTrue($form->isValid());

        $view = $form->createView();
        foreach ($view->children as $fieldName => $child) {
            $errors = $view->children[$fieldName]->vars['errors'];
            $this->assertInstanceOf(FormErrorIterator::class, $errors);
            self::assertCount(0, $errors);
        }

        self::assertCount(0, $view->vars['errors']);
    }
}
