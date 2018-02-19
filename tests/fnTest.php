<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \Pechkin\fn;
use \Pechkin\Streams\FileStream;

final class fnTest  extends TestCase
{
    public function testSetHostName()
    {
        fn::$host = 'dummy.host';
        $this->assertEquals(
            'dummy.host',
            fn::hostname()
        );
    }

    public function testEncode()
    {
        $this->assertEquals(
            "Hello world",
            fn::encode("Hello world")
        );
        $this->assertEquals(
            " =?UTF-8?B?KCDNocKwIM2cypYgzaHCsCk=?=",
            fn::encode("( ͡° ͜ʖ ͡°)")
        );
        $this->assertEquals(
            " =?UTF-8?B?RnJpZW5kcywgdGhpcyBpcyBjbGVhbi11cCB0aW1lIGFuZCB3ZSdyZSA=?=\n =?UTF-8?B?ZGlzY291bnRpbmcgYWxsIG91ciBzaWxlbnQsIGVsZWN0cmljIFViaWs=?=\n =?UTF-8?B?cyBieSB0aGlzIG11Y2ggbW9uZXkuIFllcywgd2UncmUgdGhyb3dpbmc=?=\n =?UTF-8?B?IGF3YXkgdGhlIGJsdWUtYm9vay4gQW5kIHJlbWVtYmVyOiBldmVyeSA=?=\n =?UTF-8?B?VWJpayBvbiBvdXIgbG90IGhhcyBiZWVuIHVzZWQgb25seSBhcyBkaXI=?=\n =?UTF-8?B?ZWN0ZWQu?=",
            fn::encode("Friends, this is clean-up time and we're discounting all our silent, electric Ubiks by this much money. Yes, we're throwing away the blue-book. And remember: every Ubik on our lot has been used only as directed.")
        );
    }

    public function testSecure()
    {
        $this->assertEquals(
            "a multiline string!",
            fn::secure("a\r\n multiline\r string\n!")
        );
    }

    public function testAddress()
    {
        $this->assertEquals(
            "john@example.com",
            fn::address("john@example.com", "")
        );
        $this->assertEquals(
            "John Doe <john@example.com>",
            fn::address("john@example.com", "John Doe")
        );
    }

    public function testBase64Stream()
    {
        $this->assertEquals(
            "KCDNocKwIM2cypYgzaHCsCk=",
            (string)fn::base64Stream(FileStream::fromString("( ͡° ͜ʖ ͡°)"))
        );

    }

    public function testChunk()
    {
        $this->assertEquals(
            "0123456789|0123456789|",
            (string)fn::chunk(FileStream::fromString("01234567890123456789"), 10, "|")
        );
        $this->assertEquals(
            "012|345|678|901|234|567|89|",
            (string)fn::chunk(FileStream::fromString("01234567890123456789"), 3, "|")
        );

    }

    public function testFileMime()
    {
        $this->assertEquals(
            "text/x-php",
            fn::fileMime(__file__)
        );
    }

}