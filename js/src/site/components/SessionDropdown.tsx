import app from '../../site/app';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import LinkButton from '../../common/components/LinkButton';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import Separator from '../../common/components/Separator';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';

export interface ISessionDropdownAttrs extends IDropdownAttrs {}

/**
 * `SessionDropdown` 组件显示一个按钮，其中包含当前用户的头像/姓名，以及一个会话控制的下拉菜单。
 */
export default class SessionDropdown<CustomAttrs extends ISessionDropdownAttrs = ISessionDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: ISessionDropdownAttrs) {
    super.initAttrs(attrs);

    attrs.className = 'SessionDropdown';
    attrs.buttonClassName = 'Button Button--user Button--flat';
    attrs.menuClassName = 'Dropdown-menu--right';

    attrs.accessibleToggleLabel = extractText(app.translator.trans('core.site.header.session_dropdown_accessible_label'));
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    return super.view({ ...vnode, children: this.items().toArray() });
  }

  getButtonContent() {
    const user = app.session.user;

    return [avatar(user), ' ', <span className="Button-label">{username(user)}</span>];
  }

  /**
   * 为下拉菜单的内容构建项目列表。
   */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const user = app.session.user!;

    items.add(
      'profile',
      <LinkButton icon="fas fa-user" href={app.route.user(user)}>
        {app.translator.trans('core.site.header.profile_button')}
      </LinkButton>,
      100
    );

    items.add(
      'settings',
      <LinkButton icon="fas fa-cog" href={app.route('settings')}>
        {app.translator.trans('core.site.header.settings_button')}
      </LinkButton>,
      50
    );

    if (app.site.attribute('adminUrl')) {
      items.add(
        'administration',
        <LinkButton icon="fas fa-wrench" href={app.site.attribute('adminUrl')} target="_blank">
          {app.translator.trans('core.site.header.admin_button')}
        </LinkButton>,
        0
      );
    }

    items.add('separator', <Separator />, -90);

    items.add(
      'logOut',
      <Button icon="fas fa-sign-out-alt" onclick={app.session.logout.bind(app.session)}>
        {app.translator.trans('core.site.header.log_out_button')}
      </Button>,
      -100
    );

    return items;
  }
}
