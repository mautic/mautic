<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DefaultController.
 */
class DefaultController extends FormController
{
    /**
     * Generates the default view.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->delegateView([
            'contentTemplate' => 'MauticCalendarBundle:Default:index.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_calendar_index',
                'mauticContent' => 'calendar',
                'route'         => $this->generateUrl('mautic_calendar_index'),
            ],
        ]);
    }

    /**
     * Generates the modal view.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction()
    {
        $source    = $this->request->query->get('source');
        $startDate = new \DateTime($this->request->query->get('startDate'));
        $entityId  = $this->request->query->get('objectId');

        /* @type \Mautic\CalendarBundle\Model\CalendarModel $model */
        $calendarModel = $this->getModel('calendar');
        $event         = $calendarModel->editCalendarEvent($source, $entityId);

        $model         = $event->getModel();
        $entity        = $event->getEntity();
        $session       = $this->get('session');
        $sourceSession = $this->get('session')->get('mautic.calendar.'.$source, 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_calendar_index', [$source => $sourceSession]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => [$source => $sourceSession],
            'contentTemplate' => $event->getContentTemplate(),
            'passthroughVars' => [
                'activeLink'    => 'mautic_calendar_index',
                'mauticContent' => $source,
            ],
        ];

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.'.$source.'.error.notfound',
                            'msgVars' => ['%id%' => $entityId],
                        ],
                    ],
                ])
            );
        } elseif (!$event->hasAccess()) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $source.'.'.$source);
        }

        //Create the form
        $action = $this->generateUrl('mautic_calendar_action', [
            'objectAction' => 'edit',
            'objectId'     => $entity->getId(),
            'source'       => $source,
            'startDate'    => $startDate->format('Y-m-d H:i:s'),
        ]);
        $form = $model->createForm($entity, $this->get('form.factory'), $action, ['formName' => $event->getFormName()]);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $contentName     = 'mautic.'.$source.'builder.'.$entity->getSessionId().'.content';
                    $existingContent = $entity->getContent();
                    $newContent      = $session->get($contentName, []);
                    $content         = array_merge($existingContent, $newContent);
                    $entity->setContent($content);

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    //clear the session
                    $session->remove($contentName);

                    $this->addFlash('mautic.core.notice.updated', [
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_'.$source.'_index',
                        '%url%'       => $this->generateUrl('mautic_'.$source.'_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);
                }
            } else {
                //clear any modified content
                $session->remove('mautic.'.$source.'builder.'.$entityId.'.content');
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return new JsonResponse([
                    'mauticContent' => 'calendarModal',
                    'closeModal'    => 1,
                ]);
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $builderComponents = $model->getBuilderComponents($entity);

        return $this->delegateView([
            'viewParameters' => [
                'form'   => $this->setFormTheme($form, $event->getContentTemplate()),
                'tokens' => $builderComponents[$source.'Tokens'],
                'entity' => $entity,
                'model'  => $model,
            ],
            'contentTemplate' => $event->getContentTemplate(),
            'passthroughVars' => [
                'activeLink'    => '#mautic_calendar_index',
                'mauticContent' => 'calendarModal',
                'route'         => $this->generateUrl('mautic_calendar_action', [
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId(),
                    'source'       => $source,
                    'startDate'    => $startDate->format('Y-m-d H:i:s'),
                ]),
            ],
        ]);
    }
}
