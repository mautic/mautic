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
        $headers = (count($event->getHeaders())) ? implode(PHP_EOL, array_map(function ($k, $v) {
            return "$k: $v";
        }, array_keys($event->getHeaders()), array_values($event->getHeaders()))) : '';

        $params = (count($event->getParameters())) ? implode(PHP_EOL, array_map(function ($k, $v) {
            return "$k=$v";
        }, array_keys($event->getParameters()), array_values($event->getParameters()))) : '';

        if (defined('IN_MAUTIC_CONSOLE') && defined('MAUTIC_CONSOLE_VERBOSITY') && MAUTIC_CONSOLE_VERBOSITY >= ConsoleOutput::VERBOSITY_VERY_VERBOSE) {
            $output = new ConsoleOutput();
            $output->writeln('<fg=magenta>REQUEST:</>');
            $output->writeln('<fg=white>'.$event->getMethod().' '.$event->getUrl().'</>');
            $output->writeln('<fg=cyan>'.$headers.'</>');
            $output->writeln('');
            $output->writeln('<fg=cyan>'.$params.'</>');
        } elseif ('dev' === MAUTIC_ENV) {
            $this->logger->alert('INTEGRATION REQUEST: '.$event->getMethod().' '.$event->getUrl());
            if ('' !== $headers) {
                $this->logger->alert("REQUEST HEADERS: \n".$headers.PHP_EOL);
            }
            if ('' !== $params) {
                $this->logger->alert("REQUEST PARAMS: \n".$params.PHP_EOL);
            }
            if (!empty($event->getSettings())) {
                $this->logger->alert("REQUEST SETTINGS: \n".json_encode($event->getSettings(), JSON_PRETTY_PRINT).PHP_EOL);
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

        $headers = implode(PHP_EOL, array_map(function ($k, $v) {
            return "$k: $v";
        }, array_keys($response->headers), array_values($response->headers)));

        if (defined('IN_MAUTIC_CONSOLE') && defined('MAUTIC_CONSOLE_VERBOSITY') && MAUTIC_CONSOLE_VERBOSITY >= ConsoleOutput::VERBOSITY_VERY_VERBOSE) {
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
        } elseif ('dev' === MAUTIC_ENV) {
            $this->logger->alert('RESPONSE CODE: '.$response->code);
            if ('' !== $headers) {
                $this->logger->alert("RESPONSE HEADERS: \n".$headers.PHP_EOL);
            }
            if ('' !== $json || '' !== $xml || '' !== $response->body) {
                $this->logger->alert('RESPONSE BODY:');
                if ($isJson) {
                    $this->logger->alert($json."\n");
                } elseif ($isXml) {
                    $this->logger->alert($xml."\n");
                } else {
                    $this->logger->alert($response->body."\n");
                }
            }
        }
    }
}
