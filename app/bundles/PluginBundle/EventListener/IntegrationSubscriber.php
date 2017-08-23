<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\EventListener;

use DOMDocument;
use Joomla\Http\Response;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class IntegrationSubscriber.
 *
 * This class can provide useful debugging information for API requests and responses.
 * The information is displayed when a command is executed from the console and the -vv flag is passed to it.
 */
class IntegrationSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE => ['onResponse', 0],
            PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST  => ['onRequest', 0],
        ];
    }

    /*
     * Request event
     */
    public function onRequest(PluginIntegrationRequestEvent $event)
    {
        $name     = strtoupper($event->getIntegrationName());
        $headers  = var_export($event->getHeaders(), true);
        $params   = var_export($event->getParameters(), true);
        $settings = var_export($event->getSettings(), true);

        if (defined('IN_MAUTIC_CONSOLE') && defined('MAUTIC_CONSOLE_VERBOSITY')
            && MAUTIC_CONSOLE_VERBOSITY >= ConsoleOutput::VERBOSITY_VERY_VERBOSE) {
            $output = new ConsoleOutput();
            $output->writeln('<fg=magenta>REQUEST:</>');
            $output->writeln('<fg=white>'.$event->getMethod().' '.$event->getUrl().'</>');
            $output->writeln('<fg=cyan>'.$headers.'</>');
            $output->writeln('');
            $output->writeln('<fg=cyan>'.$params.'</>');
            $output->writeln('');
            $output->writeln('<fg=cyan>'.$settings.'</>');
        } else {
            $this->logger->debug("$name REQUEST URL: ".$event->getMethod().' '.$event->getUrl());
            if ('' !== $headers) {
                $this->logger->debug("$name REQUEST HEADERS: \n".$headers.PHP_EOL);
            }
            if ('' !== $params) {
                $this->logger->debug("$name REQUEST PARAMS: \n".$params.PHP_EOL);
            }
            if ('' !== $settings) {
                $this->logger->debug("$name REQUEST SETTINGS: \n".$settings.PHP_EOL);
            }
        }
    }

    /*
     * Response event
     */
    public function onResponse(PluginIntegrationRequestEvent $event)
    {
        /** @var Response $response */
        $response = $event->getResponse();
        $headers  = var_export($response->headers, true);
        $name     = strtoupper($event->getIntegrationName());
        $isJson   = isset($response->headers['Content-Type']) && preg_match('/application\/json/', $response->headers['Content-Type']);
        $json     = $isJson ? str_replace('    ', '  ', json_encode(json_decode($response->body), JSON_PRETTY_PRINT)) : '';
        $xml      = '';
        $isXml    = isset($response->headers['Content-Type']) && preg_match('/text\/xml/', $response->headers['Content-Type']);
        if ($isXml) {
            $doc                     = new DomDocument('1.0');
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput       = true;
            $doc->loadXML($response->body);
            $xml = $doc->saveXML();
        }

        if (defined('IN_MAUTIC_CONSOLE') && defined('MAUTIC_CONSOLE_VERBOSITY')
            && MAUTIC_CONSOLE_VERBOSITY >= ConsoleOutput::VERBOSITY_VERY_VERBOSE) {
            $output = new ConsoleOutput();
            $output->writeln(sprintf('<fg=magenta>RESPONSE: %d</>', $response->code));
            $output->writeln('<fg=cyan>'.$headers.'</>');
            $output->writeln('');

            if ($isJson) {
                $output->writeln('<fg=cyan>'.$json.'</>');
            } elseif ($isXml) {
                $output->writeln('<fg=cyan>'.$xml.'</>');
            } else {
                $output->writeln('<fg=cyan>'.$response->body.'</>');
            }
        } else {
            $this->logger->debug("$name RESPONSE CODE: {$response->code}");
            if ('' !== $headers) {
                $this->logger->debug("$name RESPONSE HEADERS: \n".$headers.PHP_EOL);
            }
            if ('' !== $json || '' !== $xml || '' !== $response->body) {
                $body = "$name RESPONSE BODY: ";
                if ($isJson) {
                    $body .= $json;
                } elseif ($isXml) {
                    $body .= $xml;
                } else {
                    $body = $response->body;
                }

                $this->logger->debug($body);
            }
        }
    }
}
