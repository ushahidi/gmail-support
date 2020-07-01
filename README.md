Ushahidi Gmail Support
============

## What is this package for?
This gmail support library is a composer package, that extends the ushahidi platform datasource manager and adds gmail as a data provider. It allows the platform to authenticate a gmail account, giving the access to send and receive messages over gmail api.

## Installation
You can install the package via composer:

```bash
$ composer require ushahidi/gmail-support
```

The above command will add the package as a dependency in your current project.

*Note: This package is currently in development mode, so to set up, update the `composer.json` file in your usahidi platform codebase laravel installation.*

```json
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/webong/gmail-support"
        }
    ],
```

The package should automatically register itself. 

*Note: In a Ushahidi platform codebase, the package needs to be manually registered. Add the code below to the `boostrap/lumen.php` file*

```php
$app->register(Ushahidi\Gmail\GmailServiceProvider::class);
```

## Usage
Update your `config/services.php` file by adding your gmail credentials.

```php
    'gmail' => [
        'client_id' => env('GMAIL_ID'),
        'client_secret' => env('GMAIL_SECRET'),
        'redirect_uri' => env('GMAIL_REDIRECT_URI','urn:ietf:wg:oauth:2.0:oob'),
    ]
```

Update your `.env` file by adding your server key.

```env
GMAIL_ID=
GMAIL_SECRET=
GMAIL_REDIRECT_URI=
```

To make use of this support package for mailing in your laravel app, update your `.env` and set your mail driver to `gmail`.

```env
MAIL_DRIVER=gmail
```

*Tip: For quick gmail authentication setup run the artisan command*

```bash
$ php artisan gmail:auth
```



 










