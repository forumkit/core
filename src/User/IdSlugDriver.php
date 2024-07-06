<?php

namespace Forumkit\User;

use Forumkit\Database\AbstractModel;
use Forumkit\Http\SlugDriverInterface;

/**
 * @implements SlugDriverInterface<User>
 */
class IdSlugDriver implements SlugDriverInterface
{
    /**
     * @var UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @param User $instance
     */
    public function toSlug(AbstractModel $instance): string
    {
        return (string) $instance->id;
    }

    /**
     * @return User
     */
    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return $this->users->findOrFail($slug, $actor);
    }
}
