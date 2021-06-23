<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Form\Type\EmailType;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EmailTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManager;

    /**
     * @var MockObject|StageModel
     */
    private $stageModel;

    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var EmailType
     */
    private $form;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var ThemeHelperInterface
     */
    private $themeHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->entityManager        = $this->createMock(EntityManager::class);
        $this->stageModel           = $this->createMock(StageModel::class);
        $this->formBuilder          = $this->createMock(FormBuilderInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->themeHelper          = $this->createMock(ThemeHelperInterface::class);
        $this->form                 = new EmailType(
            $this->translator,
            $this->entityManager,
            $this->stageModel,
            $this->coreParametersHelper,
            $this->themeHelper
        );

        $this->formBuilder->method('create')->willReturnSelf();
    }

    public function testBuildForm()
    {
        $this->themeHelper
            ->expects($this->once())
            ->method('getCurrentTheme')
            ->with('blank', 'email')
            ->willReturn('blank');

        $options = [
            'data' => new Email(),
        ];

        $this->formBuilder->expects($this->at(47))
            ->method('add')
            ->with(
                'buttons',
                FormButtonsType::class,
                [
                    'pre_extra_buttons' => [
                        [
                            'name'  => 'builder',
                            'label' => 'mautic.core.builder',
                            'attr'  => [
                                'class'   => 'btn btn-default btn-dnd btn-nospin text-primary btn-builder',
                                'icon'    => 'fa fa-cube',
                                'onclick' => "Mautic.launchBuilder('emailform', 'email');",
                            ],
                        ],
                    ],
                ]
            );

        $this->form->buildForm($this->formBuilder, $options);
    }
}
