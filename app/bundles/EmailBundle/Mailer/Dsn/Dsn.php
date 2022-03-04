<?php

namespace Mautic\EmailBundle\Mailer\Dsn;

class Dsn
{
    private string $scheme;
    private string $host;
    private ?string $user;
    private ?string $password;
    private ?int $port;
    private array $options;

    private const ALLOWED_DSN_ARRAY = [
        'sync://' => ['sync', ''],
    ];

    public function __construct(string $scheme, string $host, string $user = null, string $password = null, int $port = null, array $options = [])
    {
        $this->scheme   = $scheme;
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
        $this->port     = $port;
        $this->options  = $options;
    }

    public static function fromString(string $dsn): self
    {
        if (array_key_exists($dsn, self::ALLOWED_DSN_ARRAY)) {
            return new self(self::ALLOWED_DSN_ARRAY[$dsn][0], self::ALLOWED_DSN_ARRAY[$dsn][1]);
        }

        if (false === $parsedDsn = parse_url($dsn)) {
            throw new \InvalidArgumentException(sprintf('The "%s" DSN is invalid.', $dsn));
        }

        if (!isset($parsedDsn['scheme'])) {
            throw new \InvalidArgumentException(sprintf('The "%s" DSN must contain a scheme.', $dsn));
        }

        if (!isset($parsedDsn['host'])) {
            throw new \InvalidArgumentException(sprintf('The "%s" DSN must contain a host (use "default" by default).', $dsn));
        }

        $options = [];
        if (isset($parsedDsn['path'])) {
            $options['path'] = trim($parsedDsn['path'], '/');
        }

        $user     = '' !== ($parsedDsn['user'] ?? '') ? urldecode($parsedDsn['user']) : null;
        $password = '' !== ($parsedDsn['pass'] ?? '') ? urldecode($parsedDsn['pass']) : null;
        $port     = $parsedDsn['port'] ?? null;
        parse_str($parsedDsn['query'] ?? '', $query);
        if (!empty($query)) {
            $options = array_merge($options, $query);
        }

        return new self($parsedDsn['scheme'], $parsedDsn['host'], $user, $password, $port, $options);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPort(int $default = null): ?int
    {
        return $this->port ?? $default;
    }

    /**
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }
}
