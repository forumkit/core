<?php

namespace Forumkit\Install\Steps;

use Forumkit\Install\BaseUrl;
use Forumkit\Install\DatabaseConfig;
use Forumkit\Install\ReversibleStep;

class StoreConfig implements ReversibleStep
{
    private $debugMode;

    private $dbConfig;

    private $baseUrl;

    private $configFile;

    public function __construct($debugMode, DatabaseConfig $dbConfig, BaseUrl $baseUrl, $configFile)
    {
        $this->debugMode = $debugMode;
        $this->dbConfig = $dbConfig;
        $this->baseUrl = $baseUrl;

        $this->configFile = $configFile;
    }

    public function getMessage()
    {
        return '写入配置文件';
    }

    public function run()
    {
        file_put_contents(
            $this->configFile,
            '<?php return '.var_export($this->buildConfig(), true).';'
        );
    }

    public function revert()
    {
        @unlink($this->configFile);
    }

    private function buildConfig()
    {
        return [
            'debug'    => $this->debugMode,
            'database' => $this->dbConfig->toArray(),
            'url'      => (string) $this->baseUrl,
            'paths'    => $this->getPathsConfig(),
            'headers'  => [
                'poweredByHeader'  => true,
                'referrerPolicy' => 'same-origin',
            ]
        ];
    }

    private function getPathsConfig()
    {
        return [
            'api'   => 'apikit',
            'admin' => 'adminkit',
        ];
    }
}
