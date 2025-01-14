<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class DetermineWinnerEvent extends Event
{
    /**
     * @var array{
     *             parent?: \Mautic\PageBundle\Entity\Page|\Mautic\EmailBundle\Entity\Email,
     *             children?: array<mixed>,
     *             page?: \Mautic\PageBundle\Entity\Page,
     *             email?: \Mautic\EmailBundle\Entity\Email
     *             }
     */
    private $parameters;

    /**
     * @var array{
     *             winners: array,
     *             support?: mixed,
     *             supportTemplate?: string
     *             }
     */
    private $abTestResults;

    /**
     * @param array{
     *   parent?: \Mautic\PageBundle\Entity\Page|\Mautic\EmailBundle\Entity\Email,
     *   children?: array<mixed>,
     *   page?: \Mautic\PageBundle\Entity\Page,
     *   email?: \Mautic\EmailBundle\Entity\Email
     * } $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array{
     *                parent?: \Mautic\PageBundle\Entity\Page|\Mautic\EmailBundle\Entity\Email,
     *                children?: array<mixed>,
     *                page?: \Mautic\PageBundle\Entity\Page,
     *                email?: \Mautic\EmailBundle\Entity\Email
     *                }
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array{
     *                winners:array,
     *                support?:mixed,
     *                supportTemplate?:string
     *                }
     */
    public function getAbTestResults()
    {
        return $this->abTestResults;
    }

    /**
     * @param array{
     *   winners:array,
     *   support?:mixed,
     *   supportTemplate?:string
     * } $abTestResults The following parameters are available:
     * - (required) winners - Array of IDs of the winners (empty array in case of a tie)
     * - (optional) support - Data passed to the view defined by supportTemplate below in order to render visual support for the winners (such as a graph, etc)
     * - (optional) supportTemplate - View notation to render content for the A/B stats modal. For example, `HelloWorldBundle:SubscribedEvents\AbTest:graph.html.php`
     */
    public function setAbTestResults(array $abTestResults): void
    {
        $this->abTestResults = $abTestResults;
    }
}
