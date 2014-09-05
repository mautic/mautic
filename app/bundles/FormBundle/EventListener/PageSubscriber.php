<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Helper\PageTokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber
 *
 * @package Mautic\FormBundle\EventListener
 */
class PageSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            PageEvents::PAGE_ON_DISPLAY    => array('onPageDisplay', 0),
            PageEvents::PAGE_ON_BUILD      => array('OnPageBuild', 0)
        );
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
                            $form->isPublished(false) ||
                            $this->security->hasEntityAccess(
                                'form:forms:viewown', 'form:forms:viewother', $form->getCreatedBy()
                            )
                        )
                    ) {
                        $formHtml = ($form->isPublished()) ? $form->getCachedHtml() :
                            '<div class="mauticform-error">' .
                            $this->translator->trans('mautic.form.form.pagetoken.notpublished') .
                            '</div>';

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