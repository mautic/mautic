<?php

declare(strict_types=1);
/**
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticTrelloBundle\Form;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\NewCard;
use MauticPlugin\MauticTrelloBundle\Service\TrelloApiService;
use Monolog\Logger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewCardType extends AbstractType
{
    /**
     * @var TrelloApiService
     */
    private $apiService;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Setup NewCard Form.
     */
    public function __construct(TrelloApiService $trelloApiService, Logger $logger)
    {
        $this->apiService = $trelloApiService;
        $this->logger     = $logger;
    }

    /**
     * Define fields to display.
     *
     * @todo inform user if no list was found (no board set)
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $lists = $this->apiService->getListsOnBoard();
        if (count($lists) > 0) {
            $label = 'mautic.trello.list';
        } else {
            $label = 'plugin.trello.config_favourite_board';
        }
        $builder
            ->add('name', TextType::class, [
                'label'      => 'mautic.trello.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ])
            ->add(
                'desc',
                TextareaType::class,
                [
                    'label'      => 'mautic.trello.description',
                    'label_attr' => ['class' => 'control-label sr-only'],
                    'attr'       => ['class' => 'form-control', 'rows' => 5],
                ]
            )
            ->add(
                'idList',
                ChoiceType::class,
                [
                    'label'        => $label,
                    'choices'      => $lists,
                    'choice_value' => 'id',
                    'choice_label' => 'name',
                    'label_attr'   => ['class' => 'control-label'],
                    'attr'         => ['class' => 'form-control'],
                ]
            )
            ->add(
                'due',
                DateTimeType::class,
                [
                    'label'      => 'mautic.trello.duedate',
                    'label_attr' => ['class' => 'control-label'],
                    'widget'     => 'single_text',
                    'required'   => false,
                    'attr'       => [
                        'class'       => 'form-control',
                        'data-toggle' => 'datetime',
                        'preaddon'    => 'fa fa-calendar',
                        'help'        => 'My Help Message',
                    ],
                    'format' => 'yyyy-MM-dd HH:mm',
                    // 'data'   => $data,
                ]
            )
            ->add('urlSource', HiddenType::class)
            ->add('contactId', HiddenType::class);

        $builder->add('buttons', FormButtonsType::class, [
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NewCard::class,
        ]);
    }
}
