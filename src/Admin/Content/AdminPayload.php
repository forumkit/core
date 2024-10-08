<?php

namespace Forumkit\Admin\Content;

use Forumkit\Extension\ExtensionManager;
use Forumkit\Foundation\ApplicationInfoProvider;
use Forumkit\Foundation\Config;
use Forumkit\Frontend\Document;
use Forumkit\Group\Permission;
use Forumkit\Settings\Event\Deserializing;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminPayload
{
    /**
     * @var Container;
     */
    protected $container;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ApplicationInfoProvider
     */
    protected $appInfo;

    /**
     * @param Container $container
     * @param SettingsRepositoryInterface $settings
     * @param ExtensionManager $extensions
     * @param ConnectionInterface $db
     * @param Dispatcher $events
     * @param Config $config
     * @param ApplicationInfoProvider $appInfo
     */
    public function __construct(
        Container $container,
        SettingsRepositoryInterface $settings,
        ExtensionManager $extensions,
        ConnectionInterface $db,
        Dispatcher $events,
        Config $config,
        ApplicationInfoProvider $appInfo
    ) {
        $this->container = $container;
        $this->settings = $settings;
        $this->extensions = $extensions;
        $this->db = $db;
        $this->events = $events;
        $this->config = $config;
        $this->appInfo = $appInfo;
    }

    public function __invoke(Document $document, Request $request)
    {
        $settings = $this->settings->all();

        $this->events->dispatch(
            new Deserializing($settings)
        );

        $document->payload['settings'] = $settings;
        $document->payload['permissions'] = Permission::map();
        $document->payload['extensions'] = $this->extensions->getExtensions()->toArray();

        $document->payload['displayNameDrivers'] = array_keys($this->container->make('forumkit.user.display_name.supported_drivers'));
        $document->payload['slugDrivers'] = array_map(function ($resourceDrivers) {
            return array_keys($resourceDrivers);
        }, $this->container->make('forumkit.http.slugDrivers'));

        $document->payload['phpVersion'] = $this->appInfo->identifyPHPVersion();
        $document->payload['mysqlVersion'] = $this->appInfo->identifyDatabaseVersion();
        $document->payload['debugEnabled'] = Arr::get($this->config, 'debug');

        if ($this->appInfo->scheduledTasksRegistered()) {
            $document->payload['schedulerStatus'] = $this->appInfo->getSchedulerStatus();
        }

        $document->payload['queueDriver'] = $this->appInfo->identifyQueueDriver();
        $document->payload['sessionDriver'] = $this->appInfo->identifySessionDriver(true);

        /**
         * 用于管理员用户列表。实现方式与此匹配 forumkit/statistics 中的API。
         * 如果启用了 forumkit/statistics 扩展，它将用自己的统计信息覆盖此数据。
         *
         * 这使得前端代码可以更简单，并使用单一的真实数据源来拉取总用户数量。
         */
        $document->payload['modelStatistics'] = [
            'users' => [
                'total' => User::count()
            ]
        ];
    }
}
