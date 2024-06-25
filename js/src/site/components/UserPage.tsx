import app from '../../site/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import UserCard from './UserCard';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import SelectDropdown from '../../common/components/SelectDropdown';
import LinkButton from '../../common/components/LinkButton';
import Separator from '../../common/components/Separator';
import listItems from '../../common/helpers/listItems';
import AffixedSidebar from './AffixedSidebar';
import type User from '../../common/models/User';
import type Mithril from 'mithril';

export interface IUserPageAttrs extends IPageAttrs {}

/**
 * `UserPage` 组件显示用户的个人资料。它可以扩展以在内容区域内显示内容。请参见 `ActivityPage` 和 `SettingsPage` 以获取示例。
 *
 * @abstract
 */
export default class UserPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs, CustomState = undefined> extends Page<CustomAttrs, CustomState> {
  /**
   * 此页面所属的用户。
   */
  user: User | null = null;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.bodyClass = 'App--user'; // 设置页面的主体类为 'App--user' 。
  }

  /**
   * 用户页面的基础视图模板。
   */
  view() {
    return (
      <div className="UserPage">
        {this.user
          ? [
              <UserCard
                user={this.user}
                className="Hero UserHero"
                editable={this.user.canEdit() || this.user === app.session.user}
                controlsButtonClassName="Button"
              />,
              <div className="container">
                <div className="sideNavContainer">
                  <AffixedSidebar>
                    <nav className="sideNav UserPage-nav">
                      <ul>{listItems(this.sidebarItems().toArray())}</ul>
                    </nav>
                  </AffixedSidebar>
                  <div className="sideNavOffset UserPage-content">{this.content()}</div>
                </div>
              </div>,
            ]
          : [<LoadingIndicator display="block" />]}
      </div>
    );
  }

  /**
   * 获取要在用户页面中显示的内容。
   */
  content(): Mithril.Children | void {}

  /**
   * 使用用户初始化组件，并触发其活动源的加载。
   *
   * @protected
   */
  show(user: User): void {
    this.user = user;

    app.current.set('user', user);

    app.setTitle(user.displayName());

    m.redraw();
  }

  /**
   * 根据给定的用户名，从存储中加载用户的个人资料，或者如果我们还没有该资料，则发起一个请求。
   * 然后使用该用户初始化个人资料页面。
   */
  loadUser(username: string) {
    const lowercaseUsername = username.toLowerCase();

    // 将预加载的用户对象（如果有）加载到全局应用商店中 我们不使用该方法的输出，因为它返回原始 JSON 而不是分析的模型
    app.preloadedApiDocument();

    app.store.all<User>('users').some((user) => {
      if ((user.username().toLowerCase() === lowercaseUsername || user.id() === username) && user.joinTime()) {
        this.show(user);
        return true;
      }

      return false;
    });

    if (!this.user) {
      app.store.find<User>('users', username, { bySlug: true }).then(this.show.bind(this));
    }
  }

  /**
   * 为侧边栏的内容构建项目列表。
   */
  sidebarItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'nav',
      <SelectDropdown className="App-titleControl" buttonClassName="Button">
        {this.navItems().toArray()}
      </SelectDropdown>
    );

    return items;
  }

  /**
   * 为边栏中的导航构建项目列表。
   */
  navItems() {
    const items = new ItemList<Mithril.Children>();
    const user = this.user!;
    const isActor = app.session.user === user;

    items.add(
      'posts',
      <LinkButton href={app.route('user.posts', { username: user.slug() })} icon="far fa-comment">
        {app.translator.trans('core.site.user.posts_activity')} <span className="Button-badge">{user.commentCount()}</span>
      </LinkButton>,
      100
    );

    items.add(
      'discussions',
      <LinkButton href={app.route('user.discussions', { username: user.slug() })} icon="fas fa-bars">
        {app.translator.trans('core.site.user.discussions_link')} <span className="Button-badge">{user.discussionCount()}</span>
      </LinkButton>,
      90
    );

    if (isActor) {
      items.add('separator', <Separator />, -90);
      items.add(
        'settings',
        <LinkButton href={app.route('settings')} icon="fas fa-cog">
          {app.translator.trans('core.site.user.settings_link')}
        </LinkButton>,
        -100
      );
    }

    if (isActor || app.site.attribute<boolean>('canModerateAccessTokens')) {
      if (!isActor) {
        items.add('security-separator', <Separator />, -90);
      }

      items.add(
        'security',
        <LinkButton href={app.route('user.security', { username: user.slug() })} icon="fas fa-shield-alt">
          {app.translator.trans('core.site.user.security_link')}
        </LinkButton>,
        -100
      );
    }

    return items;
  }
}
