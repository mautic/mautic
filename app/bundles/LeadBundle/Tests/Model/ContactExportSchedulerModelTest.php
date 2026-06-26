<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExportHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Model\ContactExportSchedulerModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ContactExportSchedulerModelTest extends TestCase
{
    public function testPrepareDataAllowsSameRoleContactOwners(): void
    {
        $role = new class extends Role {
            public function getId(): int
            {
                return 5;
            }
        };

        $user = new class extends User {
            public function getId(): int
            {
                return 10;
            }
        };
        $user->setRole($role);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findUserIdsByRole')
            ->with(5)
            ->willReturn([10, 20]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $model = $this->createModel($entityManager, $user);

        $data = $model->prepareData([
            'lead:leads:viewother'    => false,
            'lead:leads:viewsamerole' => true,
        ]);

        self::assertSame([
            [
                'column' => 'l.dateIdentified',
                'expr'   => 'isNotNull',
            ],
            [
                'column' => 'l.owner_id',
                'expr'   => 'in',
                'value'  => [10, 20],
            ],
        ], $data['filter']['force']);
    }

    private function createModel(EntityManager $entityManager, User $user): ContactExportSchedulerModel
    {
        $requestStack = new RequestStack();
        $request      = Request::create('/s/contacts/batchExport');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        $translator = $this->createMock(Translator::class);
        $translator->method('trans')
            ->with('mautic.lead.lead.searchcommand.isanonymous')
            ->willReturn('is:anonymous');

        $userHelper = $this->createMock(UserHelper::class);
        $userHelper->method('getUser')->willReturn($user);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->with('contact_export_batch_size', 1000)
            ->willReturn(1000);

        return new ContactExportSchedulerModel(
            $requestStack,
            $this->createMock(LeadModel::class),
            $this->createMock(ExportHelper::class),
            $this->createMock(MailHelper::class),
            $entityManager,
            $this->createMock(CorePermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $translator,
            $userHelper,
            $this->createMock(LoggerInterface::class),
            $coreParametersHelper
        );
    }
}
