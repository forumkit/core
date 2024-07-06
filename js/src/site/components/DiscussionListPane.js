import app from '../../site/app';
import DiscussionList from './DiscussionList';
import Component from '../../common/Component';
import DiscussionPage from './DiscussionPage';

const hotEdge = (e) => {
  if (e.pageX < 10) app.pane.show();
};

/**
 * `DiscussionListPane` 组件在面板中显示之前查看过的讨论列表，该面板可以通过将鼠标移动到屏幕的左边缘来显示，
 * 同时也可以将其固定在原地。
 *
 * ### 属性（Attrs）
 *
 * - `state` 表示讨论列表状态的 DiscussionListState 对象。
 */
export default class DiscussionListPane extends Component {
  view() {
    if (!this.attrs.state.hasItems()) {
      return;
    }

    return <aside className="DiscussionPage-list">{this.enoughSpace() && <DiscussionList state={this.attrs.state} />}</aside>;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    const $list = $(vnode.dom);

    // 当鼠标进入和离开讨论面板时，我们希望分别显示和隐藏面板。
    // 我们还在屏幕的左侧创建了一个 10px 的“热边缘”来激活面板。
    const pane = app.pane;
    $list.hover(pane.show.bind(pane), pane.onmouseleave.bind(pane));

    $(document).on('mousemove', hotEdge);

    // 当从另一个讨论页面跳转过来时，滚动到之前的位置，以防止讨论列表乱跳。
    if (app.previous.matches(DiscussionPage)) {
      const top = app.cache.discussionListPaneScrollTop || 0;
      $list.scrollTop(top);
    } else {
      // 如果我们正在查看的讨论列在讨论列表中，
      // 那么我们将确保它在视口中可见——如果不在，我们将向下滚动列表以显示它。
      const $discussion = $list.find('.DiscussionListItem.active');
      if ($discussion.length) {
        const listTop = $list.offset().top;
        const listBottom = listTop + $list.outerHeight();
        const discussionTop = $discussion.offset().top;
        const discussionBottom = discussionTop + $discussion.outerHeight();

        if (discussionTop < listTop || discussionBottom > listBottom) {
          $list.scrollTop($list.scrollTop() - listTop + discussionTop);
        }
      }
    }
  }

  onremove(vnode) {
    app.cache.discussionListPaneScrollTop = $(vnode.dom).scrollTop();
    $(document).off('mousemove', hotEdge);
  }

  /**
   * 我们是否在使用大于“移动设备”的设备？
   *
   * @returns {boolean}
   */
  enoughSpace() {
    return !$('.App-navigation').is(':visible');
  }
}
