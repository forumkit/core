<?php

namespace Forumkit\Notification\Blueprint;

use Forumkit\Database\AbstractModel;
use Forumkit\User\User;

/**
 * BlueprintInterface 是一个通知蓝图接口，当其实例化时，表示关于某事的通知。
 * 该蓝图由 NotificationSyncer 使用，用于将通知提交到数据库。
 */
interface BlueprintInterface
{
    /**
     * 获取发送通知的用户。
     *
     * @return User|null
     */
    public function getFromUser();

    /**
     * 获取此活动主题的模型。
     *
     * @return AbstractModel|null
     */
    public function getSubject();

    /**
     * 获取要存储在通知中的数据。
     *
     * @return mixed
     */
    public function getData();

    /**
     * 获取此活动的序列化类型。
     *
     * @return string
     */
    public static function getType();

    /**
     * 获取此活动主题模型的类名。
     *
     * @return string
     */
    public static function getSubjectModel();
}
