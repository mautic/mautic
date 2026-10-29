<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\EmailBundle\Entity\Email;
use Mautic\PageBundle\Entity\Page;
use Symfony\Contracts\EventDispatcher\Event;

final class DetermineWinnerEvent extends Event
{
    /**
     * @var array{
     *             winners: array<int, int|string>,
     *             support?: mixed,
     *             basedOn?: string,
     *             supportTemplate?: string
     *             }
     */
    private ?array $abTestResults = null;

    /**
     * @param array{
     *   parent?: VariantEntityInterface|mixed,
     *   children?: array<mixed>,
     *   page?: Page,
     *   email?: Email|VariantEntityInterface
     * } $parameters
     */
    public function __construct(
        private readonly array $parameters,
    ) {
    }

    /**
     * @return array{
     *                parent?: VariantEntityInterface|mixed,
     *                children?: array<mixed>,
     *                page?: Page,
     *                email?: Email|VariantEntityInterface
     *                }
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array{
     *                winners: array<int, int|string>,
     *                support?: mixed,
     *                basedOn?: string,
     *                supportTemplate?: string
     *                }
     */
    public function getAbTestResults(): ?array
    {
        return $this->abTestResults;
    }

    /**
     * @param array{
     *   winners: array<int, int|string>,
     *   support?: mixed,
     *   basedOn?: string,
     *   supportTemplate?: string
     * } $abTestResults The following parameters are available:
     * - (required) winners - Array of IDs of the winners (empty array in case of a tie)
     * - (optional) support - Data passed to the view defined by supportTemplate below in order to render visual support for the winners (such as a graph, etc)
     * - (optional) supportTemplate - View notation to render content for the A/B stats modal. For example, `HelloWorldBundle:SubscribedEvents\AbTest:graph.html.twig`
     */
    public function setAbTestResults(array $abTestResults): void
    {
        $this->abTestResults = $abTestResults;
    }
}
