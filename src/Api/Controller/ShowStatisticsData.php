<?php

namespace Forumkit\Api\Controller;

use Carbon\Carbon;
use DateTime;
use Forumkit\Discussion\Discussion;
use Forumkit\Http\RequestUtil;
use Forumkit\Post\Post;
use Forumkit\Post\RegisteredTypesScope;
use Forumkit\Settings\SettingsRepositoryInterface;
use Forumkit\User\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ShowStatisticsData implements RequestHandlerInterface
{
    /**
     * 用于缓存生命周期统计数据的时间量（秒为单位）。
     */
    public static $lifetimeStatsCacheTtl = 300;

    /**
     * 用于缓存定时统计数据的时间量（秒为单位）。
     */
    public static $timedStatsCacheTtl = 900;

    protected $entities = [];

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var CacheRepository
     */
    protected $cache;

    public function __construct(SettingsRepositoryInterface $settings, CacheRepository $cache)
    {
        $this->settings = $settings;
        $this->cache = $cache;

        $this->entities = [
            'users' => [User::query(), 'joined_at'],
            'discussions' => [Discussion::query(), 'created_at'],
            'posts' => [Post::where('type', 'comment')->withoutGlobalScope(RegisteredTypesScope::class), 'created_at']
        ];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        // 必须是管理员才能获取统计数据 -- 这仅在管理员控制面板中可见。
        // 确认当前用户是否为管理员。
        $actor->assertAdmin();

        $query = $request->getQueryParams();

        $reportingPeriod = Arr::get($query, 'period');
        $model = Arr::get($query, 'model');
        $customDateRange = Arr::get($query, 'dateRange');

        return new JsonResponse($this->getResponse($model, $reportingPeriod, $customDateRange));
    }

    private function getResponse(?string $model, ?string $period, ?array $customDateRange): array
    {
        if ($period === 'lifetime') {
            return $this->getLifetimeStatistics();
        }

        if (! Arr::exists($this->entities, $model)) {
            throw new InvalidParameterException('必须指定模型');
        }

        if ($period === 'custom') {
            $start = (int) $customDateRange['start'];
            $end = (int) $customDateRange['end'];

            if (! $customDateRange || ! $start || ! $end) {
                throw new InvalidParameterException('必须指定自定义日期范围');
            }

            // 基于秒的时间戳
            $startRange = Carbon::createFromTimestampUTC($start)->toDateTime();
            $endRange = Carbon::createFromTimestampUTC($end)->toDateTime();

            // 这个结果我们实际上不能缓存
            return $this->getTimedCounts($this->entities[$model][0], $this->entities[$model][1], $startRange, $endRange);
        }

        return $this->getTimedStatistics($model);
    }

    private function getLifetimeStatistics()
    {
        return $this->cache->remember('forumkit.lifetime_stats', self::$lifetimeStatsCacheTtl, function () {
            return array_map(function ($entity) {
                return $entity[0]->count();
            }, $this->entities);
        });
    }

    private function getTimedStatistics(string $model)
    {
        return $this->cache->remember("forumkit.timed_stats.$model", self::$lifetimeStatsCacheTtl, function () use ($model) {
            return $this->getTimedCounts($this->entities[$model][0], $this->entities[$model][1]);
        });
    }

    private function getTimedCounts(Builder $query, string $column, ?DateTime $startDate = null, ?DateTime $endDate = null)
    {
        $diff = $startDate && $endDate ? $startDate->diff($endDate) : null;

        if (! isset($startDate)) {
            // 需要前推12个月以及之前的周期
            $startDate = new DateTime('-2 years');
        } else {
            // 如果开始日期是自定义的，我们需要包含等量的时间在前边
            // 以展示之前周期的数据。
            $startDate = (new Carbon($startDate))->subtract($diff)->toDateTime();
        }

        if (! isset($endDate)) {
            $endDate = new DateTime();
        }

        $results = $query
            ->selectRaw(
                'DATE_FORMAT(
                    @date := '.$column.',
                    IF(@date > ?, \'%Y-%m-%d %H:00:00\', \'%Y-%m-%d\') -- 如果在过去24小时内，按小时分组
                ) as time_group',
                [new DateTime('-25 hours')]
            )
            ->selectRaw('COUNT(id) as count')
            ->where($column, '>', $startDate)
            ->where($column, '<=', $endDate)
            ->groupBy('time_group')
            ->pluck('count', 'time_group');

        $timed = [];

        $results->each(function ($count, $time) use (&$timed) {
            $time = new DateTime($time);
            $timed[$time->getTimestamp()] = (int) $count;
        });

        return $timed;
    }
}
