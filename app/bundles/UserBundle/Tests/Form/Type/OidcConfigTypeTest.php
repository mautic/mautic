<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Form\Type\ConfigType;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OidcConfigTypeTest extends TestCase
{
    public function testOnPreSubmitSetsSecretToParamValueIfEmpty(): void
    {
        $form  = $this->createStub(FormInterface::class);
        $event = new PreSubmitEvent($form, ['open_id_client_secret' => '']);
        $this->createConfigForm()->onPreSubmit($event);

        $this->assertSame('client_secret', $event->getData()['open_id_client_secret']);
    }

    public function testOnPreSubmitDoesNotSetSecretToParamValueIfNotEmpty(): void
    {
        $form  = $this->createStub(FormInterface::class);
        $event = new PreSubmitEvent($form, ['open_id_client_secret' => 'notNull']);
        $this->createConfigForm()->onPreSubmit($event);

        $this->assertSame('notNull', $event->getData()['open_id_client_secret']);
    }

    public function testBuildFormAddsFieldsWhenOpenIdIsEnabled(): void
    {
        $builder = $this->createFormBuilder();
        $this->createConfigForm()->buildForm($builder, []);
        $this->assertOpenIdFieldsExist($builder);
    }

    // we always want to add the fields so the user can enable/disable OpenID
    public function testBuildFormAddsFieldsWhenOpenIdIsDisabled(): void
    {
        $builder = $this->createFormBuilder();
        $this->createConfigForm()->buildForm($builder, []);
        $this->assertOpenIdFieldsExist($builder);
    }

    private function createFormBuilder(): FormBuilder
    {
        return new FormBuilder(
            'config',
            ConfigType::class,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(FormFactoryInterface::class)
        );
    }

    private function assertOpenIdFieldsExist(FormBuilder $builder): void
    {
        $this->assertTrue($builder->has('open_id_is_enabled'));
        $this->assertTrue($builder->has('open_id_is_required'));
        $this->assertTrue($builder->has('open_id_is_user_registration_allowed'));
        $this->assertTrue($builder->has('open_id_registered_user_role'));
        $this->assertTrue($builder->has('open_id_client_url'));
        $this->assertTrue($builder->has('open_id_client_id'));
        $this->assertTrue($builder->has('open_id_client_secret'));
        $this->assertTrue($builder->has('open_id_mapping_field'));
    }

    private function createConfigForm(): ConfigType
    {
        $coreParameterHelper = $this->createStub(CoreParametersHelper::class);
        $coreParameterHelper->method('get')->willReturn('some_value');

        return new ConfigType(
            $coreParameterHelper,
            $this->createStub(TranslatorInterface::class),
            new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub'),
        );
    }
}
