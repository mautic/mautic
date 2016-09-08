<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SortableListTrait
{
    /**
     * @param FormBuilderInterface $builder
     * @param                      $options
     */
    public function addSortableList(FormBuilderInterface $builder, $options, $listName = 'list', $listData = null)
    {
        $listOptions = [
            'with_labels' => true,
            'attr'  => [
                'data-hide-on' => '{"formfield_properties_sync_list_1": "checked"}',
            ],
            'option_required' => false,
            'constraint_callback' => new Callback(
                function ($validateMe, ExecutionContextInterface $context) {
                    $data = $context->getRoot()->getData();
                    if (empty($data['properties']['sync_list']) && !count($data['properties']['list']['list'])) {
                        $context->buildViolation('mautic.form.lists.count')->addViolation();
                    }
                }
            )
        ];

        if (null !== $listData) {
            $listOptions['data'] = $listData;
        }

        $builder->add($listName, 'sortablelist', $listOptions);

        $builder->add(
            'sync_list',
            'yesno_button_group',
            [
                'label' => 'mautic.form.field.form.property_list_sync_choices',
                'data'  => (!isset($options['data']['sync_list'])) ? false : (boolean) $options['data']['sync_list'],
                'constraints' => [
                    new Callback(
                        function ($validateMe, ExecutionContextInterface $context) {
                            $data = $context->getRoot()->getData();
                            if (!empty($data['properties']['sync_list']) && empty($data['leadField'])) {
                                $context->buildViolation('mautic.form.lists.sync_list_requires_field')->addViolation();
                            }
                        }
                    )
                ]
            ]
        );
    }
}