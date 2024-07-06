<?php

namespace Forumkit\Foundation;

use Carbon\Carbon;
use Forumkit\Locale\Translator;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\SessionManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SessionHandlerInterface;

class ApplicationInfoProvider
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @var SessionHandlerInterface
     */
    protected $sessionHandler;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @param SettingsRepositoryInterface $settings
     * @param Translator $translator
     * @param Schedule $schedule
     * @param ConnectionInterface $db
     * @param Config $config
     * @param SessionManager $session
     * @param SessionHandlerInterface $sessionHandler
     * @param Queue $queue
     */
    public function __construct(
        SettingsRepositoryInterface $settings,
        Translator $translator,
        Schedule $schedule,
        ConnectionInterface $db,
        Config $config,
        SessionManager $session,
        SessionHandlerInterface $sessionHandler,
        Queue $queue
    ) {
        $this->settings = $settings;
        $this->translator = $translator;
        $this->schedule = $schedule;
        $this->db = $db;
        $this->config = $config;
        $this->session = $session;
        $this->sessionHandler = $sessionHandler;
        $this->queue = $queue;
    }

    /**
     * 判断是否有任务已注册到调度器中。
     *
     * @return bool
     */
    public function scheduledTasksRegistered(): bool
    {
        return count($this->schedule->events()) > 0;
    }

    /**
     * 获取调度器的当前状态。
     *
     * @return string
     */
    public function getSchedulerStatus(): string
    {
        $status = $this->settings->get('schedule.last_run');

        if (! $status) {
            return $this->translator->trans('core.admin.dashboard.status.scheduler.never-run');
        }

        // 如果调度器在最近5分钟内没有运行，则标记为不活动。
        return Carbon::parse($status) > Carbon::now()->subMinutes(5)
            ? $this->translator->trans('core.admin.dashboard.status.scheduler.active')
            : $this->translator->trans('core.admin.dashboard.status.scheduler.inactive');
    }

    /**
     * 识别正在使用的队列驱动程序。
     *
     * @return string
     */
    public function identifyQueueDriver(): string
    {
        // 获取类名
        $queue = get_class($this->queue);
        // 去掉命名空间
        $queue = Str::afterLast($queue, '\\');
        // 将类名转换为小写
        $queue = strtolower($queue);
        // 去掉类似 SyncQueue, RedisQueue 的前缀
        $queue = str_replace('queue', '', $queue);

        return $queue;
    }

    /**
     * 识别我们连接的数据库版本。
     *
     * @return string
     */
    public function identifyDatabaseVersion(): string
    {
        return $this->db->selectOne('select version() as version')->version;
    }

    /**
     * 根据以下三种情况报告正在使用的会话驱动程序：
     *  1. 如果配置的会话驱动程序有效并且正在使用，将返回它。
     *  2. 如果配置的会话驱动程序无效，将回退到默认驱动程序并提及。
     *  3. 如果实际使用的驱动程序（即 `session.handler`）与当前使用的驱动程序（配置的或默认的）不同，则提及它。
     */
    public function identifySessionDriver(bool $forWeb = false): string
    {
        /*
         * 获取配置的驱动程序并回退到默认驱动程序。
         */
        $defaultDriver = $this->session->getDefaultDriver();
        $configuredDriver = Arr::get($this->config, 'session.driver', $defaultDriver);
        $driver = $configuredDriver;

        try {
            // 尝试获取配置的驱动程序实例。
            // 驱动程序实例是按需创建的。
            $this->session->driver($configuredDriver);
        } catch (InvalidArgumentException $e) {
            // 如果配置的驱动程序不是有效的驱动程序，则会抛出异常。
            // 因此我们回退到默认驱动程序。
            $driver = $defaultDriver;
        }

        /*
         * 从其类名中获取实际驱动程序名称。
         * 并将其与当前配置的驱动程序进行比较。
         */
        // 获取类名
        $handlerName = get_class($this->sessionHandler);
        // 去掉命名空间
        $handlerName = Str::afterLast($handlerName, '\\');
        // 将类名转换为小写
        $handlerName = strtolower($handlerName);
        // 去掉类似 FileSessionHandler, DatabaseSessionHandler 等的前缀
        $handlerName = str_replace('sessionhandler', '', $handlerName);

        if ($driver !== $handlerName) {
            return $forWeb ? $handlerName : "$handlerName <comment>(代码覆盖。配置为 <options=bold,underscore>$configuredDriver</>)</comment>";
        }

        if ($driver !== $configuredDriver) {
            return $forWeb ? $driver : "$driver <comment>(回退到默认驱动程序。配置为无效的驱动程序 <options=bold,underscore>$configuredDriver</>)</comment>";
        }

        return $driver;
    }

    /**
     * 识别当前的 PHP 版本。
     *
     * @return string
     */
    public function identifyPHPVersion(): string
    {
        return PHP_VERSION;
    }
}
