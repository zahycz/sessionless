# sessionless
Non-I/O blocking PHP SessionHandler implementation using Nette/Caching with DI Extension for Nette framework

## Install
```yaml
composer require zahycz/sessionless
```

## Configuration
```yaml
extensions:
  sessionless: ZahyCZ\SessionLess\Bridges\NetteDI\Extension
  
sessionless:
    appName: myApp
    path: '/../../../data'
    expiration: 20 days
    expirationSliding: true
    #storage: @Nette\Caching\Storage
```
