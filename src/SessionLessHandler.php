<?php declare(strict_types=1);
/**
 * @author Jan Žahourek
 */

namespace ZahyCZ\SessionLess;


use Nette\Caching\Cache;
use Nette\Caching\Storage;

class SessionLessHandler implements \SessionHandlerInterface {

    protected string $expiration;

    protected bool $expirationSliding;

    protected ?Cache $cache = null;

    protected Storage $storage;

    protected bool $saveNetteUserTags;

    /**
     * SessionLessHandler constructor.
     * @param Storage $storage
     * @param string $expiration
     * @param bool $expirationSliding
     * @param bool $saveNetteUserTags
     */
    public function __construct(
        Storage $storage,
        string $expiration,
        bool $expirationSliding,
        bool $saveNetteUserTags = true
    ) {
        $this->expiration = $expiration;
        $this->expirationSliding = $expirationSliding;
        $this->storage = $storage;
        $this->saveNetteUserTags = $saveNetteUserTags;
    }

    /**
     * @return bool
     */
    public function close(): bool {
        // not implemented, that is not necessary ;-)
        return true;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function destroy($id): bool {
        $this->cache->remove($id);
        return true;
    }

    /**
     * @param int $max_lifetime
     * @return int
     */
    public function gc($max_lifetime): int {
        return 1;
    }

    /**
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function open($path, $name): bool {
        $this->initCache();
        return true;
    }

    /**
     * @param string $id
     * @return string
     * @throws \Throwable
     */
    public function read($id): string {
        $value = $this->cache->load($id);
        return (string)$value;
    }

    /**
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data): bool {
        $maxlifetime = ini_get("session.gc_maxlifetime");

        $tags = ['Nette.Http.SessionLess'];

        $exists = true;
        
        if ($this->saveNetteUserTags) {
            [$userTags, $exists] = SessionUtils::getUserIdTagsFromSessionData($data);
            $tags = array_merge($tags, $userTags);
        }

        if($exists) {
            $this->cache->save($id, $data, [
                Cache::TAGS    => $tags,
                Cache::EXPIRE  => $maxlifetime ?: $this->expiration,
                Cache::SLIDING => $this->expirationSliding,
            ]);
        }
        
        return true;
    }

    /**
     * @param string $appName
     * @param string $userId
     */
    public function cleanByUserTag(string $appName, string $userId): void {
        $this->initCache();
        $this->cache->clean([
            Cache::TAGS => ['Nette.Http.UserStorage/' . $appName . '/' . $userId],
        ]);
    }

    /**
     * 
     */
    public function cleanAll(): void {
        $this->initCache();
        $this->cache->clean([
            Cache::TAGS => ['Nette.Http.UserStorage'],
        ]);
    }

    /**
     *
     */
    private function initCache(): void {
        if(!$this->cache) {
            $this->cache = new Cache($this->storage, 'SessionLess');
        }
    }
}
