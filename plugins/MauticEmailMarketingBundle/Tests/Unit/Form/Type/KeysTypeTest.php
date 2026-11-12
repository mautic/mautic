<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Form\Type;

use MauticPlugin\MauticEmailMarketingBundle\Form\Type\IcontactKeysType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

/**
 * The keys form outside of the integration settings form; the settings pages are covered by functional tests.
 */
final class KeysTypeTest extends TestCase
{
    public function testKeysAreNotRequiredOutsideOfAPublishableForm(): void
    {
        $form = $this->createFactory()->create(IcontactKeysType::class);
        $form->submit(['API-AppId' => 'app']);

        $this->assertTrue($form->isValid());
        $this->assertSame(['API-AppId' => 'app', 'API-Username' => null, 'API-Password' => null], $form->getData());
    }

    public function testBlankSecretKeepsTheSavedOne(): void
    {
        $form = $this->createFactory()->create(IcontactKeysType::class, ['API-AppId' => 'app', 'API-Username' => 'user', 'API-Password' => 'secret', 'accountId' => '1']);
        $form->submit(['API-AppId' => 'app2', 'API-Username' => 'user', 'API-Password' => '']);

        $this->assertSame(['API-AppId' => 'app2', 'API-Username' => 'user', 'API-Password' => 'secret', 'accountId' => '1'], $form->getData());
    }

    private function createFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
    }
}
