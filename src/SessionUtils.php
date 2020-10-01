<?php declare(strict_types=1);
/**
 * @author Jan Žahourek (Frits dot vanCampen at moxio dot com)
 */

namespace ZahyCZ\SessionLess;


use Nette\Security\IIdentity;

class SessionUtils {

    /**
     * @param string $sessionData
     * @return array
     */
    public static function getUserIdTagsFromSessionData(string $sessionData): array {
        $tags = [];
        if($netteSessionData = self::unserialize($sessionData)['__NF']['DATA'] ?? false) {
            foreach ($netteSessionData as $name => $value)  {
                if(strpos($name, 'Nette.Http.UserStorage') !== false && $value['identity'] instanceof IIdentity) {
                    $tags[] = $name.'/'.$value['identity']->getId();
                }
            }
        }
        return $tags;
    }
    
    /**
     * @param $session_data
     * @return array
     */
    public static function unserialize(string $session_data): array {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserialize_php($session_data);
                break;
            case "php_binary":
                return self::unserialize_phpbinary($session_data);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }

    /**
     * @param $session_data
     * @return array
     */
    private static function unserialize_php(string $session_data): array {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    /**
     * @param string $session_data
     * @return array
     */
    private static function unserialize_phpbinary(string $session_data): array {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}
