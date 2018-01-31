<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ModifyLeadTagsType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $factory
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'add_tags',
            'lead_tag',
            [
                'label' => 'mautic.lead.tags.add',
                'attr'  => [
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
                'data'            => (isset($options['data']['add_tags'])) ? $options['data']['add_tags'] : null,
                'add_transformer' => true,
            ]
        );

        $builder->add(
            'remove_tags',
            'lead_tag',
            [
                'label' => 'mautic.lead.tags.remove',
                'attr'  => [
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
                'data'            => (isset($options['data']['remove_tags'])) ? $options['data']['remove_tags'] : null,
                'add_transformer' => true,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'modify_lead_tags';
    }
}
