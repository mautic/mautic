<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Tests\Unit\Form\Type;

use MauticPlugin\MauticCloudStorageBundle\Form\Type\AmazonS3KeysType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class AmazonS3KeysTypeTest extends TypeTestCase
{
    /**
     * @return array<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([new AmazonS3KeysType()], []),
        ];
    }

    /**
     * @param array<string, string> $existingData
     */
    private function createForm(array $existingData = []): FormInterface
    {
        return $this->factory->create(AmazonS3KeysType::class, $existingData);
    }

    public function testRegionDefaultsToUsEast1WhenNotSet(): void
    {
        $form = $this->createForm([]);

        $this->assertSame('us-east-1', $form->get('region')->getData());
    }

    public function testRegionKeepsExistingValue(): void
    {
        $form = $this->createForm(['region' => 'eu-north-1']);

        $this->assertSame('eu-north-1', $form->get('region')->getData());
    }

    public function testClientIdIsRequired(): void
    {
        $form = $this->createForm([
            'client_id'     => '',
            'client_secret' => 'existing-secret',
            'bucket'        => 'my-bucket',
        ]);

        $form->submit([
            'client_id'     => '',
            'client_secret' => '',
            'bucket'        => 'my-bucket',
            'region'        => 'us-east-1',
            'endpoint'      => '',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, count($form->get('client_id')->getErrors()));
    }

    public function testBucketIsRequired(): void
    {
        $form = $this->createForm([
            'client_id'     => 'existing-id',
            'client_secret' => 'existing-secret',
            'bucket'        => '',
        ]);

        $form->submit([
            'client_id'     => 'my-id',
            'client_secret' => '',
            'bucket'        => '',
            'region'        => 'us-east-1',
            'endpoint'      => '',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, count($form->get('bucket')->getErrors()));
    }

    public function testClientSecretIsRequiredWhenNoneSavedYet(): void
    {
        $form = $this->createForm([
            'client_id'     => '',
            'client_secret' => '',
            'bucket'        => '',
        ]);

        $form->submit([
            'client_id'     => 'my-id',
            'client_secret' => '',
            'bucket'        => 'my-bucket',
            'region'        => 'us-east-1',
            'endpoint'      => '',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, count($form->get('client_secret')->getErrors()));
    }

    public function testClientSecretIsNotRequiredWhenAlreadySaved(): void
    {
        $form = $this->createForm([
            'client_id'     => 'existing-id',
            'client_secret' => 'existing-secret',
            'bucket'        => 'existing-bucket',
        ]);

        $form->submit([
            'client_id'     => 'changed-id',
            'client_secret' => '',
            'bucket'        => 'existing-bucket',
            'region'        => 'us-east-1',
            'endpoint'      => '',
        ]);

        $this->assertTrue($form->isValid());
    }

    public function testClientSecretIsPreservedWhenSubmittedBlank(): void
    {
        $form = $this->createForm([
            'client_id'     => 'existing-id',
            'client_secret' => 'existing-secret',
            'bucket'        => 'existing-bucket',
        ]);

        $form->submit([
            'client_id'     => 'changed-id',
            'client_secret' => '',
            'bucket'        => 'existing-bucket',
            'region'        => 'us-east-1',
            'endpoint'      => '',
        ]);

        $this->assertSame('existing-secret', $form->getData()['client_secret']);
        $this->assertSame('changed-id', $form->getData()['client_id']);
    }

    public function testClientSecretIsOverwrittenWhenNewValueSubmitted(): void
    {
        $form = $this->createForm([
            'client_id'     => 'existing-id',
            'client_secret' => 'existing-secret',
            'bucket'        => 'existing-bucket',
        ]);

        $form->submit([
            'client_id'     => 'existing-id',
            'client_secret' => 'brand-new-secret',
            'bucket'        => 'existing-bucket',
            'region'        => 'us-east-1',
            'endpoint'      => '',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertSame('brand-new-secret', $form->getData()['client_secret']);
    }

    public function testFullyValidSubmissionIsValid(): void
    {
        $form = $this->createForm([]);

        $form->submit([
            'client_id'     => 'my-id',
            'client_secret' => 'my-secret',
            'bucket'        => 'my-bucket',
            'region'        => 'eu-west-1',
            'endpoint'      => 'https://example.test',
        ]);

        $this->assertTrue($form->isValid());
    }
}
