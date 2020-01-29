<?php
/**
 * Recursive function that provides all key path with value
 *
 * @param array $array
 * @param array $keyStack
 * @param array $result
 * @return array
 */
function flatten(array $array = array(), array $keyStack = array(), array $result = array()):array {
    foreach ($array as $key => $value) {
        $keyStack[] = $key;

        if (is_array($value)) {
            $result = flatten($value, $keyStack, $result);
        }
        else {
            $result[] = array(
                'keys' => $keyStack,
                'value' => $value
            );
        }

        array_pop($keyStack);
    }

    return $result;
}

/**
 * Get value from array by specific path
 *
 * @param array $subject
 * @param array $path
 * @return array|mixed
 */
function deep_array_get(array $subject, array $path) {
    $result = $subject;
    foreach ($path as $p) {
        $result = $result[$p];
    }

    return $result;
}

/**
 * Unset value in array
 *
 * @param array $subject
 * @param array $path
 */
function deep_array_unset(array &$subject, array $path) {
    $keyToUnset = array_pop($path);
    $result = &$subject;
    foreach ($path as $p) {
        $result = &$result[$p];
    }

    unset($result[$keyToUnset]);
}