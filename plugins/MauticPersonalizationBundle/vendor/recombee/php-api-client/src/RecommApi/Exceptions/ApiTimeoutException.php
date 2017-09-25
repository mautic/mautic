<?php

/**
 * ApiTimeout
 * @author Ondrej Fiedler <ondrej.fiedler@recombee.com>
 */

namespace Recombee\RecommApi\Exceptions;

/**
 * Exception thrown when a request is not processed within the timeout
 */
class ApiTimeoutException extends ApiException{

    /**
      * @var \Recombee\RecommApi\Requests\Request $request Request that timed out
      */
    public $request;

    public function __construct($request, Exception $previous = null) {
        $this->request = $request;
        $description = "Client did not get response within #{$request->getTimeout()} ms";
        parent::__construct($description, 28, $previous);
    }
}
?>