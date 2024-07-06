import app from '../../site/app';
import PaginatedListState from '../../common/states/PaginatedListState';
import Notification from '../../common/models/Notification';

export default class NotificationListState extends PaginatedListState<Notification> {
  constructor() {
    super({}, 1, 20);
  }

  get type(): string {
    return 'notifications';
  }

  /**
   * 加载下一页的通知结果。
   */
  load(): Promise<void> {
    if (app.session.user?.newNotificationCount()) {
      this.pages = [];
      this.location = { page: 1 };
    }

    if (this.pages.length > 0) {
      return Promise.resolve();
    }

    app.session.user?.pushAttributes({ newNotificationCount: 0 });

    return super.loadNext();
  }

  /**
   * 将所有通知标记为已读。
   */
  markAllAsRead() {
    if (this.pages.length === 0) return;

    app.session.user?.pushAttributes({ unreadNotificationCount: 0 });

    this.pages.forEach((page) => {
      page.items.forEach((notification) => notification.pushAttributes({ isRead: true }));
    });

    return app.request({
      url: app.site.attribute('apiUrl') + '/notifications/read',
      method: 'POST',
    });
  }

  /**
   * 删除该用户的所有通知。
   */
  deleteAll() {
    if (this.pages.length === 0) return;

    app.session.user?.pushAttributes({ unreadNotificationCount: 0 });

    this.pages = [];

    return app.request({
      url: app.site.attribute('apiUrl') + '/notifications',
      method: 'DELETE',
    });
  }
}
