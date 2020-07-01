<?php declare(strict_types=1);
/**
 * @author Jan Žahourek
 */

namespace ZahyCZ\SessionLess;


use Nette\Caching\Cache;
use Nette\Caching\IStorage;

class SessionLessHandler implements \SessionHandlerInterface {

    /**
     * @var string
     */
    protected $expiration;

    /**
     * @var bool
     */
    protected $expirationSliding;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var IStorage
     */
    protected $storage;

    /**
     * @var bool
     */
    protected $saveNetteUserTags;

    /**
     * SessionLessHandler constructor.
     * @param IStorage $storage
     * @param string $expiration
     * @param bool $expirationSliding
     * @param bool $saveNetteUserTags
     */
    public function __construct(
        IStorage $storage,
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
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id): bool {
        $this->cache->remove($session_id);
        return true;
    }

    /**
     * @param int $maxlifetime
     * @return int
     */
    public function gc($maxlifetime): int {
        return 1;
    }

    /**
     * @param string $save_path
     * @param string $name
     * @return bool
     */
    public function open($save_path, $name): bool {
        $this->cache = new Cache($this->storage, 'SessionLess');
        return true;
    }

    /**
     * @param string $session_id
     * @return string
     */
    public function read($session_id): string {
        $value = $this->cache->load($session_id);
        return (string)$value;
    }

    /**
     * @param string $session_id
     * @param string $session_data
     * @return bool
     * @throws \Throwable
     */
    public function write($session_id, $session_data): bool {
        $maxlifetime = ini_get("session.gc_maxlifetime");

        $tags = ['Nette.Http.UserStorage'];

        if ($this->saveNetteUserTags) {
            $userTags = SessionUtils::getUserIdTagsFromSessionData($session_data);
            $tags = array_merge($tags, $userTags);
        }

        $this->cache->save($session_id, $session_data, [
            Cache::TAGS    => $tags,
            Cache::EXPIRE  => $maxlifetime ?: $this->expiration,
            Cache::SLIDING => true,
        ]);

        return true;
    }

    /**
     * @param string $appName
     * @param string $id
     */
    public function cleanByUserTag(string $appName, string $userId): void {
        $this->cache->clean([
            Cache::TAGS => ['Nette.Http.UserStorage/' . $appName . '/' . $userId],
        ]);
    }

    /**
     * 
     */
    public function cleanAll(): void {
        $this->cache->clean([
            Cache::TAGS => ['Nette.Http.UserStorage'],
        ]);
    }
}