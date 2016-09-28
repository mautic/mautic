<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Model\EmailModel;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var EmailModel
     */
    protected $emailModel;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * SearchSubscriber constructor.
     *
     * @param UserHelper $userHelper
     * @param EmailModel $emailModel
     */
    public function __construct(UserHelper $userHelper, EmailModel $emailModel)
    {
        $this->userHelper = $userHelper;
        $this->emailModel = $emailModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH      => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST => array('onBuildCommandList', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch (MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $filter      = array("string" => $str, "force" => array());
        $permissions = $this->security->isGranted(
            array('email:emails:viewown', 'email:emails:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['email:emails:viewown'] || $permissions['email:emails:viewother']) {
            if (!$permissions['email:emails:viewother']) {
                $filter['force'][] = array(
                    'column' => 'IDENTITY(e.createdBy)',
                    'expr'   => 'eq',
                    'value'  => $this->userHelper->getUser()->getId()
                );
            }

            $emails = $this->emailModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($emails) > 0) {
                $emailResults = array();

                foreach ($emails as $email) {
                    $emailResults[] = $this->templating->renderResponse(
                        'MauticEmailBundle:SubscribedEvents\Search:global.html.php',
                        array('email' => $email)
                    )->getContent();
                }
                if (count($emails) > 5) {
                    $emailResults[] = $this->templating->renderResponse(
                        'MauticEmailBundle:SubscribedEvents\Search:global.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($emails) - 5)
                        )
                    )->getContent();
                }
                $emailResults['count'] = count($emails);
                $event->addResults('mautic.email.emails', $emailResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList (MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('email:emails:viewown', 'email:emails:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.email.emails',
                $this->emailModel->getCommandList()
            );
        }
    }
}