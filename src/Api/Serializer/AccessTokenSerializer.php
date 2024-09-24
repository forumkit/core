<?php

namespace Forumkit\Api\Serializer;

use Forumkit\Http\AccessToken;
use Jenssegers\Agent\Agent;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccessTokenSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'access-tokens';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param AccessToken $token
     */
    protected function getDefaultAttributes($token)
    {
        $session = $this->request->getAttribute('session');

        $agent = new Agent();
        $agent->setUserAgent($token->last_user_agent);

        $attributes = [
            'token' => $token->token,
            'userId' => $token->user_id,
            'createdAt' => $this->formatDate($token->created_at),
            'lastActivityAt' => $this->formatDate($token->last_activity_at),
            'isCurrent' => $session && $session->get('access_token') === $token->token,
            'isSessionToken' => in_array($token->type, ['session', 'session_remember'], true),
            'title' => $token->title,
            'lastIpAddress' => $token->last_ip_address,
            'device' => $this->translator->trans('core.forum.security.browser_on_operating_system', [
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
            ]),
        ];

        // 移除隐藏属性（如会话令牌上的令牌值）
        foreach ($token->getHidden() as $name) {
            unset($attributes[$name]);
        }

        // 无论非操作员是谁，都向其隐藏令牌值
        if (isset($attributes['token']) && $this->getActor()->id !== $token->user_id) {
            unset($attributes['token']);
        }

        return $attributes;
    }
}
