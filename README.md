Ushahidi Gmail Support
============

## What is this package for?
This gmail support library is a composer package, that extends the ushahidi platform datasource manager, adding *Gmail* as a data source. It allows the platform to authenticate a gmail account, giving the access to send and receive messages via Gmail Service API instead of the default POP/IMAP.

## Installation
You can install the package via composer:

```bash
$ composer require ushahidi/gmail-support
```

The above command will add the package as a dependency in your current project.

*Note: In a Ushahidi platform codebase, the package needs to be manually registered. Add the code below to the `boostrap/lumen.php` file*

```php
$app->register(Ushahidi\Gmail\GmailServiceProvider::class);
```

## Usage
Update your `config/services.php` file by adding your Gmail API credentials.

```php
    'gmail' => [
        'client_id' => env('GMAIL_CLIENT_ID'),
        'client_secret' => env('GMAIL_CLIENT_SECRET'),
        'redirect_uri' => env('GMAIL_REDIRECT_URI','urn:ietf:wg:oauth:2.0:oob'),
    ]
```

Update your `.env` file by adding your server key.

```env
GMAIL_CLIENT_ID=
GMAIL_CLIENT_SECRET=
GMAIL_REDIRECT_URI=
```

*Tip: For quick gmail authentication setup run the artisan command*

```bash
$ php artisan gmail:auth
```

To make use of this support package for mailing in your laravel app, update your `.env` and set your mail driver to `gmail`.

```env
MAIL_DRIVER=gmail
```




 










