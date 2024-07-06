import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';

/**
 * `LogInButtons` 组件用于显示一系列社交登录按钮。
 */
export default class LogInButtons extends Component {
  view() {
    return <div className="LogInButtons">{this.items().toArray()}</div>;
  }

  /**
   * 构建 LogInButton 组件的列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    return new ItemList();
  }
}
