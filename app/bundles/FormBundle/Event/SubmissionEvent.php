<?php

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @var Action
     */
    private $action;

    /**
     * @var string
     */
    private $context;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array|Response|null
     */
    private $postSubmitResponse;

    /**
     * @var array<mixed>
     */
    private $postSubmitPayload;

    /**
     * SubmissionEvent constructor.
     *
     * @param $post
     * @param $server
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
        if (null === $key) {
            return $this->feedback;
        } elseif (isset($this->feedback[$key])) {
            return $this->feedback[$key];
        }

        return false;
    }

    public function checkContext(string $context): bool
    {
        return $this->context === $context;
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    public function setAction(?Action $action = null)
    {
        $this->action = $action;
        if (!is_null($action)) {
            $this->setContext($action->getType());
        }
    }

    public function getAction(): ?Action
    {
        return $this->action;
    }

    public function getActionConfig(): array
    {
        return $this->action ? $this->action->getProperties() : [];
    }

    /**
     * Set an post submit callback - include $callback['eventName' => '', 'anythingElse' ...].
     *
     * @param string $key
     */
    public function setPostSubmitCallback($key, array $callback)
    {
        if (!array_key_exists('eventName', $callback)) {
            throw new \InvalidArgumentException('eventName required');
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

    public function hasPostSubmitResponse(): bool
    {
        return null !== $this->postSubmitResponse;
    }

    public function getPostSubmitResponse()
    {
        return $this->postSubmitResponse;
    }

    public function setPostSubmitResponse($response): void
    {
        $this->postSubmitResponse = $response;
    }

    /**
     * @return mixed[]
     */
    public function getPostSubmitPayload(): array
    {
        return $this->postSubmitPayload;
    }

    /**
     * @param mixed[] $postSubmitPayload
     */
    public function setPostSubmitPayload(array $postSubmitPayload): void
    {
        $this->postSubmitPayload = $postSubmitPayload;
    }
}
