import app from '../../site/app';
import Component from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import ItemList from '../../common/utils/ItemList';
import UserControls from '../utils/UserControls';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import icon from '../../common/helpers/icon';
import Dropdown from '../../common/components/Dropdown';
import Link from '../../common/components/Link';
import AvatarEditor from './AvatarEditor';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';

/**
 * `UserCard` 组件用于显示用户的个人资料卡。它在 `UserPage` 在hero部分）和讨论中都会使用，
 * 当鼠标悬停在帖子作者上时，会展示这个卡片。
 *
 * ### 属性（Attrs）
 *
 * - `user` 用户对象
 * - `className` 类名，用于自定义样式
 * - `editable` 是否可编辑（可能是一个布尔值）
 * - `controlsButtonClassName` 控制按钮的类名
 */
export default class UserCard extends Component {
  view() {
    // 获取传入的用户属性
    const user = this.attrs.user;

    // 根据用户和控制按钮生成控件数组
    const controls = UserControls.controls(user, this).toArray();

    // 获取用户的颜色（可能是个人资料的颜色或主题颜色）
    const color = user.color();

    // 获取用户的徽章数组（可能是用户的成就或标识）
    const badges = user.badges().toArray();

    return (
      <div className={classList('UserCard', this.attrs.className)} style={color && { '--usercard-bg': color }}>
        <div className="darkenBackground">
          <div className="container">
            {!!controls.length && (
              <Dropdown
                className="UserCard-controls App-primaryControl"
                menuClassName="Dropdown-menu--right"
                buttonClassName={this.attrs.controlsButtonClassName}
                label={app.translator.trans('core.site.user_controls.button')}
                accessibleToggleLabel={app.translator.trans('core.site.user_controls.toggle_dropdown_accessible_label')}
                icon="fas fa-ellipsis-v"
              >
                {controls}
              </Dropdown>
            )}

            <div className="UserCard-profile">
              <h1 className="UserCard-identity">
                {this.attrs.editable ? (
                  <>
                    <AvatarEditor user={user} className="UserCard-avatar" /> {username(user)}
                  </>
                ) : (
                  <Link href={app.route.user(user)}>
                    <div className="UserCard-avatar">{avatar(user, { loading: 'eager' })}</div>
                    {username(user)}
                  </Link>
                )}
              </h1>

              {!!badges.length && <ul className="UserCard-badges badges">{listItems(badges)}</ul>}

              <ul className="UserCard-info">{listItems(this.infoItems().toArray())}</ul>
            </div>
          </div>
        </div>
      </div>
    );
  }

  /**
   * 构建一个包含用户个人资料上显示的简短信息的项列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  infoItems() {
    const items = new ItemList();
    const user = this.attrs.user;
    const lastSeenAt = user.lastSeenAt();

    if (lastSeenAt) {
      const online = user.isOnline();

      items.add(
        'lastSeen',
        <span className={classList('UserCard-lastSeen', { online })}>
          {online
            ? [icon('fas fa-circle'), ' ', app.translator.trans('core.site.user.online_text')]
            : [icon('far fa-clock'), ' ', humanTime(lastSeenAt)]}
        </span>,
        100
      );
    }

    items.add('joined', app.translator.trans('core.site.user.joined_date_text', { ago: humanTime(user.joinTime()) }), 90);

    return items;
  }
}
