<?php

/**
 * UnknownOptionalParameterException
 * @author Ondrej Fiedler <ondrej.fiedler@recombee.com>
 */


namespace Recombee\RecommApi\Exceptions;

/**
 * Exception thrown when a non-supported optional parameter is given to a request
 */
class UnknownOptionalParameterException extends \InvalidArgumentException {

	/**
	 * @var string $parameter Given invalid name
	 */ 
    public $parameter;

    public function __construct($par, Exception $previous = null) {
        $this->parameter = $par;
        parent::__construct("Unknown parameter {$this->parameter} was given to the request", 0, $previous);
    }
}

?>