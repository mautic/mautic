<?php

namespace Mautic\ConfigBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ParameterBag;

class ConfigEvent extends CommonEvent
{
    /**
     * @var mixed[]
     */
    private array $preserve = [];

    /**
     * @var mixed[]
     */
    private array $errors = [];

    /**
     * @var mixed[]
     */
    private array $fieldErrors = [];

    /**
     * Data got from build form before update.
     */
    private ?array $originalNormData = null;

    /**
     * Data got from build form after update.
     *
     * @var array
     */
    private $normData;

    /**
     * @param mixed[]|null $config
     */
    public function __construct(
        private ?array $config,
        private ParameterBag $post
    ) {
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
            return $this->config[$key] ?? [];
        }

        return $this->config;
    }

    /**
     * Sets the config array.
     *
     * @param string $key
     */
    public function setConfig(array $config, $key = null): void
    {
        if ($key) {
            $this->config[$key] = $config;
        } else {
            $this->config = $config;
        }
    }

    public function getPost(): ParameterBag
    {
        return $this->post;
    }

    /**
     * Set fields such as passwords that will not overwrite existing values
     * if the current is empty.
     *
     * @param array|string $fields
     */
    public function unsetIfEmpty($fields): void
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
     * @param string      $message     (untranslated)
     * @param array       $messageVars for translation
     * @param string|null $key
     * @param string|null $field
     *
     * @return ConfigEvent
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

    public function getFileContent(UploadedFile $file): string
    {
        $tmpFile = $file->getRealPath();
        $content = trim(file_get_contents($tmpFile));
        @unlink($tmpFile);

        return $content;
    }

    public function encodeFileContents($content): string
    {
        return base64_encode($content);
    }

    /**
     * @return array
     */
    public function getOriginalNormData()
    {
        return $this->originalNormData;
    }

    /**
     * @return ConfigEvent
     */
    public function setOriginalNormData(array $normData)
    {
        $this->originalNormData = $normData;

        return $this;
    }

    /**
     * @return array
     */
    public function getNormData()
    {
        return $this->normData;
    }

    /**
     * @param array $normData
     */
    public function setNormData($normData): void
    {
        $this->normData = $normData;
    }
}
