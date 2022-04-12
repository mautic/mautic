<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Class BuilderEvent.
 */
class BuilderEvent extends Event
{
    protected $slotTypes            = [];
    protected $sections             = [];
    protected $tokens               = [];
    protected $abTestWinnerCriteria = [];
    protected $translator;
    protected $entity;
    protected $requested;
    protected $tokenFilter;
    protected $tokenFilterText;
    protected $tokenFilterTarget;

    public function __construct($translator, $entity = null, $requested = 'all', $tokenFilter = '')
    {
        $this->translator        = $translator;
        $this->entity            = $entity;
        $this->requested         = $requested;
        $this->tokenFilterTarget = (0 === strpos($tokenFilter, '{@')) ? 'label' : 'token';
        $this->tokenFilterText   = str_replace(['{@', '{', '}'], '', $tokenFilter);
        $this->tokenFilter       = ('label' == $this->tokenFilterTarget) ? $this->tokenFilterText : str_replace('{@', '{', $tokenFilter);
    }

    /**
     * @param $key
     * @param $header
     * @param $icon
     * @param $content
     * @param $form
     * @param int $priority
     */
    public function addSlotType($key, $header, $icon, $content, $form, $priority = 0, array $params = [])
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

    /**
     * @param $key
     * @param $header
     * @param $icon
     * @param $content
     * @param $form
     * @param $priority
     */
    public function addSection($key, $header, $icon, $content, $form, $priority = 0)
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
     *
     * @return array
     */
    public function getAbTestWinnerCriteria()
    {
        uasort(
            $this->abTestWinnerCriteria,
            function ($a, $b) {
                return strnatcasecmp(
                    $a['group'],
                    $b['group']
                );
            }
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
    public function addAbTestWinnerCriteria($key, array $criteria)
    {
        if (array_key_exists($key, $this->abTestWinnerCriteria)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another criteria. Please use a different key.");
        }

        //check for required keys
        $this->verifyCriteria(
            ['group', 'label', 'event'],
            $criteria
        );

        //translate the group
        $criteria['group']                = $this->translator->trans($criteria['group']);
        $this->abTestWinnerCriteria[$key] = $criteria;
    }

    private function verifyCriteria(array $keys, array $criteria)
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
    public function addTokens(array $tokens, $convertToLinks = false)
    {
        if ($convertToLinks) {
            array_walk($tokens, function (&$val, $key) {
                $val = 'a:'.$val;
            });
        }

        $this->tokens = array_merge($this->tokens, $tokens);
    }

    /**
     * @param $key
     * @param $value
     */
    public function addToken($key, $value)
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
                if ('{leadfield' !== substr($key, 0, 10)) {
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
     *
     * @return bool
     */
    public function tokensRequested($tokenKeys = null)
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
     *
     * @return array
     */
    public function getTokenFilter()
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
                function ($v) use ($filter) {
                    return 0 === stripos($v, $filter);
                }
            );
        } else {
            // Do a search against the token
            $found = array_filter(
                array_keys($tokens),
                function ($k) use ($filter) {
                    return 0 === stripos($k, $filter);
                }
            );

            $tokens = array_intersect_key($tokens, array_flip($found));
        }

        return $tokens;
    }

    /**
     * Add tokens from a BuilderTokenHelper.
     *
     * @param        $tokens
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
    ) {
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
     * @param $tokens
     * @param $labelColumn
     * @param $valueColumn
     *
     * @return array|void
     */
    public function getTokensFromHelper(BuilderTokenHelper $tokenHelper, $tokens, $labelColumn = 'name', $valueColumn = 'id')
    {
        return $tokenHelper->getTokens(
            $tokens,
            ('label' == $this->tokenFilterTarget ? $this->tokenFilterText : ''),
            $labelColumn,
            $valueColumn
        );
    }

    /**
     * Check if AB Test Winner Criteria has been requested.
     *
     * @return bool
     */
    public function abTestWinnerCriteriaRequested()
    {
        return $this->getRequested('abTestWinnerCriteria');
    }

    /**
     * Check if Slot types has been requested.
     *
     * @return bool
     */
    public function slotTypesRequested()
    {
        return $this->getRequested('slotTypes');
    }

    /**
     * Check if Sections has been requested.
     *
     * @return bool
     */
    public function sectionsRequested()
    {
        return $this->getRequested('sections');
    }

    /**
     * @param $type
     *
     * @return bool
     */
    protected function getRequested($type)
    {
        if (is_array($this->requested)) {
            return in_array($type, $this->requested);
        }

        return $this->requested == $type || 'all' == $this->requested;
    }
}
