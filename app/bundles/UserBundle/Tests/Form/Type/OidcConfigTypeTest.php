<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Form\Extension;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Entity\OidcSubjectIdRepository;
use Mautic\UserBundle\Form\Type\ConfigType;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\ConfigTypeExtension;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\MockObject\MockObject;
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
    private OidcSubjectIdRepository&MockObject $subjectIdRepository;

    private ConfigType $configType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subjectIdRepository = $this->createStub(OidcSubjectIdRepository::class);

        $this->configType = new ConfigType(
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(TranslatorInterface::class),
            (new ParametersBuilder())->build(),
            new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub'),
            $this->subjectIdRepository,
        );
    }

    public function testOnPreSubmitSetsSecretToParamValueIfEmpty(): void
    {
        $form  = $this->createStub(FormInterface::class);
        $event = new PreSubmitEvent($form, ['open_id_client_secret' => '']);
        $this->configType->onPreSubmit($event);

        $this->assertSame('client_secret', $event->getData()['open_id_client_secret']);
    }

    public function testOnPreSubmitDoesNotSetSecretToParamValueIfNotEmpty(): void
    {
        $form  = $this->createStub(FormInterface::class);
        $event = new PreSubmitEvent($form, ['open_id_client_secret' => 'notNull']);
        $this->configType->onPreSubmit($event);

        $this->assertSame('notNull', $event->getData()['open_id_client_secret']);
    }

    public function testOnPostEventTruncatesTableWhenMappingFieldIsChanged(): void
    {
        $this->subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query        = $this->createMock(AbstractQuery::class);
        $form         = $this->createStub(FormInterface::class);
        $event        = new PostSubmitEvent($form, ['open_id_mapping_field' => 'platform_sub']);

        $this->subjectIdRepository->expects($this->once())
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

        $this->configType->onPostSubmit($event);
    }

    public function testOnPostEventDoesNotTruncatesTableWhenMappingFieldIsUnchanged(): void
    {
        $parameters          = (new ParametersBuilder())->build();
        $clientCredentials   = new ClientCredentials('https://example.com', 'client_id', 'client_secret', 'sub');
        $subjectIdRepository = $this->createMock(OidcSubjectIdRepository::class);
        $form                = $this->createStub(FormInterface::class);
        $event               = new PostSubmitEvent($form, ['open_id_mapping_field' => 'sub']);

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
