<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\PageTokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class FormSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class FormSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            CoreEvents::BUILD_MENU         => array('onBuildMenu', 0),
            CoreEvents::BUILD_ROUTE        => array('onBuildRoute', 0),
            CoreEvents::GLOBAL_SEARCH      => array('onGlobalSearch', 0),
            CoreEvents::BUILD_COMMAND_LIST => array('onBuildCommandList', 0),
            ApiEvents::BUILD_ROUTE         => array('onBuildApiRoute', 0),
            FormEvents::FORM_POST_SAVE     => array('onFormPostSave', 0),
            FormEvents::FORM_POST_DELETE   => array('onFormDelete', 0),
            PageEvents::PAGE_ON_DISPLAY    => array('onPageDisplay', 0),
            PageEvents::PAGE_ON_BUILD      => array('OnPageBuild', 0)
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

        $permissions = $security->isGranted(
            array('form:forms:viewown', 'form:forms:viewother'),
            'RETURN_ARRAY'
        );
        if ($permissions['form:forms:viewown'] || $permissions['form:forms:viewother']) {
            //only show own forms if the user does not have permission to view others
            if (!$permissions['form:forms:viewother']) {
                $filter['force'] = array(
                    array('column' => 'f.createdBy', 'expr' => 'eq', 'value' => $this->get('mautic.factory')->getUser())
                );
            }

            $forms = $this->factory->getModel('form.form')->getEntities(
                array(
                    'limit'  => 5,
                    'filter' => $filter
                ));

            if (count($forms) > 0) {
                $formResults = array();
                $dateForm = $this->factory->getParameter('date_format_full');
                foreach ($forms as $form) {
                    $formResults[] = $this->templating->renderResponse(
                        'MauticFormBundle:Search:form.html.php',
                        array('form' => $form)
                    )->getContent();
                }
                if (count($forms) > 5) {
                    $formResults[] = $this->templating->renderResponse(
                        'MauticFormBundle:Search:form.html.php',
                        array(
                            'showMore'     => true,
                            'searchString' => $str,
                            'remaining'    => (count($forms) - 5),
                            'dateFormat'   => $dateForm
                        )
                    )->getContent();
                }
                $formResults['count'] = count($forms);
                $event->addResults('mautic.form.form.header.index', $formResults);
            }
        }
    }

    /**
     * @param RouteEvent $event
     */
    public function onBuildApiRoute(RouteEvent $event)
    {
        /*
        $path = __DIR__ . "/../Resources/config/routing/api.php";
        $event->addRoutes($path);
        */
    }

    /**
     * @param MauticEvents\CommandListEvent $event
     */
    public function onBuildCommandList(MauticEvents\CommandListEvent $event)
    {
        if ($this->security->isGranted(array('form:forms:viewown', 'form:forms:viewother'), "MATCH_ONE")) {
            $event->addCommands(
                'mautic.form.form.header.index',
                $this->factory->getModel('form.form')->getCommandList()
            );
        }
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormPostSave(Events\FormEvent $event)
    {
        $form = $event->getForm();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "form",
                "object"    => "form",
                "objectId"  => $form->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\FormEvent $event
     */
    public function onFormDelete(Events\FormEvent $event)
    {
        $form = $event->getForm();
        $log = array(
            "bundle"     => "form",
            "object"     => "form",
            "objectId"   => $form->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $form->getName()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Add forms to available page tokens
     *
     * @param PageBuilderEvent $event
     */
    public function onPageBuild(PageBuilderEvent $event)
    {
        $tokenHelper = new PageTokenHelper($this->factory);
        $event->addTokenSection('form.pagetokens', 'mautic.form.form.header.index', $tokenHelper->getTokenContent());
    }

    /**
     * @param PageEvent $event
     */
    public function onPageDisplay(PageEvent $event)
    {
        $content = $event->getContent();
        $page    = $event->getPage();
        foreach ($content as $slot => &$html) {
            $regex = '/{form=(.*?)}/i';

            preg_match_all($regex, $html, $matches);

            if (count($matches[0])) {
                $model = $this->factory->getModel('form.form');
                foreach ($matches[1] as $k => $id) {
                    $form = $model->getEntity($id);
                    if ($form !== null &&
                        (
                            $form->isPublished() ||
                            $this->security->hasEntityAccess(
                                'form:forms:viewown', 'form:forms:viewother', $form->getCreatedBy()
                            )
                        )
                    ) {
                        $formHtml   = $form->getCachedHtml();
                        $formStatus = $form->getPublishStatus();
                        if ($formStatus !== 'published') {
                            $formHtml .= '<div class="mauticform-error">' .
                                $this->translator->trans('mautic.form.form.pagetoken.notpublished') .
                                '</div>';
                        }

                        //add the hidden page input
                        $pageInput = "\n<input type=\"hidden\" name=\"mauticform[mauticpage]\" value=\"{$page->getId()}\" />\n";
                        $formHtml  = preg_replace("#</form>#", $pageInput . "</form>", $formHtml);

                        $html      = preg_replace('#{form='.$id.'}#', $formHtml, $html);
                    } else {
                        $html = preg_replace("#{form=".$id."}#", "", $html);
                    }
                }
            }
        }
        $event->setContent($content);
    }
}