<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PageBuilderEvent
 */
class PageBuilderEvent extends Event
{

    /**
     * @var array
     */
    private $tokens = array();

    /**
     * @var array
     */
    private $abTestWinnerCriteria = array();

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @var Page
     */
    private $page = null;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
     * @param Page                                                   $page
     */
    public function __construct($translator, Page $page = null)
    {
        $this->translator = $translator;
        $this->page       = $page;
    }

    /**
     * @param $key
     * @param $header
     * @param $content
     * @param $priority
     *
     * @return void
     */
    public function addTokenSection($key, $header, $content, $priority = 0)
    {
        if (array_key_exists($key, $this->tokens)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another subscriber. Please use a different key.");
        }

        $header = $this->translator->trans($header);
        $this->tokens[$key] = array(
            'header'   => $header,
            'content'  => $content,
            'priority' => $priority
        );
    }

    /**
     * Get tokens
     *
     * @return array
     */
    public function getTokenSections()
    {
        $sort = array();
        foreach($this->tokens as $k => $v) {
            $sort['priority'][$k] = $v['priority'];
            $sort['header'][$k]   = $v['header'];
        }

        array_multisort($sort['priority'], SORT_DESC, $sort['header'], SORT_ASC, $this->tokens);
        return $this->tokens;
    }

    /**
     * Get list of AB Test winner criteria
     *
     * @return array
     */
    public function getAbTestWinnerCriteria()
    {
        uasort($this->abTestWinnerCriteria, function ($a, $b) {
            return strnatcasecmp(
                $a['group'], $b['group']);
        });

        $array = array('criteria' => $this->abTestWinnerCriteria);

        $choices = array();
        foreach ($this->abTestWinnerCriteria as $k => $c) {
            $choices[$c['group']][$k] = $c['label'];
        }
        $array['choices'] = $choices;

        return $array;
    }

    /**
     * Adds an A/B test winner criteria option
     *
     * @param string $key - a unique identifier; it is recommended that it be namespaced i.e. lead.points
     * @param array $criteria - can contain the following keys:
     *  'group'           => (required) translation string to group criteria by in the dropdown select list
     *  'label'           => (required) what to display in the list
     *  'formType'        => (optional) name of the form type SERVICE for the criteria
     *  'formTypeOptions' => (optional) array of options to pass to the formType service
     *  'callback'        => (required) callback function that will be passed the parent page for winner determination
     *      The callback function can receive the following arguments by name (via ReflectionMethod::invokeArgs())
     *          array $properties - values saved from the formType as defined here; keyed by page id in the case of
     *              multiple variants
     *          Mautic\CoreBundle\Factory\MauticFactory $factory
     *          Mautic\FormBundle\Entity\Page $page
     *          Mautic\FormBundle\Entity\Page $parent
     *          Doctrine\Common\Collections\ArrayCollection $children
     */
    public function addAbTestWinnerCriteria($key, array $criteria)
    {
        if (array_key_exists($key, $this->abTestWinnerCriteria)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another criteria. Please use a different key.");
        }

        //check for required keys and that given functions are callable
        $this->verifyCriteria(
            array('group', 'label', 'callback'),
            array('callback'),
            $criteria
        );

        //translate the group
        $criteria['group'] = $this->translator->trans($criteria['group']);
        $this->abTestWinnerCriteria[$key] = $criteria;
    }

    /**
     * @param array $keys
     * @param array $methods
     * @param array $criteria
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function verifyCriteria(array $keys, array $methods, array $criteria)
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $criteria)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }

        foreach ($methods as $m) {
            if (isset($criteria[$m]) && !is_callable($criteria[$m], true)) {
                throw new InvalidArgumentException($criteria[$m] . ' is not callable.  Please ensure that it exists and that it is a fully qualified namespace.');
            }
        }
    }

    /**
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }
}
