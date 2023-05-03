# Blitz Fastly Purger

Fastly cache purger for the Blitz plugin for Craft CMS.

## Usage

1. Install the plugin and add the class to `cachePurgerTypes` in your `config/blitz.php` file

```php
'cachePurgerTypes' => [
    'mangochutney\blitzfastly\FastlyPurger',
],
```

2. Select the purger under the `Reverse Proxy Purging` tab in your Blitz settings and configure with your API key and service ID

Or configure in your Blitz config file

```php
'cachePurgerType' => 'mangochutney\blitzfastly\FastlyPurger',

'cachePurgerSettings' => [
    'apiKey' => 'FASTLY_API_TOKEN',
    'serviceId' => 'FASTLY_SERVICE_ID',
],
```
