<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Controller\ResultController;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Model\FormModel;
use Mautic\FormBundle\Model\SubmissionModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResultControllerTest extends TestCase
{
    public function testMarkSpamActionAddsDomainToConfiguration(): void
    {
        $formId        = 123;
        $submissionId  = 456;
        $submission    = $this->createSubmission('user@example.com');
        $request       = Request::create('', Request::METHOD_POST, ['formId' => $formId, 'objectId' => $submissionId]);
        $session       = new Session(new MockArraySessionStorage());
        $session->set('mautic.formresult.'.$formId.'.page', 3);
        $request->setSession($session);

        $result        = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([]);
        $connection    = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($result);
        $connection->expects($this->never())->method('executeStatement');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getConnection')->willReturn($connection);

        $submissionModel = $this->createMock(SubmissionModel::class);
        $submissionModel->expects($this->once())->method('getEntity')->with($submissionId)->willReturn($submission);

        $formModel = $this->createMock(FormModel::class);
        $formModel->expects($this->once())
            ->method('findEmailFieldsWithMissingDonotSubmitValidation')
            ->willReturn([]);
        $formModel->expects($this->once())
            ->method('enableDonotSubmitValidationOnEmailFields')
            ->with([]);

        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')->willReturnMap([
            ['form.submission', $submissionModel],
            ['form.form', $formModel],
        ]);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_submit_emails', [])
            ->willReturn([]);

        $configurator = $this->createMock(Configurator::class);
        $configurator->expects($this->once())
            ->method('mergeParameters')
            ->with(['do_not_submit_emails' => ['*@example.com']]);
        $configurator->expects($this->once())->method('write');

        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createController($managerRegistry, $modelFactory, $coreParametersHelper, $requestStack, $security);

        $response = $controller->markSpamAction($request, $configurator, $submissionModel, $formModel);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('mautic_form_results?objectId=123&page=3', $controller->postActionRedirectArgs['returnUrl']);
        $this->assertSame(
            [
                [
                    'type'    => 'notice',
                    'msg'     => 'mautic.form.result.markspam.success',
                    'msgVars' => ['%domain%' => 'example.com'],
                ],
            ],
            $controller->postActionRedirectArgs['flashes']
        );
    }

    public function testMarkSpamActionAddsErrorFlashWhenDomainMissing(): void
    {
        $formId       = 12;
        $submissionId = 34;
        $submission   = $this->createSubmission(null);
        $submission->setResults(['email' => 'invalid']);

        $request = Request::create('', Request::METHOD_POST, ['formId' => $formId, 'objectId' => $submissionId]);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $submissionModel = $this->createMock(SubmissionModel::class);
        $submissionModel->expects($this->once())->method('getEntity')->with($submissionId)->willReturn($submission);
        $formModel = $this->createMock(FormModel::class);
        $formModel->expects($this->never())->method('findEmailFieldsWithMissingDonotSubmitValidation');
        $formModel->expects($this->never())->method('enableDonotSubmitValidationOnEmailFields');

        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')->willReturnMap([
            ['form.submission', $submissionModel],
            ['form.form', $formModel],
        ]);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->expects($this->never())->method('get');

        $configurator = $this->createMock(Configurator::class);
        $configurator->expects($this->never())->method('mergeParameters');

        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createController($managerRegistry, $modelFactory, $coreParametersHelper, $requestStack, $security);

        $response = $controller->markSpamAction($request, $configurator, $submissionModel, $formModel);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(
            [
                [
                    'type' => 'error',
                    'msg'  => 'mautic.form.result.markspam.error',
                ],
            ],
            $controller->postActionRedirectArgs['flashes']
        );
    }

    public function testMarkSpamActionSkipsConfiguratorWriteWhenDomainAlreadyExists(): void
    {
        $formId        = 50;
        $submissionId  = 60;
        $submission    = $this->createSubmission('existing@example.com');
        $request       = Request::create('', Request::METHOD_POST, ['formId' => $formId, 'objectId' => $submissionId]);
        $session       = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $result     = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([]);
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getConnection')->willReturn($connection);

        $submissionModel = $this->createMock(SubmissionModel::class);
        $submissionModel->expects($this->once())->method('getEntity')->with($submissionId)->willReturn($submission);
        $formModel = $this->createMock(FormModel::class);
        $formModel->expects($this->never())->method('findEmailFieldsWithMissingDonotSubmitValidation');
        $formModel->expects($this->never())->method('enableDonotSubmitValidationOnEmailFields');

        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')->willReturnMap([
            ['form.submission', $submissionModel],
            ['form.form', $formModel],
        ]);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_submit_emails', [])
            ->willReturn(['*@example.com']);

        $configurator = $this->createMock(Configurator::class);
        $configurator->expects($this->never())->method('mergeParameters');
        $configurator->expects($this->never())->method('write');

        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createController($managerRegistry, $modelFactory, $coreParametersHelper, $requestStack, $security);

        $controller->markSpamAction($request, $configurator, $submissionModel, $formModel);

        $this->assertSame(
            'mautic.form.result.markspam.success',
            $controller->postActionRedirectArgs['flashes'][0]['msg']
        );
    }

    public function testBatchMarkSpamActionAddsDomains(): void
    {
        $formId = 77;
        $ids    = [1, 2, 3];

        $request = Request::create('', Request::METHOD_POST, ['formId' => $formId]);
        $request->query->set('ids', json_encode($ids));
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $result     = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([]);
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getConnection')->willReturn($connection);

        $submissionModel = $this->createMock(SubmissionModel::class);
        $submissionModel->method('getEntity')->willReturnCallback(function (int $id): ?Submission {
            return match ($id) {
                1       => $this->createSubmission('first@example.com'),
                2       => $this->createSubmission('second@test.com'),
                default => null,
            };
        });
        $formModel = $this->createMock(FormModel::class);
        $formModel->expects($this->once())
            ->method('findEmailFieldsWithMissingDonotSubmitValidation')
            ->willReturn([]);
        $formModel->expects($this->once())
            ->method('enableDonotSubmitValidationOnEmailFields')
            ->with([]);

        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')->willReturnMap([
            ['form.submission', $submissionModel],
            ['form.form', $formModel],
        ]);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('do_not_submit_emails', [])
            ->willReturn(['*@existing.com']);

        $configurator = $this->createMock(Configurator::class);
        $configurator->expects($this->once())
            ->method('mergeParameters')
            ->with(['do_not_submit_emails' => ['*@existing.com', '*@example.com', '*@test.com']]);
        $configurator->expects($this->once())->method('write');

        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createController($managerRegistry, $modelFactory, $coreParametersHelper, $requestStack, $security);

        $controller->batchMarkSpamAction($request, $configurator, $submissionModel, $formModel);

        $this->assertSame(
            'mautic.form.result.markspam.batch.success',
            $controller->postActionRedirectArgs['flashes'][0]['msg']
        );
    }

    public function testBatchMarkSpamActionAddsNoticeWhenNoDomainsFound(): void
    {
        $formId  = 88;
        $request = Request::create('', Request::METHOD_POST, ['formId' => $formId]);
        $request->query->set('ids', json_encode([10]));
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $submissionModel = $this->createMock(SubmissionModel::class);
        $submissionModel->method('getEntity')->willReturn(null);
        $formModel = $this->createMock(FormModel::class);
        $formModel->expects($this->never())->method('findEmailFieldsWithMissingDonotSubmitValidation');
        $formModel->expects($this->never())->method('enableDonotSubmitValidationOnEmailFields');

        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')->willReturnMap([
            ['form.submission', $submissionModel],
            ['form.form', $formModel],
        ]);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->expects($this->never())->method('get');

        $configurator = $this->createMock(Configurator::class);
        $configurator->expects($this->never())->method('mergeParameters');

        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')->willReturn(true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = $this->createController($managerRegistry, $modelFactory, $coreParametersHelper, $requestStack, $security);

        $controller->batchMarkSpamAction($request, $configurator, $submissionModel, $formModel);

        $this->assertSame(
            'mautic.form.result.markspam.batch.none',
            $controller->postActionRedirectArgs['flashes'][0]['msg']
        );
    }

    public function testGetEmailDomainFromSubmissionExtractsDomain(): void
    {
        $submission = $this->createSubmission('Person@Example.org');

        $this->assertSame('example.org', $submission->getEmailDomain());
    }

    private function createController(ManagerRegistry $managerRegistry, ModelFactory $modelFactory, CoreParametersHelper $coreParametersHelper, RequestStack $requestStack, CorePermissions $security): TestResultController
    {
        return new TestResultController(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(FormFieldHelper::class),
            $managerRegistry,
            $modelFactory,
            $this->createMock(UserHelper::class),
            $coreParametersHelper,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(FlashBag::class),
            $requestStack,
            $security
        );
    }

    private function createSubmission(?string $email): Submission
    {
        $form = new Form();
        $form->setCreatedBy(1);

        $emailField = (new Field())
            ->setAlias('email')
            ->setType('email')
            ->setForm($form);

        $form->addField(1, $emailField);

        $submission = new Submission();
        $submission->setForm($form);

        if (null !== $email) {
            $submission->setResults(['email' => $email]);
        }

        return $submission;
    }
}

/**
 * @internal
 *
 * Simplifies assertions around redirects by capturing arguments
 */
final class TestResultController extends ResultController
{
    /** @var array<string, mixed> */
    public array $postActionRedirectArgs = [];

    /**
     * @param array<string, mixed> $args
     */
    public function postActionRedirect(array $args = []): Response
    {
        $this->postActionRedirectArgs = $args;

        return new Response('redirect');
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        if (!$parameters) {
            return $route;
        }

        return $route.'?'.http_build_query($parameters);
    }
}
