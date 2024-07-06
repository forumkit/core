import app from '../../admin/app';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { Children } from 'mithril';

export default class WidgetPage extends AdminPage {
  headerInfo() {
    return {
      className: 'WidgetPage',
      icon: 'fas fa-file-alt',
      title: app.translator.trans('core.admin.widget.title'),
      description: app.translator.trans('core.admin.widget.description'),
    };
  }

  content() {
    return this.availableWidgetPages().toArray();
  }

  availableWidgetPages(): ItemList<Children> {
    const items = new ItemList<Children>();

    return items;
  }
}
