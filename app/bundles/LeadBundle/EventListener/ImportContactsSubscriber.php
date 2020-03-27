<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\ImportBuilderEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

class ImportContactsSubscriber extends CommonSubscriber
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * ImportContactsSubscriber constructor.
     *
     * @param FieldModel $fieldModel
     * @param LeadModel  $leadModel
     */
    public function __construct(FieldModel $fieldModel, LeadModel $leadModel)
    {
        $this->fieldModel = $fieldModel;
        $this->leadModel  = $leadModel;
    }

    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::IMPORT_BUILDER => ['importBuilder', 1000],
        ];
    }

    /**
     * @param ImportBuilderEvent $event
     */
    public function importBuilder(ImportBuilderEvent $event)
    {
        $event->setObjectInRequest('lead');

        if ($event->getObject() === 'contacts') {
            $fields = [
                'mautic.lead.contact' => $this->fieldModel->getFieldList(false, false),
                'mautic.lead.company' => $this->fieldModel->getFieldList(
                    false,
                    false,
                    ['isPublished' => true, 'object' => 'company']
                ),
            ];

            $event->setFields($fields);
            $event->setModel($this->leadModel);
        }
    }
}
