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
        if (defined('IN_MAUTIC_CONSOLE') && defined('MAUTIC_CONSOLE_VERBOSITY') && MAUTIC_CONSOLE_VERBOSITY >= ConsoleOutput::VERBOSITY_VERY_VERBOSE) {
            $output = new ConsoleOutput();
            $output->writeln('<fg=magenta>REQUEST:</>');
            $output->writeln('<fg=white>'.$event->getMethod().' '.$event->getUrl().'</>');
            if (count($event->getHeaders())) {
                $output->writeln('<fg=cyan>'.implode(PHP_EOL, array_map(function ($k, $v) {
                    return "$k: $v";
                }, array_keys($event->getHeaders()), array_values($event->getHeaders()))).'</>');
                $output->writeln('');
            }
            if (count($event->getParameters())) {
                $output->writeln('<fg=cyan>'.implode(PHP_EOL, array_map(function ($k, $v) {
                    return "$k=$v";
                }, array_keys($event->getParameters()), array_values($event->getParameters()))).'</>');
            }
        }
    }

    /*
     * Response event
     */
    public function onResponse(PluginIntegrationRequestEvent $event)
    {
        if (defined('IN_MAUTIC_CONSOLE') && defined('MAUTIC_CONSOLE_VERBOSITY') && MAUTIC_CONSOLE_VERBOSITY >= ConsoleOutput::VERBOSITY_VERY_VERBOSE) {
            $output = new ConsoleOutput();
            $output->writeln('<fg=magenta>RESPONSE:</>');
            /** @var Response $response */
            $response = $event->getResponse();
            $isJson   = false;
            $isXml    = false;
            if (count($response->headers)) {
                $output->writeln('<fg=cyan>'.implode(PHP_EOL, array_map(function ($k, $v) {
                    return "$k: $v";
                }, array_keys($response->headers), array_values($response->headers))).'</>');
                $output->writeln('');
                $isJson = isset($response->headers['Content-Type']) && preg_match('/application\/json/', $response->headers['Content-Type']);
                $isXml  = isset($response->headers['Content-Type']) && preg_match('/text\/xml/', $response->headers['Content-Type']);
            }
            if ($isJson) {
                $output->writeln('<fg=cyan>'.str_replace('    ', '  ', json_encode(json_decode($response->body), JSON_PRETTY_PRINT)).'</>');
            } elseif ($isXml) {
                $doc                     = new DomDocument('1.0');
                $doc->preserveWhiteSpace = false;
                $doc->formatOutput       = true;
                $doc->loadXML($response->body);
                $output->writeln('<fg=cyan>'.$doc->saveXML().'</>');
            } else {
                $output->writeln('<fg=cyan>'.$response->body.'</>');
            }
        }
    }
}
