<?php declare(strict_types=1);
/**
 * @author Jan Žahourek
 */

namespace ZahyCZ\SessionLess\Bridges\NetteDI;

use Nette\Caching\Storages\FileStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Nette\DI\CompilerExtension;
use Nette\Utils\FileSystem;
use ZahyCZ\SessionLess\SessionLessHandler;

class Extension extends CompilerExtension {

    public function loadConfiguration(): void {
        $builder = $this->getContainerBuilder();

        $config = $this->getConfig([
            'expiration' => '20 days',
            'expirationSliding' => true,
            'appName' => 'app',
            'path' => '',
            'storage' => '@' . $this->prefix('storage')
        ]);

        $path = $config['path'] . '/' . $config['appName'];

        if (!file_exists($path)) {
            FileSystem::createDir($path);
        }

        $builder->addDefinition($this->prefix('journal'))
            ->setFactory(SQLiteJournal::class, [$path . '/journal.s3db'])
            ->setAutowired(false);

        $builder->addDefinition($this->prefix('storage'))
            ->setFactory(FileStorage::class, ['dir' => $path, 'journal' => '@' . $this->prefix('journal')])
            ->setAutowired(false);


        $builder->addDefinition($this->prefix('sessionLessHandler'))
            ->setFactory(SessionLessHandler::class, ['storage' => $config['storage'], 'expiration' => $config['expiration'], 'expirationSliding' => $config['expirationSliding']]);

        $builder->getDefinition('session')
            ->addSetup('setHandler', ['@' . $this->prefix('sessionLessHandler')]);
    }
}