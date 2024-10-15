# Doctrine Lock Messenger Bundle

The `doctrine-lock-messenger-bundle` provides middleware for Symfony Messenger to handle message deduplication and waiting mechanisms using Doctrine and Symfony Lock.

## Installation

To install the bundle, use Composer:

```bash
composer require bigoen/doctrine-lock-messenger-bundle
```

## Configuration

Add the bundle to your Symfony application by updating the `config/bundles.php` file:

```php
return [
    // ...
    Bigoen\DoctrineLockMessengerBundle\BigoenCurrencyApiBundle::class => ['all' => true],
];
```

If the bundle is not automatically added, you need to manually add it to the `config/bundles.php` file as shown above.

## Usage

### Deduplication Middleware

The `DeduplicationMiddleware` ensures that duplicate messages are not processed concurrently.

```php
use Symfony\Component\Messenger\MessageBusInterface;
use Bigoen\DoctrineLockMessengerBundle\Stamp\DeduplicationStamp;

$message = new YourMessage();
$envelope = (new Envelope($message))->with(new DeduplicationStamp('unique_key', 300));

$bus->dispatch($envelope);
```

### Wait Middleware

The `WaitMiddleware` allows messages to be delayed until a lock is acquired.

```php
use Symfony\Component\Messenger\MessageBusInterface;
use Bigoen\DoctrineLockMessengerBundle\Stamp\WaitStamp;

$message = new YourMessage();
$envelope = (new Envelope($message))->with(new WaitStamp('unique_key', 300));

$bus->dispatch($envelope);
```

## Example

Here is an example of how to use both middlewares in a Symfony Messenger configuration:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            messenger.bus.default:
                middleware:
                    - Bigoen\DoctrineLockMessengerBundle\Middleware\DeduplicationMiddleware
                    - Bigoen\DoctrineLockMessengerBundle\Middleware\WaitMiddleware
```

You can also configure the default message bus for the `LockMessageBus`:

```yaml
# config/services.yaml
services:
    bigoen_doctrine_lock_messenger.message_bus:
        alias: 'messenger.bus.default'
```

## License

This bundle is released under the MIT License. See the bundled LICENSE file for details.
```