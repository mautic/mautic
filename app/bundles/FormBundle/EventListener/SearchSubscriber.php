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
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\FormBundle\Model\FormModel;

/**
 * Class SearchSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class SearchSubscriber extends CommonSubscriber
{
    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var FormModel
     */
    protected $formModel;

    /**
     * SearchSubscriber constructor.
     *
     * @param UserHelper $userHelper
     * @param FormModel  $formModel
     */
    public function __construct(UserHelper $userHelper, FormModel $formModel)
    {
        $this->userHelper = $userHelper;
        $this->formModel  = $formModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            CoreEvents::GLOBAL_SEARCH        => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST   => array('onBuildCommandList', 0)
        );
    }

    /**
     * @param MauticEvents\GlobalSearchEvent $event
     */
    public function onGlobalSearch(MauticEvents\GlobalSearchEvent $event)
    {
        $str = $event->getSearchString();
        if (empty($str)) {
            return;
        }

        $security   = $this->security;
        $filter     = array("string" => $str, "force" => '');

        $permissions = $security->isGranted(array('form:forms:viewown', 'form:forms:viewother'), 'RETURN_ARRAY');
        if ($permissions['form:forms:viewown'] || $permissions['form:forms:viewother']) {
            //only show own forms if the user does not have permission to view others
            if (!$permissions['form:forms:viewother']) {
                $filter['force'] = array(
                    array('column' => 'f.createdBy', 'expr' => 'eq', 'value' => $this->userHelper->getUser()->getId())
                );
            }

            $forms = $this->formModel->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($forms) > 0) {
                $formResults = array();
                foreach ($forms as $form) {
                    $formResults[] = $this->templating->renderResponse(
                        'MauticFormBundle:SubscribedEvents\Search:global.html.php',
                        array('form' => $form[0])
                    )->getContent();
                }
                if (count($forms) > 5) {
                    $formResults[] = $this->templating->renderResponse(
                        'MauticFormBundle:SubscribedEvents\Search:global.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($forms) - 5)
                        )
                    )->getContent();
                }
                $formResults['count'] = count($forms);
                $event->addResults('mautic.form.forms', $formResults);
            }
        }
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('form:forms:viewown', 'form:forms:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.form.forms',
                $this->formModel->getCommandList()
            );
        }
    }

}