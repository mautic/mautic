<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PageBuilderEvent
 *
 * @package Mautic\PageBundle\Event
 */
class PageBuilderEvent extends Event
{
    private $tokens               = array();
    private $abTestWinnerCriteria = array();
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    public function addTokenSection($key, $header, $content)
    {
        if (array_key_exists($key, $this->tokens)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another subscriber. Please use a different key.");
        }

        $header = $this->translator->trans($header);
        $this->tokens[$key] = array(
            "header"  => $header,
            "content" => $content
        );
    }

    /**
     * Get tokens
     *
     * @return array
     */
    public function getTokenSections()
    {
        uasort($this->tokens, function ($a, $b) {
            return strnatcasecmp(
                $a['header'], $b['header']);
        });
        return $this->tokens;
    }

    /**
     * Get page entity
     *
     * @return null
     */
    public function getPage()
    {
        return $this->page;
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
     * Adds a submit action to the list of available actions.
     *
     * @param string $key - a unique identifier; it is recommended that it be namespaced i.e. lead.score
     * @param array $criteria - can contain the following keys:
     *  'group'    => (required) translation string to group criteria by in the dropdown select list
     *  'label'    => (required) what to display in the list
     *  'formType' => (optional) name of the form type SERVICE for the criteria
     *  'callback' => (required) callback function that will be passed the parent page for winner determination
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
     * @param array $criteria
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
}