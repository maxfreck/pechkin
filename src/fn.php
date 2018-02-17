<?php declare(strict_types=1);
/**
 * Common library functions
 *
 * Copyright: Maxim Freck, 2018.
 * Authors:   Maxim Freck <maxim@freck.pp.ru>
 */
namespace Pechkin;

use \Psr\Http\Message\StreamInterface;

use \Pechkin\Streams\FileStream;

abstract class fn
{
    /**
     * Host name
     *
     * @var array
     */
    public static $host = '';

    /**
     * @return string host name
     */
    public static function hostname(): string
    {
        if (!empty(self::$host)) return self::$host;
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
    }

    /**
     * Composes content of a MIME header field
     *
     * @param string $str  Header content
     * @param string $str  Header character set (UTF-8 is default)
     * @return string encoded header content
     */
    public static function encode(string $str, string $charset = "UTF-8"): string
    {
        if (strlen($str) > 70 || self::hasUtf($str)) {
            return substr(iconv_mime_encode('', $str, [
                'input-charset' => $charset,
                'output-charset' => "UTF-8",
                'line-break-chars' => "\n"
            ]), 1);
        }

        return $str;
    }

    /**
     * Removes line breaks from a string
     *
     * @param string $str  String content
     * @return string secured string
     */
    public static function secure(string $str): string
    {
        return trim(str_replace(["\r", "\n"], '', $str));
    }


    /**
     * Determines whether the string contains UTF-8 characters
     *
     * @param string $str  String content
     * @return bool true if the string contains UTF-8 characters
     */
    protected static function hasUtf(string $str): bool
    {
        for ($i = 0; $i < strlen($str); $i++) {
            if (ord($str[$i]) > 127) return true;
        }
        return false;
    }

    /**
     * Generate a unique ID
     *
     * @return string a unique ID
     */
    public static function uniqid(): string
    {
        return \md5(\uniqid());
    }

    /**
     * Composes email address
     *
     * @param string $email  Email
     * @param string $name   Name
     * @return string composed address
     */
    public static function address(string $email, string $name): string
    {
        $email = trim($email);
        $name = trim($name);
        return empty($name)
            ? $email
            : "{$name} <{$email}>";
    }

    /**
     * Encodes stream with MIME base64
     *
     * @param StreamInterface $in  Input stream
     * @return FileStream stream encoded with MIME base64
     */
    public static function base64Stream(StreamInterface $in): StreamInterface
    {
        $out = new FileStream('php://filter/write=convert.base64-encode/resource=php://temp', 'r+');
        while (!$in->eof()) {
            $out->write($in->read(2048));
        }
        $out->rewind();
        return $out;
    }

    /**
     * Splits stream into chunks with delimiter
     *
     * @param StreamInterface $in  Input stream
     * @param string $size  Chunk size
     * @param string $delimiter  Chunk delimiter
     * @return FileStream output stream
     */
    public static function chunk(StreamInterface $in, int $size = 75, string $delimiter="\n"): StreamInterface
    {
        $out = new FileStream('php://temp', 'r+');
        while (!$in->eof()) {
            $out->write($in->read($size).$delimiter);
        }
        $out->rewind();
        return $out;
    }

    /**
     * Determines file MIME type
     *
     * @param string $name  File name
     * @return string file MIME type
     */
    public static function fileMime(string $name): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $name);
        finfo_close($finfo);
        return $mime;
    }
}