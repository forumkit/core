import app from '../../admin/app';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { Children } from 'mithril';

export default class CustomPage extends AdminPage {
  headerInfo() {
    return {
      className: 'CustomPage',
      icon: 'far fa-file',
      title: app.translator.trans('core.admin.page.title'),
      description: app.translator.trans('core.admin.page.description'),
    };
  }

  content() {
    return this.availableCustomPages().toArray();
  }

  availableCustomPages(): ItemList<Children> {
    const items = new ItemList<Children>();

    return items;
  }
}
