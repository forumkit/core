import app from '../../site/app';
import Component from '../../common/Component';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Discussion from '../../common/models/Discussion';
import ItemList from '../../common/utils/ItemList';
import Tooltip from '../../common/components/Tooltip';

/**
 * `NotificationList` 组件用于显示已登录用户的通知列表，这些通知按讨论进行分组。
 */
export default class NotificationList extends Component {
  view() {
    const state = this.attrs.state;

    return (
      <div className="NotificationList">
        <div className="NotificationList-header">
          <h4 className="App-titleControl App-titleControl--text">{app.translator.trans('core.site.notifications.title')}</h4>

          <div className="App-primaryControl">{this.controlItems().toArray()}</div>
        </div>

        <div className="NotificationList-content">{this.content(state)}</div>
      </div>
    );
  }

  controlItems() {
    const items = new ItemList();
    const state = this.attrs.state;

    items.add(
      'mark_all_as_read',
      <Tooltip text={app.translator.trans('core.site.notifications.mark_all_as_read_tooltip')}>
        <Button
          className="Button Button--link"
          data-container=".NotificationList"
          icon="fas fa-check"
          title={app.translator.trans('core.site.notifications.mark_all_as_read_tooltip')}
          onclick={state.markAllAsRead.bind(state)}
        />
      </Tooltip>,
      70
    );

    items.add(
      'delete_all',
      <Tooltip text={app.translator.trans('core.site.notifications.delete_all_tooltip')}>
        <Button
          className="Button Button--link"
          data-container=".NotificationList"
          icon="fas fa-trash-alt"
          title={app.translator.trans('core.site.notifications.delete_all_tooltip')}
          onclick={() => {
            if (confirm(app.translator.trans('core.site.notifications.delete_all_confirm'))) {
              state.deleteAll.call(state);
            }
          }}
        />
      </Tooltip>,
      50
    );

    return items;
  }

  content(state) {
    if (state.isLoading()) {
      return <LoadingIndicator className="LoadingIndicator--block" />;
    }

    if (state.hasItems()) {
      return state.getPages().map((page) => {
        const groups = [];
        const discussions = {};

        page.items.forEach((notification) => {
          const subject = notification.subject();

          if (typeof subject === 'undefined') return;

          // 获取与此通知相关的讨论。如果它不是直接与讨论相关，它可能与帖子或其他与讨论相关的实体相关。
          let discussion = null;
          if (subject instanceof Discussion) discussion = subject;
          else if (subject && subject.discussion) discussion = subject.discussion();

          // 如果通知不是直接或间接与讨论相关，则我们将其分配给一个中性的组。
          const key = discussion ? discussion.id() : 0;
          discussions[key] = discussions[key] || { discussion: discussion, notifications: [] };
          discussions[key].notifications.push(notification);

          if (groups.indexOf(discussions[key]) === -1) {
            groups.push(discussions[key]);
          }
        });

        return groups.map((group) => {
          const badges = group.discussion && group.discussion.badges().toArray();

          return (
            <div className="NotificationGroup">
              {group.discussion ? (
                <Link className="NotificationGroup-header" href={app.route.discussion(group.discussion)}>
                  {badges && !!badges.length && <ul className="NotificationGroup-badges badges">{listItems(badges)}</ul>}
                  <span>{group.discussion.title()}</span>
                </Link>
              ) : (
                <div className="NotificationGroup-header">{app.site.attribute('title')}</div>
              )}

              <ul className="NotificationGroup-content">
                {group.notifications.map((notification) => {
                  const NotificationComponent = app.notificationComponents[notification.contentType()];
                  return (
                    !!NotificationComponent && (
                      <li>
                        <NotificationComponent notification={notification} />
                      </li>
                    )
                  );
                })}
              </ul>
            </div>
          );
        });
      });
    }

    return <div className="NotificationList-empty">{app.translator.trans('core.site.notifications.empty_text')}</div>;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.$notifications = this.$('.NotificationList-content');

    // 如果我们处于通知页面，窗口将会滚动，而不是 $notifications 元素。
    this.$scrollParent = this.inPanel() ? this.$notifications : $(window);

    this.boundScrollHandler = this.scrollHandler.bind(this);
    this.$scrollParent.on('scroll', this.boundScrollHandler);
  }

  onremove(vnode) {
    super.onremove(vnode);

    this.$scrollParent.off('scroll', this.boundScrollHandler);
  }

  scrollHandler() {
    const state = this.attrs.state;

    // 当监听整个页面的滚动事件时，我们在 `window`, 对象上监听，但我们需要从文档元素中获取实际的
    // scrollHeight, scrollTop, 和 clientHeight 
    const scrollParent = this.inPanel() ? this.$scrollParent[0] : document.documentElement;

    // 在非常短的屏幕上， scrollHeight + scrollTop 能无法完全达到 clientHeight
    // 可能只差几个像素，所以我们对此进行补偿。
    const atBottom = Math.abs(scrollParent.scrollHeight - scrollParent.scrollTop - scrollParent.clientHeight) <= 1;

    if (state.hasNext() && !state.isLoadingNext() && atBottom) {
      state.loadNext();
    }
  }

  /**
   * 如果 NotificationList 组件不在面板中（例如，在移动设备的 NotificationPage 上），我们需要监听窗口上的滚动事件，并从 body 获取滚动状态。
   */
  inPanel() {
    return this.$notifications.css('overflow') === 'auto';
  }
}
