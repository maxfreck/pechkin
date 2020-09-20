<?php declare(strict_types=1);
/**
 * Email body representation
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 */
namespace Pechkin;

use \Psr\Http\Message\StreamInterface;

use \Pechkin\Streams\AppendStream;
use \Pechkin\Streams\FileStream;
use \Pechkin\Streams\NullStream;

/**
 * Implementation of class representing email body
 */
class Body
{
    protected $body = null;
    protected $altBody = null;
    protected $attachments = [];

    protected $boundary = '';

    /**
     * @param StreamInterface $body  email body
     */
    public function __construct(StreamInterface $body = null)
    {
        $this->setBody($body ?? new NullStream());
    }

    /**
     * Sets global body boundary
     *
     * @param string $boundary   Boundary
     */
    public function setBoundary(string $boundary)
    {
        $this->boundary = $boundary;
    }

    /**
     * Sets email body
     *
     * @param StreamInterface $body  Email body
     */
    public function setBody(StreamInterface $body)
    {
        $this->body = $body;
    }

    /**
     * Sets email alternative body
     *
     * @param StreamInterface $body  Email alternative body
     */
    public function setAltBody(StreamInterface $body)
    {
        $this->altBody = $body;
    }

    /**
     * @return StreamInterface email body stream
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Appends attachment
     *
     * @param StreamInterface $stream  Attachment stream
     * @param string $id  Optional attachment content ID
     * @return string attachment content ID
     */
    public function addAttachment(StreamInterface $stream): string
    {
        $id = $stream->getMetadata('id') ?? f::uniqid();
        $this->attachments[$id] = $stream;
        return $id;
    }

    /**
     * Removes attachment with specified content ID
     *
     * @param string $id  Attachment content ID
     */
    public function removeAttachment(string $id)
    {
        if (isset($this->attachments[$id])) unset($this->attachments[$id]);
    }

    /**
     * @return bool true in case body has attachments
     */
    public function hasAttachments(): bool
    {
        return (count($this->attachments) > 0);
    }

    /**
     * @return bool true in case body has attachments with inline disposition
     */
    public function hasInlineAttachments(): bool
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment->getMetadata('disposition') === 'inline') return true;
        }

        return false;
    }

    /**
     * @return bool true in case email has alternative body
     */
    public function hasAltBody(): bool
    {
        return ($this->altBody !== null);
    }

    /**
     * @return StreamInterface email body as PSR-7 compatible stream
     */
    public function __toStream(): StreamInterface
    {
        if ($this->hasAttachments()) {
            return $this->withAttachments();
        }
        if ($this->hasAltBody()) {
            return $this->withAltBody();
        }
        return $this->withSimpleBody();
    }

    protected function withAttachments(): StreamInterface
    {
        $altBody = $this->altBody ?? FileStream::fromString('This is a multipart message');
        $boundary = f::uniqid();

        $bodyHeader = new FileStream('php://temp', 'r+');
        $bodyHeader->write("\n\n--{$this->boundary}\n");
        $bodyHeader->write("Content-Type: multipart/alternative; boundary={$boundary}\n");
        $bodyHeader->write("Mime-Version: 1.0\n\n");
        $bodyHeader->rewind();

        $body = new AppendStream([
            $bodyHeader,
            $this->withHeader($altBody, $boundary),
            $this->withHeader($this->body, $boundary),
        ]);

        $ret = new AppendStream([$body]);

        foreach ($this->attachments as $id => &$attach) {
            $ret->addStream($this->attachWithHeader($id, $attach));
        }

        return $ret;
    }

    protected function attachWithHeader(string $id, StreamInterface $stream): StreamInterface
    {
        $contentType = $stream->getMetadata('content-type') ?? 'text/plain';
        $disposition = $stream->getMetadata('disposition') ?? 'attachment';
        $name = $stream->getMetadata('name') ?? f::uniqid().'.bin';

        $header = new FileStream('php://temp', 'r+');
        $header->write("\n\n--{$this->boundary}\n");
        $header->write("Content-Transfer-Encoding: base64\n");
        $header->write("Content-Type: {$contentType}\n");
        if ($disposition === 'inline') $header->write("Content-ID: <{$id}>\n");
        $header->write("Content-Disposition: {$disposition}; filename=\"{$name}\"\n\n");
        $header->rewind();
        
        return new AppendStream([
            $header,
            f::chunk(f::base64Stream($stream))
        ]);
    }

    protected function withAltBody(): StreamInterface
    {
        return new AppendStream([
            $this->withHeader($this->altBody, $this->boundary),
            $this->withHeader($this->body, $this->boundary),
        ]);
    }

    protected function withHeader(StreamInterface $stream, string $boundary): StreamInterface
    {
        $contentType = $stream->getMetadata('content-type') ?? 'text/plain';

        $header = new FileStream('php://temp', 'r+');
        $header->write("\n\n--{$boundary}\n");
        $header->write("Content-Transfer-Encoding: base64\n");
        $header->write("Content-Type: {$contentType}\n");
        $header->write("Mime-Version: 1.0\n\n");
        $header->rewind();

        return new AppendStream([
            $header,
            f::chunk(f::base64Stream($stream))
        ]);
    }

    protected function withSimpleBody(): StreamInterface
    {
        return new AppendStream([
            FileStream::fromString("\n"),
            f::chunk(f::base64Stream($this->body))
        ]);
    }
}