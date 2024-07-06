import app from '../../site/app';
import Component from '../../common/Component';
import Checkbox from '../../common/components/Checkbox';
import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';

/**
 * `NotificationGrid` 组件用于显示一个包含通知类型和方法的表格，允许用户切换每种组合。
 *
 * ### 属性Attrs
 *
 * - `user` 
 */
export default class NotificationGrid extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * 可用通知方法的信息。
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    this.methods = this.notificationMethods().toArray();

    /**
     * 一个映射，表示哪些通知复选框正在加载。
     *
     * @type {Record<string, boolean>}
     */
    this.loading = {};

    /**
     * 可用通知类型的信息。
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    this.types = this.notificationTypes().toArray();
  }

  view() {
    const preferences = this.attrs.user.preferences();

    return (
      <table className="NotificationGrid">
        <thead>
          <tr>
            <td />
            {this.methods.map((method) => (
              <th className="NotificationGrid-groupToggle" onclick={this.toggleMethod.bind(this, method.name)}>
                {icon(method.icon)} {method.label}
              </th>
            ))}
          </tr>
        </thead>

        <tbody>
          {this.types.map((type) => (
            <tr>
              <td className="NotificationGrid-groupToggle" onclick={this.toggleType.bind(this, type.name)}>
                {icon(type.icon)} {type.label}
              </td>
              {this.methods.map((method) => {
                const key = this.preferenceKey(type.name, method.name);

                return (
                  <td className="NotificationGrid-checkbox">
                    <Checkbox
                      state={!!preferences[key]}
                      loading={this.loading[key]}
                      disabled={!(key in preferences)}
                      onchange={this.toggle.bind(this, [key])}
                    >
                      <span className="sr-only">
                        {app.translator.trans('core.site.settings.notification_checkbox_a11y_label_template', {
                          description: type.label,
                          method: method.label,
                        })}
                      </span>
                    </Checkbox>
                  </td>
                );
              })}
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.$('thead .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
      const i = parseInt($(this).index(), 10) + 1;
      $(this)
        .parents('table')
        .find('td:nth-child(' + i + ')')
        .toggleClass('highlighted', e.type === 'mouseenter');
    });

    this.$('tbody .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
      $(this)
        .parent()
        .find('td')
        .toggleClass('highlighted', e.type === 'mouseenter');
    });
  }

  /**
   * 根据第一个偏好的值切换给定偏好的状态。
   *
   * @param {string[]} keys
   */
  toggle(keys) {
    const user = this.attrs.user;
    const preferences = user.preferences();
    const enabled = !preferences[keys[0]];

    keys.forEach((key) => {
      this.loading[key] = true;
      preferences[key] = enabled;
    });

    m.redraw();

    user.save({ preferences }).then(() => {
      keys.forEach((key) => (this.loading[key] = false));

      m.redraw();
    });
  }

  /**
   * 切换给定方法的所有通知类型。
   *
   * @param {string} method
   */
  toggleMethod(method) {
    const keys = this.types.map((type) => this.preferenceKey(type.name, method)).filter((key) => key in this.attrs.user.preferences());

    this.toggle(keys);
  }

  /**
   * 切换给定类型的所有通知方法。
   *
   * @param {string} type
   */
  toggleType(type) {
    const keys = this.methods.map((method) => this.preferenceKey(type, method.name)).filter((key) => key in this.attrs.user.preferences());

    this.toggle(keys);
  }

  /**
   * 获取给定通知类型-方法组合的偏好键名称。
   *
   * @param {string} type
   * @param {string} method
   * @return {string}
   */
  preferenceKey(type, method) {
    return 'notify_' + type + '_' + method;
  }

  /**
   * 构建一个通知方法的列表项，用于在网格中显示。
   *
   * 每个通知方法都是一个对象，具有以下属性：
   *
   * - `name` 通知方法的名称
   * - `icon` 在列头中显示的图标
   * - `label` 在列头中显示的标签
   *
   * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children }>}
   */
  notificationMethods() {
    const items = new ItemList();

    items.add('alert', {
      name: 'alert',
      icon: 'fas fa-bell',
      label: app.translator.trans('core.site.settings.notify_by_web_heading'),
    });

    items.add('email', {
      name: 'email',
      icon: 'far fa-envelope',
      label: app.translator.trans('core.site.settings.notify_by_email_heading'),
    });

    return items;
  }

  /**
   * 为在网格中显示的通知类型构建项目列表。
   *
   * 每个通知类型都是一个对象，具有以下属性：
   *
   * - `name` 通知类型的名称
   * - `icon` 在通知网格行中显示的图标
   * - `label` 在通知网格行中显示的标签
   *
   * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children}>}
   */
  notificationTypes() {
    const items = new ItemList();

    items.add('discussionRenamed', {
      name: 'discussionRenamed',
      icon: 'fas fa-pencil-alt',
      label: app.translator.trans('core.site.settings.notify_discussion_renamed_label'),
    });

    return items;
  }
}
