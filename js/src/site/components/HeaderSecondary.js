import app from '../../site/app';
import Component from '../../common/Component';
import Button from '../../common/components/Button';
import LogInModal from './LogInModal';
import SignUpModal from './SignUpModal';
import SessionDropdown from './SessionDropdown';
import SelectDropdown from '../../common/components/SelectDropdown';
import NotificationsDropdown from './NotificationsDropdown';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import Search from '../components/Search';

/**
 * `HeaderSecondary` 组件用于显示二级头部控件，如搜索框和用户菜单。在默认皮肤中，这些控件会显示在头部的右侧。
 */
export default class HeaderSecondary extends Component {
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  /**
   * 为控件构建项目列表。
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add('search', <Search state={app.search} />, 30);

    if (app.site.attribute('showLanguageSelector') && Object.keys(app.data.locales).length > 1) {
      const locales = [];

      for (const locale in app.data.locales) {
        locales.push(
          <Button
            active={app.data.locale === locale}
            icon={app.data.locale === locale ? 'fas fa-check' : true}
            onclick={() => {
              if (app.session.user) {
                app.session.user.savePreferences({ locale }).then(() => window.location.reload());
              } else {
                document.cookie = `locale=${locale}; path=/; expires=Tue, 19 Jan 2038 03:14:07 GMT`;
                window.location.reload();
              }
            }}
          >
            {app.data.locales[locale]}
          </Button>
        );
      }

      items.add(
        'locale',
        <SelectDropdown
          buttonClassName="Button Button--link"
          accessibleToggleLabel={app.translator.trans('core.site.header.locale_dropdown_accessible_label')}
        >
          {locales}
        </SelectDropdown>,
        20
      );
    }

    if (app.session.user) {
      items.add('notifications', <NotificationsDropdown state={app.notifications} />, 10);
      items.add('session', <SessionDropdown />, 0);
    } else {
      if (app.site.attribute('allowSignUp')) {
        items.add(
          'signUp',
          <Button className="Button Button--link" onclick={() => app.modal.show(SignUpModal)}>
            {app.translator.trans('core.site.header.sign_up_link')}
          </Button>,
          10
        );
      }

      items.add(
        'logIn',
        <Button className="Button Button--link" onclick={() => app.modal.show(LogInModal)}>
          {app.translator.trans('core.site.header.log_in_link')}
        </Button>,
        0
      );
    }

    return items;
  }
}
