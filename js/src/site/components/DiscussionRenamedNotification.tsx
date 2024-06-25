import type Discussion from '../../common/models/Discussion';
import app from '../../site/app';
import Notification from './Notification';

interface DiscussionRenamedContent {
  postNumber: number;
}

/**
 * `DiscussionRenamedNotification` 组件用于显示一个通知，表明某个讨论的标题已被更改。
 */
export default class DiscussionRenamedNotification extends Notification {
  icon() {
    return 'fas fa-pencil-alt';
  }

  href() {
    const notification = this.attrs.notification;
    const discussion = notification.subject();

    if (!discussion) {
      return '#';
    }

    return app.route.discussion(discussion as Discussion, notification.content<DiscussionRenamedContent>().postNumber);
  }

  content() {
    return app.translator.trans('core.site.notifications.discussion_renamed_text', { user: this.attrs.notification.fromUser() });
  }

  excerpt() {
    return null;
  }
}
