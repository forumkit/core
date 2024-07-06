<?php

namespace Forumkit\Foundation;

interface SiteInterface
{
    /**
     * 创建并启动 Forumkit 应用程序实例。
     *
     * @return AppInterface
     */
    public function bootApp(): AppInterface;
}
