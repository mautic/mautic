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
    private $tokens  = array();
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Adds a token to the list of available tokens in the page builder
     *
     * @param string $key - a unique identifier; it is recommended that it be namespaced i.e. lead.field_firstname
     * @param array $action - can contain the following keys:
     *  'group'    => (required) translation string to group tokens by
     *  'label'    => (required) name to display in the list
     *  'token'    => (required) tag that is added to the editor
     *  'descr'    => (optional) short description of token
     *  'ondrop'   => (optional) Optional JS function to be called when the user drops a token into the editor;
     *                  the function must be in the Mautic namespace
     *  ''
     */
    public function addToken($key, array $token)
    {
        if (array_key_exists($key, $this->tokens)) {
            throw new InvalidArgumentException("The key, '$key' is already used by another subscriber. Please use a different key.");
        }

        $this->verifyToken(array('group', 'label', 'token'), $token);

        $this->tokens[$key] = $token;
    }

    /**
     * Get submit actions
     *
     * @param boolean $includeGroupOrder
     * @return array
     */
    public function getTokens($includeGroupOrder = true)
    {
        if ($includeGroupOrder) {
            $byGroup = array();
            foreach ($this->tokens as $k => $token) {
                $group = $this->translator->trans($token['group']);
                $byGroup[$group][] = $k;
            }
            foreach ($byGroup as &$group) {
                sort($group, SORT_NATURAL);
            }
            ksort($byGroup, SORT_NATURAL);

            return array('groupOrder' => $byGroup, 'tokens' => $this->tokens);
        } else {
            return $this->actions;
        }
    }

    /**
     * @param array $keys
     * @param array $token
     */
    private function verifyToken(array $keys, array $token)
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $token)) {
                throw new InvalidArgumentException("The key, '$k' is missing.");
            }
        }
    }
}