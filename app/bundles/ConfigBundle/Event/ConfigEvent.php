<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ConfigEvent.
 */
class ConfigEvent extends CommonEvent
{
    /**
     * @var array
     */
    private $preserve = [];

    /**
     * @param array $config
     */
    private $config;

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $post
     */
    private $post;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $fieldErrors = [];

    /**
     * @param array        $config
     * @param ParameterBag $post
     */
    public function __construct(array $config, ParameterBag $post)
    {
        $this->config = $config;
        $this->post   = $post;
    }

    /**
     * Returns the config array.
     *
     * @param string $key
     *
     * @return array
     */
    public function getConfig($key = null)
    {
        if ($key) {
            return (isset($this->config[$key])) ? $this->config[$key] : [];
        }

        return $this->config;
    }

    /**
     * Sets the config array.
     *
     * @param array $config
     * @param null  $key
     */
    public function setConfig(array $config, $key = null)
    {
        if ($key) {
            $this->config[$key] = $config;
        } else {
            $this->config = $config;
        }
    }

    /**
     * Returns the POST.
     *
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set fields such as passwords that will not overwrite existing values
     * if the current is empty.
     *
     * @param array|string $fields
     */
    public function unsetIfEmpty($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        $this->preserve = array_merge($this->preserve, $fields);
    }

    /**
     * Return array of fields to unset if empty so that existing values are not
     * overwritten if empty.
     *
     * @return array
     */
    public function getPreservedFields()
    {
        return $this->preserve;
    }

    /**
     * Set error message.
     *
     * @param string $message     (untranslated)
     * @param array  $messageVars for translation
     */
    public function setError($message, $messageVars = [], $key = null, $field = null)
    {
        if (!empty($key) && !empty($field)) {
            if (!isset($this->errors[$key])) {
                $this->fieldErrors[$key] = [];
            }

            $this->fieldErrors[$key][$field] = [
                $message,
                $messageVars,
            ];

            return $this;
        }

        $this->errors[$message] = $messageVars;

        return $this;
    }

    /**
     * Get error messages.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getFieldErrors()
    {
        return $this->fieldErrors;
    }

    /**
     * @param $value
     *
     * @return mixed|string
     */
    public function escapeString($value)
    {
        // Prevent symfony for failing due to percent signs
        if (is_string($value) && strpos($value, '%') !== false) {
            $value = urldecode($value);

            if (preg_match_all('/([^%]|^)(%{1}[^%]+[^%]%{1})([^%]|$)/i', $value, $matches)) {
                // Encode any left over to prevent Symfony from crashing
                foreach ($matches[0] as $matchKey => $match) {
                    $replaceWith = $matches[1][$matchKey].'%'.$matches[2][$matchKey].'%'.$matches[3][$matchKey];
                    $value       = str_replace($match, $replaceWith, $value);
                }
            }
        }

        return $value;
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    public function getFileContent(UploadedFile $file)
    {
        $tmpFile = $file->getRealPath();
        $content = trim(file_get_contents($tmpFile));
        @unlink($tmpFile);

        return $content;
    }

    /**
     * @param $content
     *
     * @return string
     */
    public function encodeFileContents($content)
    {
        return base64_encode($content);
    }
}
