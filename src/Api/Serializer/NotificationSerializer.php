<?php

namespace Forumkit\Api\Serializer;

use Forumkit\Notification\Notification;
use InvalidArgumentException;

class NotificationSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'notifications';

    /**
     * 通知类型（键）到序列化器的映射，该序列化器应用于输出通知的主题（值）。
     *
     * @var array
     */
    protected static $subjectSerializers = [];

    /**
     * {@inheritdoc}
     *
     * @param \Forumkit\Notification\Notification $notification
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($notification)
    {
        if (! ($notification instanceof Notification)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Notification::class
            );
        }

        return [
            'contentType' => $notification->type,
            'content'     => $notification->data,
            'createdAt'   => $this->formatDate($notification->created_at),
            'isRead'      => (bool) $notification->read_at
        ];
    }

    /**
     * @param Notification $notification
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($notification)
    {
        return $this->hasOne($notification, BasicUserSerializer::class);
    }

    /**
     * @param Notification $notification
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function fromUser($notification)
    {
        return $this->hasOne($notification, BasicUserSerializer::class);
    }

    /**
     * @param Notification $notification
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function subject($notification)
    {
        return $this->hasOne($notification, function ($notification) {
            return static::$subjectSerializers[$notification->type];
        });
    }

    /**
     * @param $type
     * @param $serializer
     */
    public static function setSubjectSerializer($type, $serializer)
    {
        static::$subjectSerializers[$type] = $serializer;
    }
}
