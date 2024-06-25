import app from '../../site/app';
import Post from './Post';
import { ucfirst } from '../../common/utils/string';
import usernameHelper from '../../common/helpers/username';
import icon from '../../common/helpers/icon';
import Link from '../../common/components/Link';
import humanTime from '../../common/helpers/humanTime';
import classList from '../../common/utils/classList';

/**
 * `EventPost` 组件用于显示表示讨论事件的帖子，例如讨论被重命名或置顶。子类必须实现 `icon` 和 `description` 方法。
 *
 * ### 属性（Attrs）
 *
 * - 包含 `Post` 组件的所有属性
 *
 * @abstract
 */
export default class EventPost extends Post {
  elementAttrs() {
    const attrs = super.elementAttrs();

    attrs.className = classList(attrs.className, 'EventPost', ucfirst(this.attrs.post.contentType()) + 'Post');

    return attrs;
  }

  content() {
    const user = this.attrs.post.user();
    const username = usernameHelper(user);
    const data = Object.assign(this.descriptionData(), {
      user,
      username: user ? (
        <Link className="EventPost-user" href={app.route.user(user)}>
          {username}
        </Link>
      ) : (
        username
      ),
      time: humanTime(this.attrs.post.createdAt()),
    });

    return super
      .content()
      .concat([icon(this.icon(), { className: 'EventPost-icon' }), <div className="EventPost-info">{this.description(data)}</div>]);
  }

  /**
   * 获取事件图标的名称。
   *
   * @return {string}
   */
  icon() {
    return '';
  }

  /**
   * 获取事件的描述文本。
   *
   * @param {Record<string, unknown>} data 包含事件描述所需数据的对象
   * @return {import('mithril').Children} 在DOM中渲染的描述
   */
  description(data) {
    return app.translator.trans(this.descriptionKey(), data);
  }

  /**
   * 获取事件描述的翻译键。
   *
   * @return {string}
   */
  descriptionKey() {
    return '';
  }

  /**
   * 获取事件的描述翻译数据。
   *
   * @return {Record<string, unknown>}
   */
  descriptionData() {
    return {};
  }
}
