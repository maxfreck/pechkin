<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \Pechkin\f;
use \Pechkin\Streams\FileStream;

final class fnTest  extends TestCase
{
    public function testSetHostName()
    {
        f::$host = 'dummy.host';
        $this->assertEquals(
            'dummy.host',
            f::hostname()
        );
    }

    public function testEncode()
    {
        $this->assertEquals(
            "Hello world",
            f::encode("Hello world")
        );
        $this->assertEquals(
            " =?UTF-8?B?KCDNocKwIM2cypYgzaHCsCk=?=",
            f::encode("( ͡° ͜ʖ ͡°)")
        );
        $this->assertEquals(
            " =?UTF-8?B?RnJpZW5kcywgdGhpcyBpcyBjbGVhbi11cCB0aW1lIGFuZCB3ZSdyZSA=?=\n =?UTF-8?B?ZGlzY291bnRpbmcgYWxsIG91ciBzaWxlbnQsIGVsZWN0cmljIFViaWs=?=\n =?UTF-8?B?cyBieSB0aGlzIG11Y2ggbW9uZXkuIFllcywgd2UncmUgdGhyb3dpbmc=?=\n =?UTF-8?B?IGF3YXkgdGhlIGJsdWUtYm9vay4gQW5kIHJlbWVtYmVyOiBldmVyeSA=?=\n =?UTF-8?B?VWJpayBvbiBvdXIgbG90IGhhcyBiZWVuIHVzZWQgb25seSBhcyBkaXI=?=\n =?UTF-8?B?ZWN0ZWQu?=",
            f::encode("Friends, this is clean-up time and we're discounting all our silent, electric Ubiks by this much money. Yes, we're throwing away the blue-book. And remember: every Ubik on our lot has been used only as directed.")
        );
    }

    public function testSecure()
    {
        $this->assertEquals(
            "a multiline string!",
            f::secure("a\r\n multiline\r string\n!")
        );
    }

    public function testAddress()
    {
        $this->assertEquals(
            "john@example.com",
            f::address("john@example.com", "")
        );
        $this->assertEquals(
            "John Doe <john@example.com>",
            f::address("john@example.com", "John Doe")
        );
    }

    public function testBase64Stream()
    {
        $this->assertEquals(
            "KCDNocKwIM2cypYgzaHCsCk=",
            (string)f::base64Stream(FileStream::fromString("( ͡° ͜ʖ ͡°)"))
        );

    }

    public function testChunk()
    {
        $this->assertEquals(
            "0123456789|0123456789|",
            (string)f::chunk(FileStream::fromString("01234567890123456789"), 10, "|")
        );
        $this->assertEquals(
            "012|345|678|901|234|567|89|",
            (string)f::chunk(FileStream::fromString("01234567890123456789"), 3, "|")
        );

    }

    public function testFileMime()
    {
        $this->assertEquals(
            "text/x-php",
            f::fileMime(__file__)
        );
    }

}