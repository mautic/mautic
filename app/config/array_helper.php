<?php

if (!function_exists('flatten')) {
    /**
     * Recursive function that provides all key path with value.
     */
    function flatten(array $array = [], array $keyStack = [], array $result = []): array
    {
        foreach ($array as $key => $value) {
            $keyStack[] = $key;

            if (is_array($value)) {
                $result = flatten($value, $keyStack, $result);
            } else {
                $result[] = [
                    'keys'  => $keyStack,
                    'value' => $value,
                ];
            }

            array_pop($keyStack);
        }

        return $result;
    }
}

if (!function_exists('deep_array_get')) {
    /**
     * Get value from array by specific path.
     *
     * @return array|mixed
     */
    function deep_array_get(array $subject, array $path)
    {
        $result = $subject;
        foreach ($path as $p) {
            $result = $result[$p];
        }

        return $result;
    }
}

if (!function_exists('deep_array_unset')) {
    /**
     * Unset value in array.
     */
    function deep_array_unset(array &$subject, array $path)
    {
        $keyToUnset = array_pop($path);
        $result     = &$subject;
        foreach ($path as $p) {
            $result = &$result[$p];
        }

        unset($result[$keyToUnset]);
    }
}
