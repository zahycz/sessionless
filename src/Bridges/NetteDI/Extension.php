<?php declare(strict_types=1);
/**
 * @author Jan Žahourek
 */

namespace ZahyCZ\SessionLess\Bridges\NetteDI;

use Nette\Caching\Storages\FileStorage;
use Nette\Caching\Storages\SQLiteJournal;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\FileSystem;
use ZahyCZ\SessionLess\SessionLessHandler;

class Extension extends CompilerExtension {

    public function getConfigSchema(): Schema {
        return Expect::structure([
            'expiration' => Expect::string()->default('20 days'),
            'expirationSliding' => Expect::bool()->default(true),
            'path' => Expect::string()->default('/../session'),
            'storage' => Expect::string()->default('@' . $this->prefix('storage')),
            'saveNetteUserTags' => Expect::bool()->default(true)
        ]);
    }

    public function loadConfiguration(): void {
        $builder = $this->getContainerBuilder();

        $config = $this->config;

        if (!file_exists($config->path)) {
            FileSystem::createDir($config->path);
        }

        $builder->addDefinition($this->prefix('journal'))
            ->setFactory(SQLiteJournal::class, [$config->path . '/journal.s3db'])
            ->setAutowired(false);

        $builder->addDefinition($this->prefix('storage'))
            ->setFactory(FileStorage::class, ['dir' => $config->path, 'journal' => '@' . $this->prefix('journal')])
            ->setAutowired(false);


        $builder->addDefinition($this->prefix('sessionLessHandler'))
            ->setFactory(SessionLessHandler::class, ['storage' => $config->storage, 'expiration' => $config->expiration, 'expirationSliding' => $config->expirationSliding, 'saveNetteUserTags' => $config->saveNetteUserTags]);

        $builder->getDefinition('session')
            ->addSetup('setHandler', ['@' . $this->prefix('sessionLessHandler')]);
    }
}