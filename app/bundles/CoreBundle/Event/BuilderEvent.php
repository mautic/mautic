<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Contracts\EventDispatcher\Event;

class BuilderEvent extends Event
{
    protected $slotTypes            = [];

    protected $sections             = [];

    protected $tokens               = [];

    protected $abTestWinnerCriteria = [];

    /**
     * @var string|string[]
     */
    protected string|array $tokenFilterText;

    protected string $tokenFilterTarget;

    public function __construct(
        protected $translator,
        protected $entity = null,
        protected $requested = 'all',
        protected string $tokenFilter = ''
    ) {
        $this->tokenFilterTarget = (str_starts_with($tokenFilter, '{@')) ? 'label' : 'token';
        $this->tokenFilterText   = str_replace(['{@', '{', '}'], '', $tokenFilter);
        $this->tokenFilter       = ('label' == $this->tokenFilterTarget) ? $this->tokenFilterText : str_replace('{@', '{', $tokenFilter);
    }

    /**
     * @param int $priority
     */
    public function addSlotType($key, $header, $icon, $content, $form, $priority = 0, array $params = []): void
    {
        $this->slotTypes[$key] = [
            'header'   => $this->translator->trans($header),
            'icon'     => $icon,
            'content'  => $content,
            'params'   => $params,
            'form'     => $form,
            'priority' => $priority,
        ];
    }

    /**
     * Get slot types.
     *
     * @return array
     */
    public function getSlotTypes()
    {
        $sort = ['priority' => [], 'header' => []];
        foreach ($this->slotTypes as $k => $v) {
            $sort['priority'][$k] = $v['priority'];
            $sort['header'][$k]   = $v['header'];
        }

        array_multisort($sort['priority'], SORT_DESC, $sort['header'], SORT_ASC, $this->slotTypes);

        foreach ($this->slotTypes as $i => $slot) {
            $slot['header'] = str_replace(' ', '<br />', $slot['header']);

            $this->slotTypes[$i] = $slot;
        }

        return $this->slotTypes;
    }

    public function addSection($key, $header, $icon, $content, $form, $priority = 0): void
    {
        $this->sections[$key] = [
            'header'   => $this->translator->trans($header),
            'icon'     => $icon,
            'content'  => $content,
            'form'     => $form,
            'priority' => $priority,
        ];
    }

    /**
     * Get slot types.
     *
     * @return array
     */
    public function getSections()
    {
        $sort = ['priority' => [], 'header' => []];
        foreach ($this->sections as $k => $v) {
            $sort['priority'][$k] = $v['priority'];
            $sort['header'][$k]   = $v['header'];
        }

        array_multisort($sort['priority'], SORT_DESC, $sort['header'], SORT_ASC, $this->sections);

        return $this->sections;
    }

    /**
     * Get list of AB Test winner criteria.
     */
    public function getAbTestWinnerCriteria(): array
    {
        uasort(
            $this->abTestWinnerCriteria,
            fn ($a, $b): int => strnatcasecmp(
                $a['group'],
                $b['group']
            )
        );
        $array = ['criteria' => $this->abTestWinnerCriteria];

        $choices = [];
        foreach ($this->abTestWinnerCriteria as $k => $c) {
            $choices[$c['group']][$c['label']] = $k;
        }
        $array['choices'] = $choices;

        return $array;
    }

    /**
     * Adds an A/B test winner criteria option.
     *
     * @param string $key - a unique identifier; it is recommended that it be namespaced i.e. lead.points
     * @param array{
     *   group: string,
     *   label: string,
     *   event: string,
     *   formType?: string,
     *   formTypeOptions?: string
     * } $criteria Can contain the following keys:
     *  - group - (required) translation string to group criteria by in the dropdown select list
     *  - label - (required) what to display in the list
     *  - event - (required) event class constant that will receieve the DetermineWinnerEvent for further handling. E.g. `HelloWorldEvents::ON_DETERMINE_PLANET_VISIT_WINNER`
     *  - formType - (optional) name of the form type SERVICE for the criteria
     *  - formTypeOptions - (optional) array of options to pass to the formType service
     */
    public function addAbTestWinnerCriteria($key, array $criteria): void
    {
        if (array_key_exists($key, $this->abTestWinnerCriteria)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another criteria. Please use a different key.");
        }

        // check for required keys
        $this->verifyCriteria(
            ['group', 'label', 'event'],
            $criteria
        );

        // translate the group
        $criteria['group']                = $this->translator->trans($criteria['group']);
        $this->abTestWinnerCriteria[$key] = $criteria;
    }

