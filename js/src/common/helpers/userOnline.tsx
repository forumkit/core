import type Mithril from 'mithril';
import User from '../models/User';
import icon from './icon';

/**
 * `useronline` 辅助函数在用户在线时显示一个绿色的圆圈。
 */
export default function userOnline(user: User): Mithril.Vnode<{}, {}> | null {
  if (user.lastSeenAt() && user.isOnline()) {
    return <span className="UserOnline">{icon('fas fa-circle')}</span>;
  }

  return null;
}
