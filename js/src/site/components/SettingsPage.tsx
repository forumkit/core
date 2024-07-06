import app from '../../site/app';
import UserPage, { IUserPageAttrs } from './UserPage';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Button from '../../common/components/Button';
import FieldSet from '../../common/components/FieldSet';
import NotificationGrid from './NotificationGrid';
import ChangePasswordModal from './ChangePasswordModal';
import ChangeEmailModal from './ChangeEmailModal';
import listItems from '../../common/helpers/listItems';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';

/**
 * `SettingsPage` 组件在用户个人资料的上下文中显示用户的设置控制面板。
 */
export default class SettingsPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs> {
  discloseOnlineLoading?: boolean;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.show(app.session.user!);

    app.setTitle(extractText(app.translator.trans('core.site.settings.title')));
  }

  content() {
    return (
      <div className="SettingsPage">
        <ul>{listItems(this.settingsItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * 为用户的设置控件生成项列表。
   */
  settingsItems() {
    const items = new ItemList<Mithril.Children>();

    ['account', 'notifications', 'privacy'].forEach((section, index) => {
      const sectionItems = `${section}Items` as 'accountItems' | 'notificationsItems' | 'privacyItems';

      items.add(
        section,
        <FieldSet className={`Settings-${section}`} label={app.translator.trans(`core.site.settings.${section}_heading`)}>
          {this[sectionItems]().toArray()}
        </FieldSet>,
        100 - index * 10
      );
    });

    return items;
  }

  /**
   * 为用户的帐户设置生成项目列表。
   */
  accountItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'changePassword',
      <Button className="Button" onclick={() => app.modal.show(ChangePasswordModal)}>
        {app.translator.trans('core.site.settings.change_password_button')}
      </Button>,
      100
    );

    items.add(
      'changeEmail',
      <Button className="Button" onclick={() => app.modal.show(ChangeEmailModal)}>
        {app.translator.trans('core.site.settings.change_email_button')}
      </Button>,
      90
    );

    return items;
  }

  /**
   * 为用户的通知设置生成项目列表。
   */
  notificationsItems() {
    const items = new ItemList<Mithril.Children>();

    items.add('notificationGrid', <NotificationGrid user={this.user} />, 100);

    return items;
  }

  /**
   * 为用户的隐私设置构建项目列表。
   */
  privacyItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'discloseOnline',
      <Switch
        state={this.user!.preferences()?.discloseOnline}
        onchange={(value: boolean) => {
          this.discloseOnlineLoading = true;

          this.user!.savePreferences({ discloseOnline: value }).then(() => {
            this.discloseOnlineLoading = false;
            m.redraw();
          });
        }}
        loading={this.discloseOnlineLoading}
      >
        {app.translator.trans('core.site.settings.privacy_disclose_online_label')}
      </Switch>,
      100
    );

    return items;
  }
}
