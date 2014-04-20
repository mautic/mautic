<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class GlobalSearchEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class GlobalSearchEvent extends Event
{

    /**
     * @var
     */
    protected $results = array();

    /**
     * @var
     */
    protected $searchString;

    /**
     * @param $searchString
     */
    public function __construct($searchString)
    {
        $this->searchString = strtolower(trim(strip_tags($searchString)));
    }

    /**
     * Returns the string to be searched
     *
     * @return mixed
     */
    public function getSearchString()
    {
        return $this->searchString;
    }

    /**
     * Add an array of results from a search query to be listed in right side panel
     * Each result should be the ENTIRE html to be rendered
     *
     * @param       $header  String name for section header
     * @param array $results Array of HTML output that will be wrapped in <li /> elements
     */
    public function addResults($header, array $results)
    {
        $this->results[$header] = $results;
    }

    /**
     * Returns the results
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }
}
