<?php declare(strict_types=1);
/**
 * Email headers representation
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 */
namespace Pechkin;

use \Psr\Http\Message\StreamInterface;
use \Pechkin\Streams\FileStream;

/**
 * Implementation of class representing email headers
 */
class Headers
{
    protected $headers = [];

    /**
     * @param string $email  Sender email
     * @param string $name  Sender name
     */
    public function __construct(string $email, string $name = '')
    {
        $this->initHeaders(fn::address($email, $name));
    }

    protected function initHeaders(string $from)
    {
        $this->headers = [
            'Date' => date('r'),
            'X-Mailer' => 'Pechkin',

            'From' => $from,

            'Reply-To' => [],
            'To' => [],
            'Cc' => [],
            'Bcc' => [],
        ];
        $this->generateMessageId();
    }

    /**
     * Generates Message-ID header
     */
    public function generateMessageId()
    {
        $this->setHeader('Message-ID', fn::encode(sprintf('<%s@%s>', fn::uniqid(), fn::hostname())));
    }

    /**
     * @return StreamInterface headers as PSR-7 compatible stream
     */
    public function __toStream(): StreamInterface
    {
        $ret = new FileStream('php://temp', 'w');

        foreach ($this->headers as $header => $value) {
            if (empty($value)) continue;
            if (is_array($value)) $value = implode(', ', $value);
            $value = fn::encode(fn::secure($value));
            $header = fn::secure($header);
            $ret->write("{$header}: {$value}\n");
        }

        $ret->rewind();
        return $ret;
    }

    /**
     * @return string headers as string
     */
    public function __toString(): string
    {
        return $this->__toStream()->getContents();
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
        $name = trim($name);
        $value = trim($value);

        if ($this->isForbidden($name)) return false;

        $this->headers[$name] = $value;

        return true;
    }

    protected function isForbidden(string $header): bool
    {
        return in_array(strtolower($header),[
            'to',
            'cc',
            'bcc'
        ]);
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
        return $this->setHeader('From', fn::address($email, $name));
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
        return $this->addAddress('Reply-To', $email, $name);
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
        return $this->addAddress('To', $email, $name);
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
        return $this->addAddress('Cc', $email, $name);
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
        return $this->addAddress('Bcc', $email, $name);
    }

    protected function addAddress(string $type, string $email, string $name = ''): bool
    {
        $email = trim($email);
        $this->headers[$type][$email] = fn::address($email, $name);
        return true;
    }

    /**
     * @return array array of recepient emails
     */
    public function getAllRecepients(): array
    {
        return array_merge(
            array_keys($this->headers['To']),
            array_keys($this->headers['Cc']),
            array_keys($this->headers['Bcc'])
        );
    }
}
