<?php

namespace Forumkit\Notification;

use Carbon\Carbon;
use Forumkit\Database\AbstractModel;
use Forumkit\Notification\Blueprint\BlueprintInterface;
use Forumkit\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * 在数据库中模拟一个通知记录。
 *
 * 一个通知记录与用户相关联，并在其通知列表中显示。
 * 通知表示用户应该知道的事情已经发生，比如用户的讨论被其他人重命名。
 *
 * 每个通知记录都有一个类型。
 * 该类型决定了记录在通知列表中的外观，以及与之关联的主题。
 * 例如，'discussionRenamed' 通知类型表示有人重命名了用户的讨论。其主题是一个讨论，其ID存储在 `subject_id` 列中。
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $from_user_id
 * @property string $type
 * @property int|null $subject_id
 * @property mixed|null $data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $read_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Forumkit\User\User|null $user
 * @property \Forumkit\User\User|null $fromUser
 * @property \Forumkit\Database\AbstractModel|\Forumkit\Post\Post|\Forumkit\Discussion\Discussion|null $subject
 */
class Notification extends AbstractModel
{
    /**
     * 应该被转换为日期的属性。
     *
     * @var array
     */
    protected $dates = ['created_at', 'read_at'];

    /**
     * 通知类型和它们主体所使用的模型类的映射。
     * 例如，'discussionRenamed' 通知类型，表示用户的讨论被重命名，其主体模型类为 'Forumkit\Discussion\Discussion'。
     *
     * @var array
     */
    protected static $subjectModels = [];

    /**
     * 将通知标记为已读。
     *
     * @return void
     */
    public function read()
    {
        $this->read_at = Carbon::now();
    }

    /**
     * 在获取数据属性时，将数据库中存储的JSON反序列化为普通数组。
     *
     * @param string|null $value
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return $value !== null
            ? json_decode($value, true)
            : null;
    }

    /**
     * 在设置数据属性时，将其序列化为JSON以存储在数据库中。
     *
     * @param mixed $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * 通过在我们的主题模型映射中查找其类型来获取此通知记录的主题模型。
     *
     * @return string|null
     */
    public function getSubjectModelAttribute()
    {
        return $this->type ? Arr::get(static::$subjectModels, $this->type) : null;
    }

    /**
     * 定义与通知接收者的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 定义与通知发送者的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * 定义与通知主题的关系。
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject()
    {
        return $this->morphTo('subject', 'subjectModel');
    }

    /**
     * 限制查询以仅包括给定用户可见的通知主题。
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWhereSubjectVisibleTo(Builder $query, User $actor)
    {
        return $query->where(function ($query) use ($actor) {
            $classes = [];

            foreach (static::$subjectModels as $type => $class) {
                $classes[$class][] = $type;
            }

            foreach ($classes as $class => $types) {
                $query->orWhere(function ($query) use ($types, $class, $actor) {
                    $query->whereIn('type', $types)
                        ->whereExists(function ($query) use ($class, $actor) {
                            $query->selectRaw(1)
                                ->from((new $class)->getTable())
                                ->whereColumn('id', 'subject_id');

                            if (method_exists($class, 'registerVisibilityScoper')) {
                                $class::query()->setQuery($query)->whereVisibleTo($actor);
                            }
                        });
                });
            }
        });
    }

    /**
     * 限制查询以仅包括具有给定主题的通知。
     *
     * @param Builder $query
     * @param object $model
     * @return Builder
     */
    public function scopeWhereSubject(Builder $query, $model)
    {
        return $query->whereSubjectModel(get_class($model))
            ->where('subject_id', $model->id);
    }

    /**
     * 限制查询以仅包括使用给定主题模型的通知类型。
     *
     * @param Builder $query
     * @param string $class
     * @return Builder
     */
    public function scopeWhereSubjectModel(Builder $query, string $class)
    {
        $notificationTypes = array_filter(self::getSubjectModels(), function ($modelClass) use ($class) {
            return $modelClass === $class or is_subclass_of($class, $modelClass);
        });

        return $query->whereIn('type', array_keys($notificationTypes));
    }

    /**
     * 限制查询以查找与给定蓝图匹配的所有记录。
     *
     * @param Builder $query
     * @param BlueprintInterface $blueprint
     * @return Builder
     */
    public function scopeMatchingBlueprint(Builder $query, BlueprintInterface $blueprint)
    {
        return $query->where(static::getBlueprintAttributes($blueprint));
    }

    /**
     * 向给定接收者发送通知。
     *
     * @param User[] $recipients
     * @param BlueprintInterface $blueprint
     */
    public static function notify(array $recipients, BlueprintInterface $blueprint)
    {
        $attributes = static::getBlueprintAttributes($blueprint);
        $now = Carbon::now()->toDateTimeString();

        static::insert(
            array_map(function (User $user) use ($attributes, $now) {
                return $attributes + [
                    'user_id' => $user->id,
                    'created_at' => $now
                ];
            }, $recipients)
        );
    }

    /**
     * 获取类型到主题模型的映射。
     *
     * @return array
     */
    public static function getSubjectModels()
    {
        return static::$subjectModels;
    }

    /**
     * 为给定的通知类型设置主题模型。
     *
     * @param string $type 通知类型
     * @param string $subjectModel 该类型的主题模型的类名
     * @return void
     */
    public static function setSubjectModel($type, $subjectModel)
    {
        static::$subjectModels[$type] = $subjectModel;
    }

    protected static function getBlueprintAttributes(BlueprintInterface $blueprint): array
    {
        return [
            'type' => $blueprint::getType(),
            'from_user_id' => ($fromUser = $blueprint->getFromUser()) ? $fromUser->id : null,
            'subject_id' => ($subject = $blueprint->getSubject()) ? $subject->id : null,
            'data' => ($data = $blueprint->getData()) ? json_encode($data) : null
        ];
    }
}
