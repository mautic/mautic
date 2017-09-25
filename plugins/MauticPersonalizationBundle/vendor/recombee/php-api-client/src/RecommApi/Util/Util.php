<?php
/**
 * Util
 * @author Ondrej Fiedler <ondrej.fiedler@recombee.com>
 */

namespace Recombee\RecommApi\Util;

/**
 * Utils
 */
class Util
{
	public static function sliceDbName($uri) {
	    $prefix = '/{databaseId}/';
	    if (substr($uri, 0, strlen($prefix)) == $prefix) {
	        $uri = substr($uri, strlen($prefix));
	    }
	    return $uri;
	}
}
?>