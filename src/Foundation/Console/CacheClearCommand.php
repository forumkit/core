<?php

namespace Forumkit\Foundation\Console;

use Forumkit\Console\AbstractCommand;
use Forumkit\Foundation\Event\ClearingCache;
use Forumkit\Foundation\Paths;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Events\Dispatcher;

class CacheClearCommand extends AbstractCommand
{
    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Store $cache
     * @param Paths $paths
     */
    public function __construct(Store $cache, Dispatcher $events, Paths $paths)
    {
        $this->cache = $cache;
        $this->events = $events;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('删除所有临时和生成的文件');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('正在清除缓存...');

        $succeeded = $this->cache->flush();

        if (! $succeeded) {
            $this->error('无法清除 `storage/cache`. 目录中的内容。请调整文件权限并再次尝试。这通常可以通过在管理仪表板页面上的 `工具` 下拉菜单中清除缓存来解决。');

            return 1;
        }

        $storagePath = $this->paths->storage;
        array_map('unlink', glob($storagePath.'/formatter/*'));
        array_map('unlink', glob($storagePath.'/locale/*'));
        array_map('unlink', glob($storagePath.'/views/*'));

        $this->events->dispatch(new ClearingCache);
    }
}
