SyliusMailPlugin
================
[![Total Downloads](https://poser.pugx.org/gregoryeple/oauth-server-bundle/downloads.svg)](https://packagist.org/packages/gregoryeple/mail-plugin)

Configure how your emails are sent by Sylius.

# Installation-procedure
```bash
$ composer require gregoryeple/mail-plugin
```

## Enable the plugin

```php
// in app/AppKernel.php
public function registerBundles() {
	$bundles = array(
		// ...
		new BeHappy\SyliusMailPlugin\BeHappySyliusMailPlugin(),
	);
	// ...
}
```

```yml
#in app/config/config.yml
imports:
    ...
    - { resource: "@BeHappySyliusMailPlugin/Resources/config/app/config.yml" }
```

```yml
# in routing.yml
...

behappy_mail_plugin:
    resource: '@BeHappySyliusMailPlugin/Resources/config/routing.yml'
...
```

## Generate database

Simply launch

```bash
php bin/console doctrine:schema:update --dump-sql --force
``` 


# That's it !
In the BackOffice, you have now a new entry under the configuration menu where you can create your mail configuration. You can register one configuration by channel.

You can define the user sending address, their name and a reply-to.

DKIM Signature is also fully supported by setting the domain, the selector and the private key content.

Once your configuration is created, you can send a test email to any address and check the result. (don't forget do enable delivery in dev by modifying config_dev.yml)

# Repository history

This repository has been initialy created to make [BeHappyCommunication/SyliusMailPlugin](https://github.com/BeHappyCommunication/SyliusMailPlugin) compatible with PHP 8.

Works on Sylius 1.13. (other versions have not been tested)

## Credits
- Stephane DECOCK, and [all contributors](https://github.com/gregoryeple/SyliusMailPlugin/contributors)
- Forked from [SyliusMailPlugin](https://github.com/BeHappyCommunication/SyliusMailPlugin)
