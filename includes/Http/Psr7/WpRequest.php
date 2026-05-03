<?php

declare(strict_types=1);

namespace InfilePhp\WordPress\Http\Psr7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class WpRequest extends WpMessage implements RequestInterface
{
    /** @var string */
    private $method;

    /** @var string|UriInterface */
    private $uri;

    /** @var string|null */
    private $requestTarget;

    public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1')
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;

        if ($body !== '' && $body !== null) {
            $this->stream = new WpStream($body);
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        if ($this->uri instanceof UriInterface) {
            $target = $this->uri->getPath();
            if ($target === '') {
                $target = '/';
            }
            if ($this->uri->getQuery() !== '') {
                $target .= '?' . $this->uri->getQuery();
            }
            return $target;
        }

        if (is_string($this->uri)) {
            $parsed = parse_url($this->uri);
            $target = $parsed['path'] ?? '/';
            if (isset($parsed['query'])) {
                $target .= '?' . $parsed['query'];
            }
            return $target;
        }

        return '/';
    }

    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            if ($uri->getHost() !== '') {
                $new->headerNames['host'] = 'Host';
                $new->headers['Host'] = [$uri->getHost()];
            }
        }

        return $new;
    }
}
