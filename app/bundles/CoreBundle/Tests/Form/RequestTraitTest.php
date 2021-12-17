<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Form;

use Mautic\CoreBundle\Form\RequestTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormFactoryBuilder;

class RequestTraitTest extends \PHPUnit\Framework\TestCase
{
    use RequestTrait;

    /**
     * @var Form
     */
    private $form;

    protected function setUp(): void
    {
        $dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $formConfigBuilder = new FormConfigBuilder('foo', null, $dispatcher);
        $formConfigBuilder->setFormFactory((new FormFactoryBuilder())->getFormFactory());
        $formConfigBuilder->setCompound(true);
        $formConfigBuilder->setDataMapper(
            $this->createMock(DataMapperInterface::class)
        );
        $fooConfig = $formConfigBuilder->getFormConfig();

        $this->form = new Form($fooConfig);
    }

    public function testMultiSelectPrepareParametersFromRequest()
    {
        $params = [
            'multiselect'  => '',
            'multiselect2' => 'input',
            'multiselect3' => ['first'],
            'multiselect4' => 'first|second',
            'multiselect5' => ['first', 'second'],
        ];

        $expectedValues =
            [
                'multiselect'  => [],
                'multiselect2' => ['input'],
                'multiselect3' => ['first'],
                'multiselect4' => ['first', 'second'],
                'multiselect5' => ['first', 'second'],
            ];

        foreach ($params as $alias => $value) {
            $this->form->add(
                $alias,
                ChoiceType::class,
                [
                    'choices'  => [],
                    'multiple' => true,
                    'label'    => false,
                    'attr'     => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                ]
            );
        }

        $this->prepareParametersFromRequest($this->form, $params);

        $this->assertSame($expectedValues, $params);
    }
}
