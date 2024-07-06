import app from '../../common/app';
import type Mithril from 'mithril';
import User from '../models/User';

/**
 * `username` 辅助函数用于在 <span className="username"> 标签中显示用户的用户名。
 * 如果用户不存在，用户名将显示为 [deleted]。
 */
export default function username(user: User | null | undefined | false): Mithril.Vnode {
  const name = (user && user.displayName()) || app.translator.trans('core.lib.username.deleted_text');

  return <span className="username">{name}</span>;
}
