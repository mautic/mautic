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
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\FormBundle\Helper\PageTokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber
 */
class PageSubscriber extends CommonSubscriber
{
    private $formRegex = '{form=(.*?)}';

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents ()
    {
        return array(
            PageEvents::PAGE_ON_DISPLAY => array('onPageDisplay', 0),
            PageEvents::PAGE_ON_BUILD   => array('onPageBuild', 0)
        );
    }

    /**
     * Add forms to available page tokens
     *
     * @param PageBuilderEvent $event
     */
    public function onPageBuild (PageBuilderEvent $event)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory, 'form');

        if ($event->tokenSectionsRequested()) {
            $event->addTokenSection('form.pagetokens', 'mautic.form.forms', $tokenHelper->getTokenContent());
        }

        if ($event->abTestWinnerCriteriaRequested()) {
            //add AB Test Winner Criteria
            $formSubmissions = array(
                'group'    => 'mautic.form.abtest.criteria',
                'label'    => 'mautic.form.abtest.criteria.submissions',
                'callback' => '\Mautic\FormBundle\Helper\AbTestHelper::determineSubmissionWinner'
            );
            $event->addAbTestWinnerCriteria('form.submissions', $formSubmissions);
        }

        if ($event->tokensRequested($this->formRegex)) {
            $event->addTokensFromHelper($tokenHelper, $this->formRegex, 'name', 'id', true);
        }
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay (PageDisplayEvent $event)
    {
        $content = $event->getContent();
        $page    = $event->getPage();
        $regex   = '/' . $this->formRegex . '/i';

        preg_match_all($regex, $content, $matches);

        if (count($matches[0])) {
            /** @var \Mautic\FormBundle\Model\FormModel $model */
            $model = $this->factory->getModel('form');
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
                    $formHtml = ($form->isPublished()) ? $model->getContent($form) :
                        '<div class="mauticform-error">' .
                        $this->translator->trans('mautic.form.form.pagetoken.notpublished') .
                        '</div>';

                    //add the hidden page input
                    $pageInput = "\n<input type=\"hidden\" name=\"mauticform[mauticpage]\" value=\"{$page->getId()}\" />\n";
                    $formHtml  = preg_replace("#</form>#", $pageInput . "</form>", $formHtml);

                    //pouplate get parameters
                    //priority populate value order by: query string (parameters) -> with lead
                    if (!$form->getInKioskMode()) {
                        $model->populateValuesWithLead($form, $formHtml);
                    }
                    $model->populateValuesWithGetParameters($form, $formHtml);

                    $content = preg_replace('#{form=' . $id . '}#', $formHtml, $content);
                } else {
                    $content = preg_replace("#{form=" . $id . "}#", "", $content);
                }
            }
        }
        $event->setContent($content);
    }
}
