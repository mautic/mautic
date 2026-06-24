<?php

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;

class SubmissionEvent extends CommonEvent
{
    /**
     * Cleaned post results.
     */
    private array $results = [];

    /**
     * Form fields.
     */
    private array $fields = [];

    /**
     * Results converted to tokens.
     */
    private array $tokens = [];

    /**
     * Callback for post form submit.
     *
     * @var array<string, mixed[]>
     */
    private array $callbacks = [];

    /**
     * @var mixed[]
     */
    private array $callbackResponses = [];

    /**
     * @var mixed[]
     */
    private array $contactFieldMatches = [];

    /**
     * Array to hold information set by other actions that may be useful to subsequent actions.
     *
     * @var mixed[]
     */
    private array $feedback = [];

    private ?Action $action = null;

    private ?string $context = null;

    /**
     * @var array|Response|null
     */
    private $postSubmitResponse;

    /**
     * @var array<mixed>
     */
    private ?array $postSubmitPayload = null;

    /**
     * @param mixed[]                 $post   raw POST results
     * @param mixed[]|array|ServerBag $server
     */
    public function __construct(
        Submission $submission,
        private $post,
        private $server,
        private Request $request,
    ) {
        $this->entity  = $submission;
    }

    public function getSubmission(): Submission
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

    public function getRequest(): Request
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

    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return SubmissionEvent
     */
    public function setResults(array $results)
    {
        $this->results = $results;

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return SubmissionEvent
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * @return SubmissionEvent
     */
    public function setTokens(array $tokens)
    {
        $this->tokens = $tokens;

        return $this;
    }

    public function getContactFieldMatches(): array
    {
        return $this->contactFieldMatches;
    }

    /**
     * @return SubmissionEvent
     */
    public function setContactFieldMatches(array $contactFieldMatches)
    {
        $this->contactFieldMatches = $contactFieldMatches;

        return $this;
    }

    public function setActionFeedback($key, $feedback): void
    {
        $this->feedback[$key] = $feedback;
    }

    /**
     * Get feedback injected by another action.
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

    public function setAction(?Action $action = null): void
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
    public function setPostSubmitCallback($key, array $callback): void
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

    public function hasPostSubmitCallbacks(): bool
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
