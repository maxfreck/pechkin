<?php declare(strict_types=1);
/**
 * PSR-7 compatible empty stream
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 */
namespace Pechkin\Streams;

use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 compatible empty stream implementation
 */
class NullStream implements StreamInterface
{

    private $closed;
    private $metadata;

    /**
     * @param array $metadata   Stream metadata
     */
    public function __construct(array $metadata = [])
    {
        $this->closed = false;
        $this->metadata = $metadata;
    }

    public function __toString(): string
    {
        $this->assertClosed();
        return '';
    }

    public function close()
    {
        $this->detach();
    }

    public function detach()
    {
        $this->closed = true;
        return null;
    }

    public function getSize(): int
    {
        $this->assertClosed();
        return 0;
    }

    public function tell(): int
    {
        $this->assertClosed();
        return 0;
    }

    public function eof(): bool
    {
        $this->assertClosed();
        return true;
    }

    public function isSeekable(): bool
    {
        $this->assertClosed();
        return false;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException(__class__.' is nonseekable stream');
    }

    public function rewind()
    {
        throw new \RuntimeException(__class__.' is nonseekable stream');
    }

    public function isWritable()
    {
        $this->assertClosed();
        return false;
    }

    public function write($string)
    {
        throw new \RuntimeException(__class__.' is nonwritable stream');
    }

    public function isReadable()
    {
        $this->assertClosed();
        return true;
    }

    public function read($length)
    {
        $this->assertClosed();
        return '';
    }

    public function getContents(): string
    {
        $this->assertClosed();
        return '';
    }

    public function getMetadata($key = null)
    {
        $this->assertClosed();

        if ($key === null) return $this->metadata;
        if (array_key_exists($key, $this->metadata)) return $this->metadata[$key];
        return null;
    }


    private function assertClosed()
    {
        if ($this->closed) throw new \RuntimeException('The stream is closed');
    }
}
