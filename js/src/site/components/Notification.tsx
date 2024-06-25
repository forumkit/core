import app from '../../site/app';
import type NotificationModel from '../../common/models/Notification';
import Component, { ComponentAttrs } from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import icon from '../../common/helpers/icon';
import humanTime from '../../common/helpers/humanTime';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import classList from '../../common/utils/classList';
import type Mithril from 'mithril';

export interface INotificationAttrs extends ComponentAttrs {
  notification: NotificationModel;
}

/**
 * `Notification` 组件抽象地显示单个通知。子类应该实现 `icon`, `href`, 和 `content` 方法。
 */
export default abstract class Notification<CustomAttrs extends INotificationAttrs = INotificationAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const notification = this.attrs.notification;
    const href = this.href?.() ?? '';

    const fromUser = notification.fromUser();

    return (
      <Link
        className={classList('Notification', `Notification--${notification.contentType()}`, [!notification.isRead() && 'unread'])}
        href={href}
        external={href.includes('://')}
        onclick={this.markAsRead.bind(this)}
      >
        {avatar(fromUser || null)}
        {icon(this.icon?.(), { className: 'Notification-icon' })}
        <span className="Notification-title">
          <span className="Notification-content">{this.content?.()}</span>
          <span className="Notification-title-spring" />
          {humanTime(notification.createdAt())}
        </span>
        {!notification.isRead() && (
          <Button
            className="Notification-action Button Button--link"
            icon="fas fa-check"
            title={app.translator.trans('core.site.notifications.mark_as_read_tooltip')}
            onclick={(e: Event) => {
              e.preventDefault();
              e.stopPropagation();

              this.markAsRead();
            }}
          />
        )}
        <div className="Notification-excerpt">{this.excerpt?.()}</div>
      </Link>
    );
  }

  /**
   * 获取应在通知中显示的图标的名称。
   */
  abstract icon(): string;

  /**
   * 获取通知应链接到的URL。
   */
  abstract href(): string;

  /**
   * 获取通知的内容。
   */
  abstract content(): Mithril.Children;

  /**
   * 获取通知的摘录。
   */
  abstract excerpt(): Mithril.Children;

  /**
   * 将通知标记为已读。
   */
  markAsRead() {
    if (this.attrs.notification.isRead()) return;

    app.session.user?.pushAttributes({ unreadNotificationCount: (app.session.user.unreadNotificationCount() ?? 1) - 1 });

    this.attrs.notification.save({ isRead: true });
  }
}
