<?php

namespace MiniPhpRest\core\utils\session;

class SessionUtils
{

    /**
     * @param string $sessionPath Le path du site sur lequel s'applique la session. Ex: '/nasonemovies'
     * @param string|null $sessionName Le nom de la session. Ex: 'NASONEMOVIESSESSID'. Si null, alors le path sera utilisé (après nettoyage)
     * @return void
     */
    public static function sessionStart(string $sessionPath, string $sessionName = null) {


        $sessionPath = '/nasonemovies';

        if (empty($sessionName)) {
            $sessionName = self::getSessionName($sessionPath);
        }

        session_name($sessionName);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $sessionPath,
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'strict',
        ]);

        session_start();
    }

    public static function sessionDestroy(string $sessionPath) {

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $sessionPath,
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'strict',
        ]);

        session_destroy();
    }

    private static function getSessionName(string $sessionName) : string
    {
        $sName = str_replace(['/', '\\'], '_', $sessionName);
        $sName = str_replace(' ', '_', $sName);
        $sName = str_replace('-', '_', $sName);
        return 'SSID' . $sName;
    }

}