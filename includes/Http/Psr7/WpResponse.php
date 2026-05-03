<?php

declare(strict_types=1);

namespace InfilePhp\WordPress\Http\Psr7;

use Psr\Http\Message\ResponseInterface;

class WpResponse extends WpMessage implements ResponseInterface
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $reasonPhrase;

    private static $phrases = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    public function __construct(int $status = 200, array $headers = [], $body = null, string $version = '1.1', ?string $reason = null)
    {
        $this->statusCode = $status;
        $this->setHeaders($headers);
        
        if ($body !== '' && $body !== null) {
            $this->stream = new WpStream($body);
        }
        
        $this->protocol = $version;
        if ($reason === null && isset(self::$phrases[$status])) {
            $this->reasonPhrase = self::$phrases[$status];
        } else {
            $this->reasonPhrase = (string) $reason;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $new = clone $this;
        $new->statusCode = (int) $code;
        if ($reasonPhrase === '' && isset(self::$phrases[$new->statusCode])) {
            $reasonPhrase = self::$phrases[$new->statusCode];
        }
        $new->reasonPhrase = (string) $reasonPhrase;
        return $new;
    }
}
