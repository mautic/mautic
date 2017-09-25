<?php
/*
 This file is auto-generated, do not edit
*/

/**
 * DeleteViewPortion request
 */
namespace Recombee\RecommApi\Requests;
use Recombee\RecommApi\Exceptions\UnknownOptionalParameterException;

/**
 * The view portions feature is currently experimental.
 * Deletes an existing view portion specified by (`userId`, `itemId`, `sessionId`) from the database.
 */
class DeleteViewPortion extends Request {

    /**
     * @var string $user_id ID of the user who rated the item.
     */
    protected $user_id;
    /**
     * @var string $item_id ID of the item which was rated.
     */
    protected $item_id;
    /**
     * @var string $session_id Identifier of a session.
     */
    protected $session_id;
    /**
     * @var array Array containing values of optional parameters
     */
   protected $optional;

    /**
     * Construct the request
     * @param string $user_id ID of the user who rated the item.
     * @param string $item_id ID of the item which was rated.
     * @param array $optional Optional parameters given as an array containing pairs name of the parameter => value
     * - Allowed parameters:
     *     - *sessionId*
     *         - Type: string
     *         - Description: Identifier of a session.
     * @throws Exceptions\UnknownOptionalParameterException UnknownOptionalParameterException if an unknown optional parameter is given in $optional
     */
    public function __construct($user_id, $item_id, $optional = array()) {
        $this->user_id = $user_id;
        $this->item_id = $item_id;
        $this->session_id = isset($optional['sessionId']) ? $optional['sessionId'] : null;
        $this->optional = $optional;

        $existing_optional = array('sessionId');
        foreach ($this->optional as $key => $value) {
            if (!in_array($key, $existing_optional))
                 throw new UnknownOptionalParameterException($key);
         }
        $this->timeout = 1000;
        $this->ensure_https = false;
    }

    /**
     * Get used HTTP method
     * @return static Used HTTP method
     */
    public function getMethod() {
        return Request::HTTP_DELETE;
    }

    /**
     * Get URI to the endpoint
     * @return string URI to the endpoint
     */
    public function getPath() {
        return "/{databaseId}/viewportions/";
    }

    /**
     * Get query parameters
     * @return array Values of query parameters (name of parameter => value of the parameter)
     */
    public function getQueryParameters() {
        $params = array();
        $params['userId'] = $this->user_id;
        $params['itemId'] = $this->item_id;
        if (isset($this->optional['sessionId']))
            $params['sessionId'] = $this->optional['sessionId'];
        return $params;
    }

    /**
     * Get body parameters
     * @return array Values of body parameters (name of parameter => value of the parameter)
     */
    public function getBodyParameters() {
        $p = array();
        return $p;
    }

}
?>
