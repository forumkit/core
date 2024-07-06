<?php

namespace Forumkit\Discussion;

use Forumkit\Database\AbstractModel;
use Forumkit\Http\SlugDriverInterface;
use Forumkit\User\User;

/**
 * @implements SlugDriverInterface<Discussion>
 */
class IdWithTransliteratedSlugDriver implements SlugDriverInterface
{
    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    /**
     * @param Discussion $instance
     */
    public function toSlug(AbstractModel $instance): string
    {
        return $instance->id.(trim($instance->slug) ? '-'.$instance->slug : '');
    }

    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        if (strpos($slug, '-')) {
            $slug_array = explode('-', $slug);
            $slug = $slug_array[0];
        }

        return $this->discussions->findOrFail($slug, $actor);
    }
}
