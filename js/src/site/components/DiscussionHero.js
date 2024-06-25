import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * `DiscussionHero` 组件用于在讨论页面上显示标题区域。
 *
 * ### 属性 attrs
 *
 * - `discussion`
 */
export default class DiscussionHero extends Component {
  view() {
    return (
      <header className="Hero DiscussionHero">
        <div className="container">
          <ul className="DiscussionHero-items">{listItems(this.items().toArray())}</ul>
        </div>
      </header>
    );
  }

  /**
   * 为讨论标题区域的内容构建项目列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    const items = new ItemList();
    const discussion = this.attrs.discussion;
    const badges = discussion.badges().toArray();

    if (badges.length) {
      items.add('badges', <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>, 10);
    }

    items.add('title', <h1 className="DiscussionHero-title">{discussion.title()}</h1>);

    return items;
  }
}
