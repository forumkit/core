import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * `HeaderPrimary` 组件用于显示主要的页眉控件。在默认的皮肤下，
 * 这些控件会显示在站点标题的右侧。
 */
export default class HeaderPrimary extends Component {
  // 渲染视图
  view() {
    // 返回一个带有 `HeaderPrimary` 类名的无序列表，列表项由this.items().toArray()生成
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  // 配置组件
  config(isInitialized, context) {
    // 由于此组件位于页面内容的上方（即，它是全局UI的一部分，在不同路由之间持续存在），
    // 我们将标记DOM以在路由更改时保留。
    context.retain = true;
  }

  /**
   * 为控件构建项目列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    // 创建一个新的ItemList实例
    return new ItemList();
  }
}
