<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests;

use PHPUnit\Framework\Attributes\After;
use Symfony\Component\Process\Process;

/**
 * Serves a local fixture over HTTP so remote-asset tests stay offline and deterministic.
 */
trait RemoteFileServerTrait
{
    private ?Process $remoteFileServer = null;

    private ?string $remoteFileServerHost = null;

    protected function serveRemoteFile(string $fixture): string
    {
        if (null === $this->remoteFileServer) {
            $host = '127.0.0.1';
            $port = $this->findFreePort($host);

            $this->remoteFileServer = new Process([
                PHP_BINARY, '-S', $host.':'.$port, '-t', __DIR__.'/Fixtures/remote',
            ]);
            $this->remoteFileServer->start();
            $this->remoteFileServerHost = $host.':'.$port;

            $this->waitUntilServerReady($host, $port);
        }

        return 'http://'.$this->remoteFileServerHost.'/'.$fixture;
    }

    #[After]
    protected function stopRemoteFileServer(): void
    {
        $this->remoteFileServer?->stop(0);
        $this->remoteFileServer     = null;
        $this->remoteFileServerHost = null;
    }

    private function findFreePort(string $host): int
    {
        $socket = stream_socket_server('tcp://'.$host.':0', $errno, $errstr);
        if (false === $socket) {
            throw new \RuntimeException(sprintf('Cannot reserve a free port: %s (%d)', $errstr, $errno));
        }

        $name = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private function waitUntilServerReady(string $host, int $port): void
    {
        for ($i = 0; $i < 50; ++$i) {
            $connection = @fsockopen($host, $port, $errno, $errstr, 0.1);
            if (false !== $connection) {
                fclose($connection);

                return;
            }

            usleep(100_000);
        }

        throw new \RuntimeException('Local remote-file server did not start in time.');
    }
}
