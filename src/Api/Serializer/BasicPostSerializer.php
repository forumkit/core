<?php

namespace Forumkit\Api\Serializer;

use Exception;
use Forumkit\Foundation\ErrorHandling\LogReporter;
use Forumkit\Post\CommentPost;
use Forumkit\Post\Post;
use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasicPostSerializer extends AbstractSerializer
{
    /**
     * @var LogReporter
     */
    protected $log;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(LogReporter $log, TranslatorInterface $translator)
    {
        $this->log = $log;
        $this->translator = $translator;
    }
    /**
     * {@inheritdoc}
     */
    protected $type = 'posts';

    /**
     * {@inheritdoc}
     *
     * @param \Forumkit\Post\Post $post
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($post)
    {
        if (! ($post instanceof Post)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Post::class
            );
        }

        $attributes = [
            'number'      => (int) $post->number,
            'createdAt'   => $this->formatDate($post->created_at),
            'contentType' => $post->type
        ];

        if ($post instanceof CommentPost) {
            try {
                $attributes['contentHtml'] = $post->formatContent($this->request);
                $attributes['renderFailed'] = false;
            } catch (Exception $e) {
                $attributes['contentHtml'] = $this->translator->trans('core.lib.error.render_failed_message');
                $this->log->report($e);
                $attributes['renderFailed'] = true;
            }
        } else {
            $attributes['content'] = $post->content;
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($post)
    {
        return $this->hasOne($post, BasicUserSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function discussion($post)
    {
        return $this->hasOne($post, BasicDiscussionSerializer::class);
    }
}
