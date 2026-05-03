<?php

declare(strict_types=1);

namespace InfilePhp\WordPress\Http\Psr7;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class WpPsr17Factory implements RequestFactoryInterface, StreamFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new WpRequest($method, $uri);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new WpStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = @fopen($filename, $mode);
        if ($resource === false) {
            throw new \RuntimeException('Unable to open file ' . $filename);
        }
        return new WpStream($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new WpStream($resource);
    }
}
