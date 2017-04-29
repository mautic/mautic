<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CampaignEventLeadTagsType.
 */
class CampaignEventLeadTagsType extends AbstractType
{
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'tags',
            'lead_tag',
            [
                'by_reference' => false,
                'attr'         => [
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
            ]
        );


    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaignevent_lead_tags';
    }
}
