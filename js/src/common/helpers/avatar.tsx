import type Mithril from 'mithril';
import type { ComponentAttrs } from '../Component';
import User from '../models/User';
import classList from '../utils/classList';

export interface AvatarAttrs extends ComponentAttrs {}

/**
 * `avatar` 辅助函数用于显示用户的头像。
 *
 * @param user 用户信息
 * @param attrs 应用于头像元素的属性
 */
export default function avatar(user: User | null, attrs: ComponentAttrs = {}): Mithril.Vnode {
  attrs.className = classList('Avatar', attrs.className);
  attrs.loading ??= 'lazy';
  let content: string = '';

  // 如果 `title` 属性被设置为 null 或 false，则我们不希望给头像添加标题。
  // 另一方面，如果它根本没有被设置，我们可以安全地将其默认设置为用户的用户名。
  const hasTitle: boolean | string = attrs.title === 'undefined' || attrs.title;
  if (!hasTitle) delete attrs.title;

  // 如果传入了用户信息，那么我们将使用他们的上传图片或用户名的第一个字母（如果他们没有上传图片）来设置头像。
  if (user) {
    const username = user.displayName() || '?';
    const avatarUrl = user.avatarUrl();

    if (hasTitle) attrs.title = attrs.title || username;

    if (avatarUrl) {
      return <img {...attrs} src={avatarUrl} alt="" />;
    }

    content = username.charAt(0).toUpperCase();
    attrs.style = { '--avatar-bg': user.color() };
  }

  return <span {...attrs}>{content}</span>;
}
