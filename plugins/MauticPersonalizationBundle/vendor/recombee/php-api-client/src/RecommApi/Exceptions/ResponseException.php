<?php

/**
 * ResponseException
 * @author Ondrej Fiedler <ondrej.fiedler@recombee.com>
 */

namespace Recombee\RecommApi\Exceptions;

/**
 * Exception thrown when a request do not succeed (did not return 200 or 201)
 */
class ResponseException extends ApiException{

    /**
     * @var \Recombee\RecommApi\Requests\Request $request Request which failed
     */
    public $request;
    /**
     * @var int $status_code Obtained HTTP status code
     */
    public $status_code;
    /**
     * @var string $description Obtained error description
     */
    public $description;

    public function __construct($request, $status_code, $description, Exception $previous = null) {
        $this->request = $request;
        $this->status_code = $status_code;
        $this->description = $description;
        parent::__construct($description, $status_code, $previous);
    }
}
?>