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
     * SessionLessHandler constructor.
     * @param string $expiration
     * @param bool $expirationSliding
     * @param IStorage $storage
     */
    public function __construct(IStorage $storage, string $expiration, bool $expirationSliding) {
        $this->expiration = $expiration;
        $this->expirationSliding = $expirationSliding;
        $this->storage = $storage;
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
        
        $this->cache->save($session_id, $session_data, [
            Cache::EXPIRE => $maxlifetime ?: $this->expiration,
            Cache::SLIDING => true,
        ]);

        return true;
    }
}