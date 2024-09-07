import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * `HeaderPrimary` 组件用于显示主要的头部控件。在默认皮肤中，这些控件显示在论坛标题的右侧。
 */
export default class HeaderPrimary extends Component {
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  config(isInitialized, context) {
    // 由于这个组件位于页面内容的上方（即，它是全局UI的一部分，在路由之间保持不变），
    // 我们将标记DOM，以便在路由更改时保留它。
    context.retain = true;
  }

  /**
   * 构建控件的项列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    return new ItemList();
  }
}
