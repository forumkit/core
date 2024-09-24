<?php

namespace Forumkit\Install\Steps;

use Forumkit\Foundation\Application;
use Forumkit\Install\Step;
use Forumkit\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;

class WriteSettings implements Step
{
    /**
     * @var ConnectionInterface
     */
    private $database;

    /**
     * @var array
     */
    private $custom;

    public function __construct(ConnectionInterface $database, array $custom)
    {
        $this->database = $database;
        $this->custom = $custom;
    }

    public function getMessage()
    {
        return '正在写入默认设置';
    }

    public function run()
    {
        $repo = new DatabaseSettingsRepository($this->database);

        $repo->set('version', Application::VERSION);

        foreach ($this->getSettings() as $key => $value) {
            $repo->set($key, $value);
        }
    }

    private function getSettings()
    {
        return $this->custom + $this->getDefaults();
    }

    private function getDefaults()
    {
        return [
            'allow_hide_own_posts' => 'reply',
            'allow_post_editing' => 'reply',
            'allow_renaming' => '10',
            'allow_sign_up' => '1',
            'custom_less' => '',
            'default_locale' => 'zh',
            'default_route' => '/all',
            'display_name_driver' => 'username',
            'extensions_enabled' => '[]',
            'site_name' => '社区驱动型知识库',
            'site_description' => 'Forumkit 是一个轻量级、可扩展的内容管理社区。',
            'mail_driver' => 'mail',
            'mail_from' => 'noreply@localhost',
            'slug_driver_Forumkit\Discussion\Discussion' => 'default',
            'slug_driver_Forumkit\User\User' => 'default',
            'theme_colored_header' => '1',
            'theme_dark_mode' => '0',
            'theme_primary_color' => '#1E87F0',
            'theme_secondary_color' => '#1E87F0',
            'welcome_message' => '令人难以置信的轻量，使用现代技术构建的可扩展讨论社区框架，灵活且快速，可以帮助您更好地构建成功的网站。Forumkit 轻松创建设计精美、用户友好、响应迅速的交流论坛。',
            'welcome_title' => '你好，我是 Forumkit,',
        ];
    }
}
