<?php declare(strict_types=1);
/**
 * PSR-7 compatible file stream
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 */
namespace Pechkin\Streams;

use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 compatible file stream implementation
 */
class FileStream implements StreamInterface
{
    private $resource;
    private $name;
    private $mode;
    private $metadata;

    private $size;
    private $seekable;
    private $readable;
    private $writable;

    private const READABLE = [
        'r', 'w+', 'r+', 'x+', 'c+','rb', 'w+b', 'r+b', 'x+b','c+b', 'rt', 'w+t', 'r+t','x+t', 'c+t', 'a+'
    ];
    private const WRITABLE = [
        'w', 'w+', 'rw', 'r+', 'x+','c+', 'wb', 'w+b', 'r+b','x+b', 'c+b', 'w+t', 'r+t','x+t', 'c+t', 'a', 'a+'
    ];

    /**
     * @param string $fileName  File name (e.g. php://temp)
     * @param string $mode      Type of access you require to the stream (see fopen mode for details)
     * @param array $metadata   Stream metadata
     */
    public function __construct(string $fileName, string $mode, array $metadata = [])
    {
        $this->name = $fileName;
        $this->mode = $mode;
        $this->metadata = $metadata;

        $this->resource = fopen($fileName, $mode);
        $this->size = -1;

        if (!$this->resource) {
            throw new \RuntimeException('Unable to open file '.$fileName);
        }

        $meta = stream_get_meta_data($this->resource);
        $this->seekable = (bool)$meta['seekable'];
        $this->readable = in_array($meta['mode'], self::READABLE);
        $this->writable = in_array($meta['mode'], self::WRITABLE);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        $this->assertClosed();
        
        $save = ftell($this->resource);
        fseek($this->resource, 0);
        $string = (string)stream_get_contents($this->resource);
        fseek($this->resource, $save);

        return $string;
    }

    public function close()
    {
        if (!$this->resource) return;
        fclose($this->resource);
        $this->detach();
    }

    public function detach()
    {
        if (!$this->resource) return null;

        $ret = $this->resource;
        $this->resource = false;

        return $ret;
    }

    public function getSize(): int
    {
        $this->assertClosed();

        if ($this->size >= 0) return $this->size;

        $stats = fstat($this->resource);
        if (!isset($stats['size'])) {
            throw new \RuntimeException('Unable to get file size '.$this->name);
        }
        $this->size = (int)$stats['size'];

        return $this->size;
    }

    public function tell(): int
    {
        $this->assertClosed();

        $result = ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return (int)$result;
    }

    public function eof(): bool
    {
        $this->assertClosed();

        return feof($this->resource);
    }

    public function isSeekable(): bool
    {
        $this->assertClosed();

        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->assertClosed();

        fseek($this->resource, $offset, $whence);
    }

    public function rewind()
    {
        $this->assertClosed();
        $this->seek(0);
    }

    public function isWritable()
    {
        $this->assertClosed();
        return $this->writable;
    }

    public function write($string)
    {
        $this->assertClosed();
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        $this->size = -1;
        $result = fwrite($this->resource, $string);

        if ($result === false) {
            throw new \RuntimeException('Failed to write to stream');
        }

        return $result;
    }

    public function isReadable()
    {
        $this->assertClosed();
        return $this->readable;
    }

    public function read($length)
    {
        $this->assertClosed();
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        if ($length < 0) {
            throw new \RuntimeException('Length parameter cannot be negative');
        }

        if (0 === $length) return '';

        $string = fread($this->resource, $length);
        if (false === $string) {
            throw new \RuntimeException('Failed to read from stream');
        }

        return $string;
    }

    public function getContents(): string
    {
        $this->assertClosed();

        $contents = stream_get_contents($this->resource);

        if ($contents === false) {
            throw new \RuntimeException('Failed to read stream contents');
        }

        return $contents;
    }

    public function getMetadata($key = null)
    {
        $this->assertClosed();

        if ($key === null){
            return array_merge($this->metadata, stream_get_meta_data($this->resource));
        }

        if (array_key_exists($key, $this->metadata)) return $this->metadata[$key];

        $metadata = stream_get_meta_data($this->resource);
        if (array_key_exists($key, $metadata)) return $metadata[$key];
        
        return null;
    }

    private function assertClosed()
    {
        if (!$this->resource) throw new \RuntimeException('The stream is closed');
    }

    /**
     * Creates php://temp FileStream with read/write acces from a given string
     * 
     * @param string $str   String
     * @param array $metadata   Stream metadata
     */
    public static function fromString(string $str, array $metadata = [])
    {
        $stream = new FileStream('php://temp', 'r+', $metadata);
        $stream->write($str);
        $stream->rewind();
        return $stream;
    }
}
