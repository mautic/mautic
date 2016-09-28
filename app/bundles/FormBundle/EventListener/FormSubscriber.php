<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;

/**
 * Class FormSubscriber
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * FormSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel = $auditLogModel;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents ()
    {
        return array(
            FormEvents::FORM_POST_SAVE   => array('onFormPostSave', 0),
            FormEvents::FORM_POST_DELETE => array('onFormDelete', 0),
            FormEvents::FORM_ON_BUILD    => array('onFormBuilder', 0),
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormPostSave (Events\FormEvent $event)
    {
        $form = $event->getForm();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "form",
                "object"    => "form",
                "objectId"  => $form->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->ipLookupHelper->getIpAddressFromRequest()
            );
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormDelete (Events\FormEvent $event)
    {
        $form = $event->getForm();
        $log  = array(
            "bundle"    => "form",
            "object"    => "form",
            "objectId"  => $form->deletedId,
            "action"    => "delete",
            "details"   => array('name' => $form->getName()),
            "ipAddress" => $this->ipLookupHelper->getIpAddressFromRequest()
        );
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add a simple email form
     *
     * @param Events\FormBuilderEvent $event
     */
    public function onFormBuilder (Events\FormBuilderEvent $event)
    {
        // Add form submit actions
        $action = array(
            'group'              => 'mautic.email.actions',
            'label'              => 'mautic.form.action.sendemail',
            'description'        => 'mautic.form.action.sendemail.descr',
            'formType'           => 'form_submitaction_sendemail',
            'formTheme'          => 'MauticFormBundle:FormTheme\SubmitAction',
            'formTypeCleanMasks' => array(
                'message' => 'html'
            ),
            'callback'           => '\Mautic\FormBundle\Helper\FormSubmitHelper::sendEmail'
        );

        $event->addSubmitAction('form.email', $action);
    }
}
