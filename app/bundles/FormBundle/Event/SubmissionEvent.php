<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SubmissionEvent.
 */
class SubmissionEvent extends CommonEvent
{
    /**
     * Raw POST results.
     *
     * @var array
     */
    private $post = [];

    /**
     * @var array
     */
    private $server = [];

    /**
     * Cleaned post results.
     *
     * @var array
     */
    private $results = [];

    /**
     * Form fields.
     *
     * @var array
     */
    private $fields = [];

    /**
     * Results converted to tokens.
     *
     * @var array
     */
    private $tokens = [];

    /**
     * Callback for post form submit.
     *
     * @var mixed
     */
    private $callbacks = [];

    /**
     * @var mixed
     */
    private $callbackResponses = [];

    /**
     * @var array
     */
    private $contactFieldMatches = [];

    /**
     * Array to hold information set by other actions that may be useful to subsequent actions.
     *
     * @var array
     */
    private $feedback = [];

    /**
     * Configuration for the action.
     *
     * @var array
     */
    private $actionConfig = [];

    /**
     * Active action.
     *
     * @var
     */
    private $action;

    /**
     * @var Request
     */
    private $request;

    /**
     * SubmissionEvent constructor.
     *
     * @param Submission $submission
     * @param            $post
     * @param            $server
     * @param Request    $request
     */
    public function __construct(Submission $submission, $post, $server, Request $request)
    {
        $this->entity  = $submission;
        $this->post    = $post;
        $this->server  = $server;
        $this->request = $request;
    }

    /**
     * Returns the Submission entity.
     *
     * @return Submission
     */
    public function getSubmission()
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @return array
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return \Mautic\FormBundle\Entity\Form
     */
    public function getForm()
    {
        return $this->entity->getForm();
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param array $results
     *
     * @return SubmissionEvent
     */
    public function setResults($results)
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     *
     * @return SubmissionEvent
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param array $tokens
     *
     * @return SubmissionEvent
     */
    public function setTokens($tokens)
    {
        $this->tokens = $tokens;

        return $this;
    }

    /**
     * @return array
     */
    public function getContactFieldMatches()
    {
        return $this->contactFieldMatches;
    }

    /**
     * @param array $contactFieldMatches
     *
     * @return SubmissionEvent
     */
    public function setContactFieldMatches($contactFieldMatches)
    {
        $this->contactFieldMatches = $contactFieldMatches;

        return $this;
    }

    /**
     * @param $key
     * @param $feedback
     */
    public function setActionFeedback($key, $feedback)
    {
        $this->feedback[$key] = $feedback;
    }

    /**
     * Get feedback injected by another action.
     *
     * @param null $key
     *
     * @return array|bool|mixed
     */
    public function getActionFeedback($key = null)
    {
        if (null == $key) {
            return $this->feedback;
        } elseif (isset($this->feedback[$key])) {
            return $this->feedback[$key];
        }

        return false;
    }

    /**
     * @param $action
     *
     * @return bool
     */
    public function checkContext($action)
    {
        return $this->action === $action;
    }

    /**
     * @param array $config
     */
    public function setActionConfig($action, array $config)
    {
        $this->action       = $action;
        $this->actionConfig = $config;
    }

    /**
     * @return mixed
     */
    public function getActionConfig()
    {
        return $this->actionConfig;
    }

    /**
     * Set an post submit callback - include $callback['eventName' => '', 'anythingElse' ...].
     *
     * @param string $key
     * @param array  $callback
     */
    public function setPostSubmitCallback($key, array $callback)
    {
        // support for `callback` is @deprecated and should be removed in 3.0
        if (!array_key_exists('eventName', $callback) && !array_key_exists('callback', $callback)) {
            throw new \InvalidArgumentException('eventName or callback required');
        }

        $this->callbacks[$key] = $callback;
    }

    /**
     * @return mixed
     */
    public function getPostSubmitCallback($key = null)
    {
        return (null === $key) ? $this->callbacks : $this->callbacks[$key];
    }

    /**
     * @return int
     */
    public function hasPostSubmitCallbacks()
    {
        return count($this->callbacks) || count($this->callbackResponses);
    }

    /**
     * @return mixed
     */
    public function getPostSubmitCallbackResponse($key = null)
    {
        return (null === $key) ? $this->callbackResponses : $this->callbackResponses[$key];
    }

    /**
     * @param mixed $callbackResponse
     *
     * @return SubmissionEvent
     */
    public function setPostSubmitCallbackResponse($key, $callbackResponse)
    {
        $this->callbackResponses[$key] = $callbackResponse;

        return $this;
    }
}