    private function verifyCriteria(array $keys, array $criteria): void
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $criteria)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }
    }

    /**
     * @param bool $convertToLinks
     */
    public function addTokens(array $tokens, $convertToLinks = false): void
    {
        if ($convertToLinks) {
            array_walk($tokens, function (&$val, $key): void {
                $val = 'a:'.$val;
            });
        }

        $this->tokens = array_merge($this->tokens, $tokens);
    }

    public function addToken($key, $value): void
    {
        $this->tokens[$key] = $value;
    }

    /**
     * Get token array.
     *
     * @return array
     */
    public function getTokens($withBC = true)
    {
        if (false === $withBC) {
            $tokens = [];
            foreach ($this->tokens as $key => $value) {
                if (!str_starts_with($key, '{leadfield')) {
                    $tokens[$key] = $value;
                }
            }

            return $tokens;
        }

        return $this->tokens;
    }

    /**
     * Check if tokens have been requested.
     * Pass in string or array of tokens to filter against if filterType == token.
     *
     * @param string|array|null $tokenKeys
     */
    public function tokensRequested($tokenKeys = null): bool
    {
        if ($requested = $this->getRequested('tokens')) {
            if (!empty($this->tokenFilter) && 'token' == $this->tokenFilterTarget) {
                if (!is_array($tokenKeys)) {
                    $tokenKeys = [$tokenKeys];
                }

                $found = false;
                foreach ($tokenKeys as $token) {
                    if (0 === stripos($token, $this->tokenFilter)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $requested = false;
                }
            }
        }

        return $requested;
    }

    /**
     * Get text of the search filter.
     */
    public function getTokenFilter(): array
    {
        return [
            'target' => $this->tokenFilterTarget,
            'filter' => $this->tokenFilterText,
        ];
    }

    /**
     * Simple token filtering.
     *
     * @param array $tokens array('token' => 'label')
     *
     * @return array
     */
    public function filterTokens($tokens)
    {
        $filter = $this->tokenFilter;

        if (empty($filter)) {
            return $tokens;
        }

        if ('label' == $this->tokenFilterTarget) {
            // Do a search against the label
            $tokens = array_filter(
                $tokens,
                fn ($v): bool => 0 === stripos($v, $filter)
            );
        } else {
            // Do a search against the token
            $found = array_filter(
                array_keys($tokens),
                fn ($k): bool => 0 === stripos($k, $filter)
            );

            $tokens = array_intersect_key($tokens, array_flip($found));
        }

        return $tokens;
    }

    /**
     * Add tokens from a BuilderTokenHelper.
     *
     * @param string $labelColumn
     * @param string $valueColumn
     * @param bool   $convertToLinks If true, the tokens will be converted to links
     */
    public function addTokensFromHelper(
        BuilderTokenHelper $tokenHelper,
        $tokens,
        $labelColumn = 'name',
        $valueColumn = 'id',
        $convertToLinks = false
    ): void {
        $tokens = $this->getTokensFromHelper($tokenHelper, $tokens, $labelColumn, $valueColumn);
        if (null == $tokens) {
            $tokens = [];
        }

        $this->addTokens(
            $tokens,
            $convertToLinks
        );
    }

    /**
     * Get tokens from a BuilderTokenHelper.
     *
     * @return array|void
     */
    public function getTokensFromHelper(BuilderTokenHelper $tokenHelper, $tokens, $labelColumn = 'name', $valueColumn = 'id')
    {
        return $tokenHelper->getTokens(
            $tokens,
            'label' == $this->tokenFilterTarget ? $this->tokenFilterText : '',
            $labelColumn,
            $valueColumn
        );
    }

    /**
     * Check if AB Test Winner Criteria has been requested.
     */
    public function abTestWinnerCriteriaRequested(): bool
    {
        return $this->getRequested('abTestWinnerCriteria');
    }

    /**
     * Check if Slot types has been requested.
     */
    public function slotTypesRequested(): bool
    {
        return $this->getRequested('slotTypes');
    }

    /**
     * Check if Sections has been requested.
     */
    public function sectionsRequested(): bool
    {
        return $this->getRequested('sections');
    }

    protected function getRequested($type): bool
    {
        if (is_array($this->requested)) {
            return in_array($type, $this->requested);
        }

        return $this->requested == $type || 'all' == $this->requested;
    }
}
