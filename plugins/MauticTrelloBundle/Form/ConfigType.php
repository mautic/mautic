<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticTrelloBundle\Form;

use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\MauticTrelloBundle\Openapi\lib\ApiException;
use MauticPlugin\MauticTrelloBundle\Service\TrelloApiService;
use Monolog\Logger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Configure Trello integration in main Mautic Configiguration.
 */
class ConfigType extends AbstractType
{
    /**
     * @var TrelloApiService
     */
    private $apiService;

    protected $fieldModel;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ConfigType constructor.
     */
    public function __construct(FieldModel $fieldModel, TrelloApiService $trelloApiService, Logger $logger)
    {
        $this->fieldModel = $fieldModel;
        $this->apiService = $trelloApiService;
        $this->logger     = $logger;
    }

    /**
     * Creates the Settings section for Trello.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->fieldModel->getFieldList(false, false);

        $builder->add(
            'favorite_board',
            ChoiceType::class,
            [
                'choices'    => $this->getBoards(),
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'trello_config';
    }

    /**
     * Get all Trello boards.
     */
    protected function getBoards(): array
    {
        return array_flip($this->apiService->getBoardsArray());
    }
}
