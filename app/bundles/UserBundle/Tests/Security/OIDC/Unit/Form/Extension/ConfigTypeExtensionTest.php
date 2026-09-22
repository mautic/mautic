<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Form\Extension;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Form\Type\ConfigType;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\ConfigTypeExtension;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;

final class ConfigTypeExtensionTest extends TestCase
{
    public function testGetExtendedTypes(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);

        $this->assertEquals([ConfigType::class], $configTypeExtension::getExtendedTypes());
    }

    public function testOnPreSubmitSetsSecretToParamValueIfEmpty(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $event               = $this->createMock(PreSubmitEvent::class);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['open_id_client_secret' => '']);

        $event->expects($this->once())
            ->method('setData')
            ->with(['open_id_client_secret' => 'client_secret']);

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPreSubmit($event);
    }

    public function testOnPreSubmitDoesNotSetSecretToParamValueIfNotEmpty(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $event               = $this->createMock(PreSubmitEvent::class);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['open_id_client_secret' => 'notNull']);

        $event->expects($this->never())
            ->method('setData');

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPreSubmit($event);
    }

    public function testOnPostEventTruncatesTableWhenMappingFieldIsChanged(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $queryBuilder        = $this->createMock(QueryBuilder::class);
        $query               = $this->createMock(AbstractQuery::class);
        $event               = $this->createMock(PostSubmitEvent::class);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['open_id_mapping_field' => 'platform_sub']);

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

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPostSubmit($event);
    }

    public function testOnPostEventDoesNotTruncatesTableWhenMappingFieldIsUnchanged(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $event               = $this->createMock(PostSubmitEvent::class);

        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['open_id_mapping_field' => 'sub']);

        $subjectIdRepository->expects($this->never())
            ->method('createQueryBuilder');

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPostSubmit($event);
    }

    public function testBuildFormAddsFieldsWhenOpenIdIsEnabled(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $eventDispatcher     = $this->createStub(EventDispatcherInterface::class);
        $formFactory         = $this->createStub(FormFactoryInterface::class);
        $builder             = new FormBuilder('config', ConfigType::class, $eventDispatcher, $formFactory);

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->buildForm($builder, []);

        $this->assertTrue($builder->has('open_id_is_enabled'));
        $this->assertTrue($builder->has('open_id_is_required'));
        $this->assertTrue($builder->has('open_id_is_user_registration_allowed'));
        $this->assertTrue($builder->has('open_id_registered_user_role'));
        $this->assertTrue($builder->has('open_id_client_url'));
        $this->assertTrue($builder->has('open_id_client_id'));
        $this->assertTrue($builder->has('open_id_client_secret'));
        $this->assertTrue($builder->has('open_id_mapping_field'));
    }

    // we always want to add the fields so the user can enable/disable OpenID
    public function testBuildFormAddsFieldsWhenOpenIdIsDisabled(): void
    {
        $parameters          = (new ParametersBuilder())->withIsEnabled(false)->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);
        $eventDispatcher     = $this->createStub(EventDispatcherInterface::class);
        $formFactory         = $this->createStub(FormFactoryInterface::class);
        $builder             = new FormBuilder('config', ConfigType::class, $eventDispatcher, $formFactory);

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->buildForm($builder, []);

        $this->assertTrue($builder->has('open_id_is_enabled'));
        $this->assertTrue($builder->has('open_id_is_required'));
        $this->assertTrue($builder->has('open_id_is_user_registration_allowed'));
        $this->assertTrue($builder->has('open_id_registered_user_role'));
        $this->assertTrue($builder->has('open_id_client_url'));
        $this->assertTrue($builder->has('open_id_client_id'));
        $this->assertTrue($builder->has('open_id_client_secret'));
        $this->assertTrue($builder->has('open_id_mapping_field'));
    }
}
