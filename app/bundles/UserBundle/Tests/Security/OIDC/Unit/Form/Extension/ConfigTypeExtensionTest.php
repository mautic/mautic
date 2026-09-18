<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Form\Extension;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Mautic\UserBundle\Form\Type\ConfigType;
use Mautic\UserBundle\Security\OIDC\DTO\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Form\Extension\ConfigTypeExtension;
use Mautic\UserBundle\Security\OIDC\Repository\SubjectIdRepository;
use Mautic\UserBundle\Security\OIDC\Tests\Builder\DTO\ParametersBuilder;
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
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);

        self::assertEquals([ConfigType::class], $configTypeExtension::getExtendedTypes());
    }

    public function testOnPreSubmitSetsSecretToParamValueIfEmpty(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $event               = self::createMock(PreSubmitEvent::class);

        $event->expects(self::once())
            ->method('getData')
            ->willReturn(['open_id_client_secret' => '']);

        $event->expects(self::once())
            ->method('setData')
            ->with(['open_id_client_secret' => 'client_secret']);

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPreSubmit($event);
    }

    public function testOnPreSubmitDoesNotSetSecretToParamValueIfNotEmpty(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $event               = self::createMock(PreSubmitEvent::class);

        $event->expects(self::once())
            ->method('getData')
            ->willReturn(['open_id_client_secret' => 'notNull']);

        $event->expects(self::never())
            ->method('setData');

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPreSubmit($event);
    }

    public function testOnPostEventTruncatesTableWhenMappingFieldIsChanged(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $queryBuilder        = self::createMock(QueryBuilder::class);
        $query               = self::createMock(AbstractQuery::class);
        $event               = self::createMock(PostSubmitEvent::class);

        $event->expects(self::once())
            ->method('getData')
            ->willReturn(['open_id_mapping_field' => 'platform_sub']);

        $subjectIdRepository->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('delete')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('execute');

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPostSubmit($event);
    }

    public function testOnPostEventDoesNotTruncatesTableWhenMappingFieldIsUnchanged(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $event               = self::createMock(PostSubmitEvent::class);

        $event->expects(self::once())
            ->method('getData')
            ->willReturn(['open_id_mapping_field' => 'sub']);

        $subjectIdRepository->expects(self::never())
            ->method('createQueryBuilder');

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->onPostSubmit($event);
    }

    public function testBuildFormAddsFieldsWhenOpenIdIsEnabled(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $eventDispatcher     = self::createMock(EventDispatcherInterface::class);
        $formFactory         = self::createMock(FormFactoryInterface::class);
        $builder             = new FormBuilder('config', ConfigType::class, $eventDispatcher, $formFactory);

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->buildForm($builder, []);

        self::assertTrue($builder->has('open_id_is_enabled'));
        self::assertTrue($builder->has('open_id_is_required'));
        self::assertTrue($builder->has('open_id_is_user_registration_allowed'));
        self::assertTrue($builder->has('open_id_registered_user_role'));
        self::assertTrue($builder->has('open_id_client_url'));
        self::assertTrue($builder->has('open_id_client_id'));
        self::assertTrue($builder->has('open_id_client_secret'));
        self::assertTrue($builder->has('open_id_mapping_field'));
    }

    // we always want to add the fields so the user can enable/disable OpenID
    public function testBuildFormAddsFieldsWhenOpenIdIsDisabled(): void
    {
        $parameters          = (new ParametersBuilder())->withIsEnabled(false)->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = self::createMock(SubjectIdRepository::class);
        $eventDispatcher     = self::createMock(EventDispatcherInterface::class);
        $formFactory         = self::createMock(FormFactoryInterface::class);
        $builder             = new FormBuilder('config', ConfigType::class, $eventDispatcher, $formFactory);

        $configTypeExtension = new ConfigTypeExtension($parameters, $clientCredentials, $subjectIdRepository);
        $configTypeExtension->buildForm($builder, []);

        self::assertTrue($builder->has('open_id_is_enabled'));
        self::assertTrue($builder->has('open_id_is_required'));
        self::assertTrue($builder->has('open_id_is_user_registration_allowed'));
        self::assertTrue($builder->has('open_id_registered_user_role'));
        self::assertTrue($builder->has('open_id_client_url'));
        self::assertTrue($builder->has('open_id_client_id'));
        self::assertTrue($builder->has('open_id_client_secret'));
        self::assertTrue($builder->has('open_id_mapping_field'));
    }
}
