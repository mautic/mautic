<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GrapesJsBuilderModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Translator
     */
    private $translator;

    /**
     * @var MockObject|Email
     */
    private $emailEntity;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|EmailRepository
     */
    private $emailRepository;

    /**
     * @var GrapesJsBuilderModel
     */
    private $grapesJsBuilderModel;

    /**
     * @var MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var MockObject|EmailModel
     */
    private $emailModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator                         = $this->createMock(Translator::class);
        $this->entityManager                      = $this->createMock(EntityManager::class);
        $this->grapesJsBuilderRepository          = $this->createMock(GrapesJsBuilderRepository::class);
        $this->requestStack                       = $this->createMock(RequestStack::class);
        $this->emailModel                         = $this->createMock(EmailModel::class);
        $this->emailEntity                        = $this->createMock(Email::class);
        $this->emailRepository                    = $this->createMock(EmailRepository::class);

        $this->grapesJsBuilderModel = new GrapesJsBuilderModel($this->requestStack, $this->emailModel);
        $this->grapesJsBuilderModel->setTranslator($this->translator);
        $this->grapesJsBuilderModel->setEntityManager($this->entityManager);
        $this->entityManager->expects($this->any())
        ->method('getRepository')
        ->willReturn($this->grapesJsBuilderRepository);

        $this->emailModel->expects($this->any())
        ->method('getRepository')
        ->willReturn($this->emailRepository);

        $this->emailModel->expects($this->any())
        ->method('getEntity')
        ->willReturn($this->emailEntity);
    }

    public function testNoCustomMjml()
    {
        $requestMock      = $this->createMock(Request::class);
        $parameterBag     = $this->createMock(ParameterBag::class);

        $requestMock->request = $parameterBag;

        $parameterBag->method('has')
        ->with('grapesjsbuilder')
        ->willReturn(false);

        $requestMock->method('get')
        ->with('emailform')
        ->willReturn(['customHtml' => 'HTML']);

        $this->requestStack->expects($this->exactly(2))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->grapesJsBuilderModel->addOrEditEntity($this->emailEntity);
    }

    public function testWithCustomMjml()
    {
        $requestMock      = $this->createMock(Request::class);
        $parameterBag     = $this->createMock(ParameterBag::class);

        $requestMock->request = $parameterBag;

        $parameterBag->method('has')
        ->with('grapesjsbuilder')
        ->willReturn(true);

        $requestMock->method('get')
        ->will($this->returnValueMap(
            [
                ['grapesjsbuilder', null, ['customMjml' => 'customMjml']],
                ['emailform', null, ['customHtml' => 'HTML']],
            ]
        ));

        $this->requestStack->expects($this->exactly(3))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->grapesJsBuilderModel->addOrEditEntity($this->emailEntity);
    }

    public function testGetGrapesJsFromEmailId()
    {
        $this->emailRepository->setMethods(['findOneBy']);

        $this->grapesJsBuilderRepository->expects($this->once())
        ->method('findOneBy')
        ->with(['email' => $this->emailEntity])
        ->willReturn($this->emailEntity);

        $this->assertEquals($this->grapesJsBuilderModel->getGrapesJsFromEmailId(1), $this->emailEntity);
    }
}
