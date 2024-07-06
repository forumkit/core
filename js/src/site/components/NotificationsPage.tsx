import app from '../../site/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import NotificationList from './NotificationList';
import type Mithril from 'mithril';
import extractText from '../../common/utils/extractText';

/**
 * `NotificationsPage` 组件用于显示通知列表。它仅在移动设备中使用，此时通知下拉菜单位于抽屉内。
 */
export default class NotificationsPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    app.history.push('notifications', extractText(app.translator.trans('core.site.notifications.title')));

    app.notifications.load();

    this.bodyClass = 'App--notifications';
  }

  view() {
    return (
      <div className="NotificationsPage">
        <NotificationList state={app.notifications}></NotificationList>
      </div>
    );
  }
}
