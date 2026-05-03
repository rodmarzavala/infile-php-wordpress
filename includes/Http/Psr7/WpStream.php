<?php

declare(strict_types=1);

namespace InfilePhp\WordPress\Http\Psr7;

use Psr\Http\Message\StreamInterface;

class WpStream implements StreamInterface
{
    /** @var resource */
    private $stream;

    /**
     * @param string|resource $body
     */
    public function __construct($body = '')
    {
        if (is_string($body)) {
            $this->stream = fopen('php://temp', 'r+');
            fwrite($this->stream, $body);
            rewind($this->stream);
        } elseif (is_resource($body)) {
            $this->stream = $body;
        } else {
            throw new \InvalidArgumentException('First argument to Stream::create() must be a string, resource or StreamInterface.');
        }
    }

    public function __toString(): string
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function close(): void
    {
        if (isset($this->stream)) {
            fclose($this->stream);
            $this->detach();
        }
    }

    public function detach()
    {
        $result = $this->stream;
        unset($this->stream);
        return $result;
    }

    public function getSize(): ?int
    {
        if (!isset($this->stream)) {
            return null;
        }
        $stats = fstat($this->stream);
        return $stats['size'] ?? null;
    }

    public function tell(): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }
        $result = ftell($this->stream);
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }
        return $result;
    }

    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        if (!isset($this->stream)) {
            return false;
        }
        $meta = stream_get_meta_data($this->stream);
        return $meta['seekable'];
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }
        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position ' . $offset);
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        if (!isset($this->stream)) {
            return false;
        }
        $meta = stream_get_meta_data($this->stream);
        $mode = $meta['mode'];
        return strpos($mode, 'w') !== false || strpos($mode, 'a') !== false || strpos($mode, 'x') !== false || strpos($mode, 'c') !== false || strpos($mode, '+') !== false;
    }

    public function write($string): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }
        if (!$this->isWritable()) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }
        return $result;
    }

    public function isReadable(): bool
    {
        if (!isset($this->stream)) {
            return false;
        }
        $meta = stream_get_meta_data($this->stream);
        $mode = $meta['mode'];
        return strpos($mode, 'r') !== false || strpos($mode, '+') !== false;
    }

    public function read($length): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }
        if (!$this->isReadable()) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new \RuntimeException('Unable to read from stream');
        }
        return $result;
    }

    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }
        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }
        return $contents;
    }

    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }
        $meta = stream_get_meta_data($this->stream);
        if ($key === null) {
            return $meta;
        }
        return $meta[$key] ?? null;
    }
}
