import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * `HeaderPrimary` 组件用于显示主要头部控件。在默认皮肤中，这些控件显示在论坛标题的右侧。
 */
export default class HeaderPrimary extends Component {
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  /**
   * 创建一个控件的项列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    return new ItemList();
  }
}
