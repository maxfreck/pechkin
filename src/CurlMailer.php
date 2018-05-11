<?php declare(strict_types=1);
/**
 * CurlMailer: sends email using cURL library
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 */
namespace Pechkin;

/**
 * Implementation of cURL mailer
 */
class CurlMailer extends AbstractMailer
{
    protected $proxy = null;
    protected $dataChunkSize = 1024;

    public function __construct(string $server, string $email, string $password, \stdClass $settings = null)
    {
        parent::__construct($server, $email, $password, $settings);
        $this->setHeader('X-Mailer', 'Pechkin '.self::versionString().'-php cURL mailer');
    }

    protected function applySettings(\stdClass $s)
    {
        parent::applySettings($s);

        if (isset($s->proxy) && !empty($s->proxy)) $this->setProxy($s->proxy);
        if (isset($s->dataChunkSize) && (int)$s->dataChunkSize > 0) $this->dataChunkSize = (int)$s->dataChunkSize;
    }

    /**
     * Sets proxy (for details see CURLOPT_PROXY)
     *
     * @param string $proxy   Proxy
     */
    public function setProxy(string $proxy)
    {
        if ($proxy === '') {
            $this->proxy = null;
        } else {
            $this->proxy = $proxy;
        }
    }

    /**
     * Sends email via cURL
     */
    public function send()
    {
        $recepients = $this->headers->getAllRecepients();
        if(count($recepients) === 0) {
            throw new \RuntimeException('No recipients were specified');
        }

        $ch = curl_init($this->server);

        curl_setopt_array($ch, [
            CURLOPT_MAIL_FROM => "<" . $this->user . ">",
            CURLOPT_USERNAME => $this->user,
            CURLOPT_PASSWORD => $this->password,
            CURLOPT_MAIL_RCPT => $recepients,
            CURLOPT_USE_SSL => CURLUSESSL_ALL,
            CURLOPT_UPLOAD => true,
            CURLOPT_CONNECTTIMEOUT => 30
        ]);

        if ($this->timeout > 0) curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if (!empty($this->proxy)) {
            if ($this->debug) echo __method__." using proxy {$this->proxy}\n";
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }

        if ($this->debug) curl_setopt($ch, CURLOPT_VERBOSE, true);

        $smtpStream = $this->buildDataStream();
        curl_setopt($ch, CURLOPT_READFUNCTION, function () use ($smtpStream) {
            if ($smtpStream->eof()) return null;
            return $smtpStream->read($this->dataChunkSize);
        });

        curl_exec($ch);

        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error #{$errno}: {$err}");
        }
    }
}
