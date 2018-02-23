<?php declare(strict_types=1);
/**
 * AbstractMailer: base class for all mailers
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 */
namespace Pechkin;

/**
 * Pechkin SemVer number.
 *
 * @var array
 */
const PECHKIN_SEMVER = [
    'major' => 0,
    'minor' => 5,
    'patch' => 2
];

use \Psr\Http\Message\StreamInterface;

use \Pechkin\Streams\AppendStream;
use \Pechkin\Streams\FileStream;

/**
 * Implementation of abstract mailer
 */
abstract class AbstractMailer
{
    protected $headers = null;
    protected $body = null;

    protected $server;
    protected $user;
    protected $password;

    protected $timeout = 0;
    protected $debug = false;

    /**
     * @param string $server    Smtp server name (e.g. smtps://smtp.example.com:465)
     * @param string $user      User name
     * @param string $password  Password
     * @param object $settings  Mailer settings
     */
    public function __construct(string $server, string $user, string $password, \stdClass $settings = null)
    {
        $this->server = $server;
        $this->user = $user;
        $this->password = $password;

        $this->headers = new Headers($this->user);
        $this->body = new Body();

        if ($settings !== null) {
            $this->applySettings($settings);
        }
    }

    protected function applySettings(\stdClass $s)
    {
        if (isset($s->debug) && $s->debug) $this->debug = true;

        if (isset($s->timeout)) $this->timeout = (int)$s->timeout;

        if (isset($s->from) && isset($s->from->email)) {
            $this->setFrom($s->from->email, $s->from->name ?? '');
        }

        if (isset($s->subject) && !empty($s->subject)) $this->setSubject($s->subject);
        if (isset($s->body) && ($s->body instanceof StreamInterface)) $this->setBody($s->body);
        if (isset($s->altBody) && ($s->altBody instanceof StreamInterface)) $this->setAltBody($s->altBody);

        if (isset($s->priority)) $this->setHeader('Priority', (string)((int)$s->proirity));

        if (isset($s->host)) {
            fn::$host = $s->host;
            $this->headers->generateMessageId();
        }

        if (isset($s->replyTo))
            foreach ($s->replyTo as $a)
                if (isset($a->email)) $this->addReplyTo($a->email, $a->name ?? '');

        if (isset($s->to))
            foreach ($s->to as $a)
                if (isset($a->email)) $this->addTo($a->email, $a->name ?? '');

        if (isset($s->cc))
            foreach ($s->cc as $a)
                if (isset($a->email)) $this->addCc($a->email, $a->name ?? '');

        if (isset($s->bcc))
            foreach ($s->bcc as $a)
                if (isset($a->email)) $this->addBcc($a->email, $a->name ?? '');
    }

    /**
     * Sets email header
     *
     * @param string $name   Header name
     * @param string $value  Header value
     * @return bool true if header is set
     */
    public function setHeader(string $name, string $value): bool
    {
        return $this->headers->setHeader($name, $value);
    }

    /**
     * Sets email subject
     *
     * @param string $subject  Email subject
     * @return bool true if subject is set
     */
    public function setSubject(string $subject): bool
    {
        return $this->setHeader('Subject', $subject);
    }

    /**
     * Sets From header
     *
     * @param string $email  Sender email
     * @param string $name   Sender name
     * @return bool true if From is set
     */
    public function setFrom(string $email, string $name = ''): bool
    {
        return $this->headers->setFrom($email, $name);
    }

    /**
     * Appends Reply-To recepient
     *
     * @param string $email  Email
     * @param string $name   Name
     * @return bool true in case of success
     */
    public function addReplyTo(string $email, string $name = ''): bool
    {
        return $this->headers->addReplyTo($email, $name);
    }

    /**
     * Appends To recepient
     *
     * @param string $email  Email
     * @param string $name   Name
     * @return bool true in case of success
     */
    public function addTo(string $email, string $name = ''): bool
    {
        return $this->headers->addTo($email, $name);
    }

    /**
     * Appends Cc recepient
     *
     * @param string $email  Email
     * @param string $name   Name
     * @return bool true in case of success
     */
    public function addCc(string $email, string $name = ''): bool
    {
        return $this->headers->addCc($email, $name);
    }

    /**
     * Appends Bcc recepient
     *
     * @param string $email  Email
     * @param string $name   Name
     * @return bool true in case of success
     */
    public function addBcc(string $email, string $name = ''): bool
    {
        return $this->headers->addBcc($email, $name);
    }

    /**
     * Sets email body
     *
     * @param StreamInterface $body  Email body
     */
    public function setBody(StreamInterface $body)
    {
        $this->body->setBody($body);
    }

    /**
     * Sets email alternative body
     *
     * @param StreamInterface $body  Email alternative body
     */
    public function setAltBody(StreamInterface $body)
    {
        $this->body->setAltBody($body);
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
        return $this->body->addAttachment($stream);
    }

    /**
     * Removes attachment with specified content ID
     *
     * @param string $id  Attachment content ID
     */
    public function removeAttachment(string $id)
    {
        $this->body->removeAttachment($id);
    }


    /**
     * Sends an email
     */
    abstract public function send();

    /**
     * Constructs stream containing email data
     *
     * @return StreamInterface email data
     */
    public function buildDataStream(): StreamInterface
    {
        if ($this->body->hasAttachments()) {
            return $this->buildAttachmentsEmailStream();
        }

        if ($this->body->hasAltBody()) {
            return $this->buildAltBodyEmailStream();
        }

        return $this->buildSimpleEmailStream();
    }

    protected function buildSimpleEmailStream(): StreamInterface
    {
        $this->setHeader('Content-Type', $this->body->getBody()->getMetadata('content-type') ?? 'text/plain');
        $this->setHeader('Content-Transfer-Encoding', 'base64');
        return $this->streams();
    }

    protected function streams(): StreamInterface
    {
        return new AppendStream([
            $this->headers->__toStream(),
            $this->body->__toStream()
        ]);
    }

    protected function buildAltBodyEmailStream(): StreamInterface
    {
        $boundary = fn::uniqid();
        $this->body->setBoundary($boundary);
        $this->setHeader('Content-Type', 'multipart/alternative; boundary='.$boundary);

        return $this->streams();
    }

    protected function buildAttachmentsEmailStream(): StreamInterface
    {
        $boundary = fn::uniqid();
        $this->body->setBoundary($boundary);
        $contentType = $this->body->hasInlineAttachments() ? 'multipart/related' : 'multipart/mixed';
        $this->setHeader('Content-Type', $contentType.'; boundary='.$boundary);

        return $this->streams();
    }

    /**
     * @return string current Pechkin version
     */
    public static function versionString(): string
    {
        return implode('.', PECHKIN_SEMVER);
    }
}