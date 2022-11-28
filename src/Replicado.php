<?php

namespace Uspdev\Replicado;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Replicado
{

    /** Instância da classe singleton */
    private static $instance;

    /** Instância do logger */
    private static $logger;

    # Variáveis de config
    public $host;
    public $port;
    public $database;
    public $username;
    public $password;
    public $codundclg;
    public $codundclgs;

    public $pathlog = '/tmp/replicado.log';
    public $usarCache = false;
    public $debug = false;
    public $sybase = true;

    protected $vars = ['host', 'port', 'database', 'username', 'password', 'pathlog', 'usarCache', 'debug', 'sybase', 'codundclg', 'codundclgs'];

    private function __construct()
    {}

    private function __clone()
    {}

    /**
     * Retorna uma instância do config existente ou cria uma nova
     *
     * @return \Uspdev\Replicado\Replicado
     */
    public static function getInstance($newConfig = [])
    {
        if (!SELF::$instance) {
            SELF::$instance = new Self;
            SELF::$instance->setConfig($newConfig);
        }
        return SELF::$instance;
    }

    /**
     * Retorna as configurações em uso
     *
     * Se não especificado $var retorna um array com todas as variáveis
     * Se retornar array, 'password vem mascarado'. Para obtê-lo use $var='password'
     *
     * @param $var Variável a ser retornada
     * @return Array|String
     */
    public static function getConfig($var = null)
    {
        $config = SELF::getInstance();
        if ($var) {
            return $config->$var;
        }
        foreach ($config->vars as $var) {
            $ret[$var] = $config->$var;
        }
        $ret['password'] = '**********'; //mascarando password

        return $ret;
    }

    /**
     * Aplica configuração do Replicado
     *
     * Pode ser passado por parâmetro (tem precedência) ou pegar pelo env.
     * Além dos parâmetros do env, pode ser passado
     * ['reset'=>true]
     * para reverter todas as configs para padrão env
     *
     * TODO: Ainda não foram tratados os configs de queries: CODUNDCLG e CODUNDCLGS. ****************
     *
     * @param Array $config (default=[])
     * @return Array Retorna configurações em uso
     */
    public static function setConfig(array $newConfig = [])
    {
        if (isset($newConfig['reset']) && $newConfig['reset'] == true) {
            SELF::$config = new Config();
        }
        $config = SELF::getInstance($newConfig);

        foreach ($config->vars as $var) {
            if (isset($newConfig[$var])) {
                $config->$var = $newConfig[$var];
            } else {
                // var=usarCache -> varSnake=usar_cache
                $varSnake = ltrim(strtoupper(preg_replace('/[A-Z]/', '_$0', $var)), '_');
                $config->$var = Uteis::env('REPLICADO_' . $varSnake, $config->$var);
            }
        }

        if (empty($config->host) || empty($config->port) || empty($config->username) || empty($config->password)) {
            $config->log('Config', 'Configurações do replicado incompletas.');
            die('Configurações do replicado incompletas.');
        }

        return $config->getConfig();
    }

    /**
     * Grava em log uma mensagem
     *
     * @param String $channelName
     * @param String $message
     * @return void
     */
    public function log(string $channelName, string $message)
    {
        if (!isset(SELF::$logger)) {
            SELF::$logger = new Logger($channelName);
            SELF::$logger->pushHandler(new StreamHandler($this->pathlog, Logger::DEBUG));
        }
        SELF::$logger->error($message);
    }

}