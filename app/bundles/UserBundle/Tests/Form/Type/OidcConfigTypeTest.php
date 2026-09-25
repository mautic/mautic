<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Type;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Form\Type\ConfigType;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Settings;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
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

    public function testOnPostEventTruncatesTableWhenMappingFieldIsChanged(): void
    {
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(AbstractQuery::class);
        $form         = $this->createStub(FormInterface::class);
        $event        = new PostSubmitEvent($form, ['open_id_mapping_field' => 'platform_sub']);

        $subjectIdRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('delete')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute');

        $this->createConfigForm($subjectIdRepository)->onPostSubmit($event);
    }

    public function testOnPostEventDoesNotTruncatesTableWhenMappingFieldIsUnchanged(): void
    {
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $form                = $this->createStub(FormInterface::class);
        $event               = new PostSubmitEvent($form, ['open_id_mapping_field' => 'sub']);

        $subjectIdRepository->expects($this->never())
            ->method('createQueryBuilder');

        $this->createConfigForm($subjectIdRepository)->onPostSubmit($event);
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
        $this->createConfigForm(null, (new ParametersBuilder())->withIsEnabled(false)->build())->buildForm($builder, []);
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

    private function createConfigForm(?OidcSubjectIdRepository $subjectIdRepository = null, ?Settings $settings = null): ConfigType
    {
        $coreParameterHelper = $this->createStub(CoreParametersHelper::class);

        $coreParameterHelper->method('get')->willReturn('some_value');

        return new ConfigType(
            $coreParameterHelper,
            $this->createStub(TranslatorInterface::class),
            $settings ?? (new ParametersBuilder())->build(),
            new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub'),
            $subjectIdRepository ?? $this->createStub(OidcSubjectIdRepository::class),
        );
    }
}
