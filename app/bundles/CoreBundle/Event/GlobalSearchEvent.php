<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Translation\Translator;
use Symfony\Contracts\EventDispatcher\Event;

class GlobalSearchEvent extends Event
{
    /**
     * @var array
     */
    protected $results = [];

    protected string $searchString;

    /**
     * @param string     $searchString
     * @param Translator $translator
     */
    public function __construct(
        $searchString,
        protected $translator
    ) {
        $this->searchString = strtolower(trim(strip_tags($searchString)));
    }

    /**
     * Returns the string to be searched.
     */
    public function getSearchString(): string
    {
        return $this->searchString;
    }

    /**
     * Add an array of results from a search query to be listed in right side panel
     * Each result should be the ENTIRE html to be rendered.
     *
     * @param string $header  String name for section header
     * @param array  $results Array of HTML output that will be wrapped in <li /> elements
     */
    public function addResults($header, array $results): void
    {
        $header                 = $this->translator->trans($header);
        $this->results[$header] = $results;
    }

    /**
     * Returns the results.
     *
     * @return array
     */
    public function getResults()
    {
        uksort($this->results, 'strnatcmp');

        return $this->results;
    }
}
