<?php

# ============================================================================ #

/**
 *  M O G G I E: a PHP micro-framework.
 *
 * @copyright   Copyright (c) 2023, Nevison Aguilar <aguilardc1105@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 *
 */

namespace Core\Request;

use Psr\Http\Message\StreamInterface;

final class Stream implements StreamInterface
{
    use StreamTrait;

    /** @var resource|null A resource reference */
    private $stream;

    /** @var bool */
    private bool $seekable;

    /** @var bool */
    private bool $readable;

    /** @var bool */
    private bool $writable;

    /** @var array|mixed|void|bool|null */
    private $uri;

    /** @var int|null */
    private ?int $size;

    /** @var array Hash of readable and writable stream types */
    private const READ_WRITE_HASH = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    /**
     * @param resource $body
     */
    public function __construct($body)
    {
        if (!is_resource($body)) {
            throw new \InvalidArgumentException('First argument to Stream::__construct() must be resource');
        }

        $this->stream = $body;
        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'] && 0 === fseek($this->stream, 0, \SEEK_CUR);
        $this->readable = isset(self::READ_WRITE_HASH['read'][$meta['mode']]);
        $this->writable = isset(self::READ_WRITE_HASH['write'][$meta['mode']]);
    }

    /**
     * Creates a new PSR-7 stream.
     *
     * @param string|StreamInterface $body
     *
     * @return StreamInterface
     */
    public static function create(StreamInterface|string $body = ''): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (is_string($body)) {
            if (200000 <= strlen($body)) {
                $body = self::openZvalStream($body);
            } else {
                $resource = fopen('php://memory', 'r+');
                fwrite($resource, $body);
                fseek($resource, 0);
                $body = $resource;
            }
        }

        if (!is_resource($body)) {
            throw new \InvalidArgumentException('First argument to Stream::create() must be a string, resource or StreamInterface');
        }

        return new self($body);
    }

    /**
     * Closes the stream when the destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    /**
     * @return array|bool|mixed|void|null
     */
    private function getUri()
    {
        if (false !== $this->uri) {
            $this->uri = $this->getMetadata('uri') ?? false;
        }

        return $this->uri;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        if (null !== $this->size) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        // Clear the stat cache if the stream has a URI
        if ($uri = $this->getUri()) {
            clearstatcache(true, $uri);
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    /**
     * @return int
     */
    public function tell(): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (false === $result = @ftell($this->stream)) {
            throw new \RuntimeException('Unable to determine stream position: ' . (error_get_last()['message'] ?? ''));
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function eof(): bool
    {
        return !isset($this->stream) || feof($this->stream);
    }

    /**
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * @param $offset
     * @param $whence
     * @return void
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (-1 === fseek($this->stream, $offset, $whence)) {
            throw new \RuntimeException('Unable to seek to stream position "' . $offset . '" with whence ' . var_export($whence, true));
        }
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * @param $string
     * @return int
     */
    public function write($string): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        // We can't know the size after writing anything
        $this->size = null;

        if (false === $result = @fwrite($this->stream, $string)) {
            throw new \RuntimeException('Unable to write to stream: ' . (error_get_last()['message'] ?? ''));
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * @param $length
     * @return string
     */
    public function read($length): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        if (false === $result = @fread($this->stream, $length)) {
            throw new \RuntimeException('Unable to read from stream: ' . (error_get_last()['message'] ?? ''));
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (false === $contents = @stream_get_contents($this->stream)) {
            throw new \RuntimeException('Unable to read stream contents: ' . (error_get_last()['message'] ?? ''));
        }

        return $contents;
    }

    /**
     * @param null $key
     * @return mixed
     */
    public function getMetadata($key = null): mixed
    {
        if (null !== $key && !is_string($key)) {
            throw new \InvalidArgumentException('Metadata key must be a string');
        }

        if (!isset($this->stream)) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        if (null === $key) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    /**
     * @param string $body
     * @return false|resource
     */
    private static function openZValStream(string $body)
    {
        static $wrapper;

            $wrapper ?? stream_wrapper_register('ZValStream', $wrapper = get_class(new ZVal()));

        $context = stream_context_create(['ZValStream' => ['data' => $body]]);

        if (!$stream = @fopen('ZValStream://', 'r+', false, $context)) {
            stream_wrapper_register('ZValStream', $wrapper);
            $stream = fopen('ZValStream://', 'r+', false, $context);
        }

        return $stream;
    }
}
