<?php declare(strict_types=1);
/**
 * PSR-7 compatible stream that wraps several streams one after the other.
 * This code is loosely based on guzzle/psr7 AppendStream code.
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 *
 *
 * guzzle/psr7 is:
 * Copyright: Michael Dowling, 2015.
 * Authors:   Michael Dowling <mtdowling@gmail.com>, Tobias Schultze
 */
namespace Pechkin\Streams;

use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR-7 compatible stream
 * that wraps several streams one after the other.
 */
class AppendStream implements StreamInterface
{
    private $streams = [];
    private $metadata = [];

    private $seekable = true;
    private $current = 0;
    private $pos = 0;

    /**
     * @param array $streams  Streams to wrap
     * @param array $metadata  Stream metadata
     */
    public function __construct(array $streams = [], array $metadata = [])
    {
        foreach ($streams as &$stream) {
            $this->addStream($stream);
        }
    }

    public function __toString()
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function addStream(\Psr\Http\Message\StreamInterface $stream)
    {
        if (!$stream->isReadable()) {
            throw new \InvalidArgumentException('Each stream must be readable');
        }

        // The stream is only seekable if all streams are seekable
        if (!$stream->isSeekable()) {
            $this->seekable = false;
        }

        $this->streams[] = $stream;
    }

    public function getContents()
    {
        $ret = '';
        foreach ($this->streams as &$stream) {
            $save = $stream->tell();
            $stream->seek(0);
            $ret.= $stream->getContents();
            $stream->seek($save);
        }
        return $ret;
    }

    public function close()
    {
        $this->pos = $this->current = 0;
        $this->seekable = true;

        foreach ($this->streams as &$stream) {
            $stream->close();
        }

        $this->streams = [];
    }

    public function detach()
    {
        $this->pos = $this->current = 0;
        $this->seekable = true;

        foreach ($this->streams as $stream) {
            $stream->detach();
        }

        $this->streams = [];
    }

    public function tell()
    {
        return $this->pos;
    }

    public function getSize()
    {
        $size = 0;

        foreach ($this->streams as &$stream) {
            $s = $stream->getSize();
            if ($s === null) {
                    return null;
            }
            $size += $s;
        }

        return $size;
    }

    public function eof()
    {
        return !$this->streams ||
            ($this->current >= count($this->streams) - 1 &&
                $this->streams[$this->current]->eof());
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('This AppendStream is not seekable');
        } elseif ($whence !== SEEK_SET) {
            throw new \RuntimeException('The AppendStream can only seek with SEEK_SET');
        }

        $this->pos = $this->current = 0;

        // Rewind each stream
        foreach ($this->streams as $i => $stream) {
            try {
                $stream->rewind();
            } catch (\Exception $e) {
                throw new \RuntimeException("Unable to seek stream {$i} of the AppendStream", 0, $e);
            }
        }

        // Seek to the actual position by reading from each stream
        while ($this->pos < $offset && !$this->eof()) {
            $result = $this->read(min(8096, $offset - $this->pos));
            if ($result === '') {
                break;
            }
        }
    }

    public function read($length)
    {
        $buffer = '';
        $total = count($this->streams) - 1;
        $remaining = $length;
        $progressToNext = false;

        while ($remaining > 0) {

            // Progress to the next stream if needed.
            if ($progressToNext || $this->streams[$this->current]->eof()) {
                $progressToNext = false;
                if ($this->current === $total) {
                    break;
                }
                $this->current++;
            }

            $result = $this->streams[$this->current]->read($remaining);

            // Using a loose comparison here to match on '', false, and null
            if ($result == null) {
                $progressToNext = true;
                continue;
            }

            $buffer .= $result;
            $remaining = $length - strlen($buffer);
        }

        $this->pos += strlen($buffer);

        return $buffer;
    }

    public function isReadable()
    {
        return true;
    }

    public function isWritable()
    {
        return false;
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function write($string)
    {
        throw new \RuntimeException('Cannot write to an AppendStream');
    }

    public function getMetadata($key = null)
    {
        if ($key === null) return $this->metadata;
        if (array_key_exists($key, $this->metadata)) return $this->metadata[$key];
        return null;
    }
}
