import app from '../../admin/app';
import Component from '../../common/Component';
import LinkButton from '../../common/components/LinkButton';
import SessionDropdown from './SessionDropdown';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * `HeaderSecondary` 组件用于显示次要的头部控件。
 */
export default class HeaderSecondary extends Component {
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  /**
   * 为控件构建一个项目列表。
   *
   * @return {ItemList<import('mithril').Children>} 返回一个ItemList，其中包含mithril库的子元素。
   */
  items() {
    const items = new ItemList();

    items.add(
      'help',
      <LinkButton href="/docs/troubleshooting" icon="fas fa-question-circle" external={true} target="_blank">
        {app.translator.trans('core.admin.header.get_help')}
      </LinkButton>
    );

    items.add('session', <SessionDropdown />);

    return items;
  }
}
