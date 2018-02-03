# Pechkin — a small and easy-to-use mailing library for PHP

## Features

- Support for PSR-7 streams.
- Send emails with multiple To, Cc, Bcc and Reply-to addresses.
- Send emails through http/socks proxy.
- Support for raw, multipart/alternative, multipart/mixed and multipart/related emails.

## Installing Pechkin

The recommended way to install Pechkin is through [Composer](http://getcomposer.org).

Install Composer:

```bash
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of Pechkin:

```bash
php composer.phar require maxfreck/pechkin
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

You can then later update Pechkin using composer:

 ```bash
composer.phar update
 ```

## Using Pechkin

All email metainformation (content type, file name, attachment disposition) is passed as a stream metadata.

Basic usage example:

```php
<?php

//use composer's autoloader
require '../vendor/autoload.php';

use \Pechkin\CurlMailer;
use \Pechkin\Streams\FileStream;

use \Pechkin\fn;


fn::$host = 'example.org';

$body = <<<EOD
<h1>Lorem ipsum</h1>
<div><img src="cid:img1"></div>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute
irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla
pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia
deserunt mollit anim id est laborum.</p>
EOD;

$mailer = new CurlMailer(
    "smtps://smtp.example.org:465",
    "nemo@example.org",
    "Nemo's password",
    (object)[
        'from' => (object)['email' => 'nemo@example.org', 'name' => 'Nemo Nobody'],
        'replyTo' => [
            (object)['email' => 'nemo@example.org', 'name' => 'Nemo Nobody'],
        ],
        'to' => [
            (object)['email' => 'foo@example.com', 'Mr. Foo'],
        ],
        'subject' => "Hello, Mr. Foo",
        'body' => FileStream::fromString($body, ['content-type' => 'text/html; charset="utf-8"']),
        'altBody' => FileStream::fromString("This is alt body"),
        //'proxy' => 'socks5://127.0.0.1:8080' //See CURLOPT_PROXY for more information
    ]
);

$mailer->addAttachment(
    new FileStream(
        'image.png',
        'r',
        [
            'content-type' => fn::fileMime('image.png'),
            'name' => 'image.png',
            'disposition' => 'inline'
        ]
    ),
    'img1' //Attachment content ID
);

$mailer->send();
```

## To-Do

- Unit tests. There is no unit tests at all for now.
- Raw SMTP sender similar to PHPMailer.