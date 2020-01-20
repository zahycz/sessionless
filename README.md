# sessionless
Non-I/O blocking PHP SessionHandler implementation using Nette/Caching with DI Extension for Nette framework

## Install
Warning! There is no stable version of this package yet!

```yaml
composer require zahycz/sessionless:dev-master
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
    #storage: @Nette\Caching\IStorage
```
