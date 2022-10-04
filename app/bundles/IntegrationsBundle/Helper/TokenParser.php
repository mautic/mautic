<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\IntegrationsBundle\DTO\IntegrationObjectToken as Token;

class TokenParser
{
    public const TOKEN = '{mapped-integration-object=(.*?)}';

    public function findTokens(string $content): ArrayCollection
    {
        $tokens = new ArrayCollection();

        preg_match_all('/'.self::TOKEN.'/', $content, $matches);

        if (empty($matches[1])) {
            return $tokens;
        }

        foreach ($matches[1] as $key => $tokenDataRaw) {
            $token = new Token($matches[0][$key]);
            $parts = $this->getPartsDividedByPipe($tokenDataRaw);

            $token->setObjectName($parts[0]);
            foreach ($parts as $part) {
                $options = $this->trimArrayElements(explode('=', $part));

                if (2 !== count($options)) {
                    continue;
                }

                $keyword = $options[0];
                $value   = $options[1];

                if ('mapped-integration-object' === $keyword) {
                    $token->setObjectName($value);
                }

                if ('integration' === $keyword) {
                    $token->setIntegration($value);
                }

                if ('default' === $keyword) {
                    $token->setDefaultValue($value);
                }

                if ('link-text' == $keyword) {
                    $token->setLinkText($value);
                }

                if ('base-url' == $keyword) {
                    $token->setBaseURL($value);
                }
            }

            $tokens->set($token->getToken(), $token);
        }

        return $tokens;
    }

    /**
     * @param $integrationObjectName
     * @param $integration
     * @param $default
     * @param $linkText
     * @param $baseURL
     */
    public function buildTokenWithDefaultOptions($integrationObjectName, $integration, $default, $linkText, $baseURL): string
    {
        return sprintf(
            '{mapped-integration-object=%s | integration=%s | default=%s | link-text=%s | base-url=%s}',
            $integrationObjectName,
            $integration,
            $default,
            $linkText,
            $baseURL
        );
    }

    /**
     * @return string[]
     */
    private function getPartsDividedByPipe(string $tokenDataRaw): array
    {
        return $this->trimArrayElements(explode('|', $tokenDataRaw));
    }

    /**
     * @param string[] $array
     *
     * @return string[]
     */
    private function trimArrayElements(array $array): array
    {
        return array_map('trim', $array);
    }
}
