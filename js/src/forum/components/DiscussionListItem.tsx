import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import Link from '../../common/components/Link';
import avatar from '../../common/helpers/avatar';
import listItems from '../../common/helpers/listItems';
import highlight from '../../common/helpers/highlight';
import icon from '../../common/helpers/icon';
import humanTime from '../../common/utils/humanTime';
import ItemList from '../../common/utils/ItemList';
import abbreviateNumber from '../../common/utils/abbreviateNumber';
import Dropdown from '../../common/components/Dropdown';
import TerminalPost from './TerminalPost';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import DiscussionControls from '../utils/DiscussionControls';
import slidable from '../utils/slidable';
import classList from '../../common/utils/classList';
import DiscussionPage from './DiscussionPage';
import escapeRegExp from '../../common/utils/escapeRegExp';
import Tooltip from '../../common/components/Tooltip';
import type Discussion from '../../common/models/Discussion';
import type Mithril from 'mithril';
import type { DiscussionListParams } from '../states/DiscussionListState';

export interface IDiscussionListItemAttrs extends ComponentAttrs {
  discussion: Discussion;
  params: DiscussionListParams;
}

/**
 * `DiscussionListItem` 组件用于在讨论列表中显示单个讨论。
 */
export default class DiscussionListItem<CustomAttrs extends IDiscussionListItemAttrs = IDiscussionListItemAttrs> extends Component<CustomAttrs> {
  /**
   * 确保除非有新数据传入，否则不会重新绘制讨论。
   * 这里使用 subtree 保留器来优化性能，避免不必要的渲染。
   */
  subtree!: SubtreeRetainer;

  highlightRegExp?: RegExp;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.subtree = new SubtreeRetainer(
      () => this.attrs.discussion.freshness,
      () => {
        const time = app.session.user && app.session.user.markedAllAsReadAt();
        return time && time.getTime();
      },
      () => this.active()
    );
  }

  elementAttrs() {
    return {
      className: classList('DiscussionListItem', {
        active: this.active(),
        'DiscussionListItem--hidden': this.attrs.discussion.isHidden(),
        Slidable: 'ontouchstart' in window,
      }),
    };
  }

  view() {
    const discussion = this.attrs.discussion;

    const controls = DiscussionControls.controls(discussion, this).toArray();
    const attrs = this.elementAttrs();

    return (
      <div {...attrs}>
        {this.controlsView(controls)}
        {this.slidableUnderneathView()}
        {this.contentView()}
      </div>
    );
  }

  controlsView(controls: Mithril.ChildArray): Mithril.Children {
    return (
      !!controls.length && (
        <Dropdown
          icon="fas fa-ellipsis-v"
          className="DiscussionListItem-controls"
          buttonClassName="Button Button--icon Button--flat Slidable-underneath Slidable-underneath--right"
          accessibleToggleLabel={app.translator.trans('core.forum.discussion_controls.toggle_dropdown_accessible_label')}
        >
          {controls}
        </Dropdown>
      )
    );
  }

  slidableUnderneathView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const isUnread = discussion.isUnread();

    return (
      <span
        className={classList('Slidable-underneath Slidable-underneath--left Slidable-underneath--elastic', { disabled: !isUnread })}
        onclick={this.markAsRead.bind(this)}
      >
        {icon('fas fa-check')}
      </span>
    );
  }

  contentView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const isUnread = discussion.isUnread();
    const isRead = discussion.isRead();

    return (
      <div className={classList('DiscussionListItem-content', 'Slidable-content', { unread: isUnread, read: isRead })}>
        {this.authorAvatarView()}
        {this.badgesView()}
        {this.mainView()}
        {this.replyCountItem()}
      </div>
    );
  }

  authorAvatarView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const user = discussion.user();

    return (
      <Tooltip
        text={app.translator.trans('core.forum.discussion_list.posted_text', { user, ago: humanTime(discussion.createdAt()) })}
        position="right"
      >
        <Link className="DiscussionListItem-author" href={user ? app.route.user(user) : '#'}>
          {avatar(user || null, { title: '' })}
        </Link>
      </Tooltip>
    );
  }

  badgesView(): Mithril.Children {
    const discussion = this.attrs.discussion;

    return <ul className="DiscussionListItem-badges badges">{listItems(discussion.badges().toArray())}</ul>;
  }

  mainView(): Mithril.Children {
    const discussion = this.attrs.discussion;
    const jumpTo = this.getJumpTo();

    return (
      <Link href={app.route.discussion(discussion, jumpTo)} className="DiscussionListItem-main">
        <h2 className="DiscussionListItem-title">{highlight(discussion.title(), this.highlightRegExp)}</h2>
        <ul className="DiscussionListItem-info">{listItems(this.infoItems().toArray())}</ul>
      </Link>
    );
  }

  getJumpTo() {
    const discussion = this.attrs.discussion;
    let jumpTo = 0;

    if (this.attrs.params.q) {
      const post = discussion.mostRelevantPost();
      if (post) {
        jumpTo = post.number();
      }

      const phrase = escapeRegExp(this.attrs.params.q);
      this.highlightRegExp = new RegExp(phrase + '|' + phrase.trim().replace(/\s+/g, '|'), 'gi');
    } else {
      jumpTo = Math.min(discussion.lastPostNumber() ?? 0, (discussion.lastReadPostNumber() || 0) + 1);
    }

    return jumpTo;
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // 如果我们在触摸设备上，设置讨论行可以滑动。
    // 这允许用户将行拖到屏幕的任一侧以显示控件。
    if ('ontouchstart' in window) {
      const slidableInstance = slidable(this.element);

      this.$('.DiscussionListItem-controls').on('hidden.bs.dropdown', () => slidableInstance.reset());
    }
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeupdate(vnode);

    return this.subtree.needsRebuild();
  }

  /**
   * 确定当前是否正在查看讨论。
   */
  active() {
    return app.current.matches(DiscussionPage, { discussion: this.attrs.discussion });
  }

  /**
   * 确定是否应该显示关于谁开始讨论的信息，
   * 而不是关于对讨论的最新回复的信息。
   */
  showFirstPost() {
    return ['newest', 'oldest'].includes(this.attrs.params.sort ?? '');
  }

  /**
   * 确定是否应该显示回复的数量而不是未读帖子的数量。
   *
   * @return {boolean}
   */
  showRepliesCount() {
    return this.attrs.params.sort === 'replies';
  }

  /**
   * 将讨论标记为已读。
   */
  markAsRead() {
    const discussion = this.attrs.discussion;

    if (discussion.isUnread()) {
      discussion.save({ lastReadPostNumber: discussion.lastPostNumber() });
      m.redraw();
    }
  }

  /**
   * 构建讨论列表的信息项列表。默认情况下，这仅是第一个/最后一个帖子的指示器。
   */
  infoItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.attrs.params.q) {
      const post = this.attrs.discussion.mostRelevantPost() || this.attrs.discussion.firstPost();

      if (post && post.contentType() === 'comment') {
        const excerpt = highlight(post.contentPlain() ?? '', this.highlightRegExp, 175);
        items.add('excerpt', excerpt, -100);
      }
    } else {
      items.add('terminalPost', <TerminalPost discussion={this.attrs.discussion} lastPost={!this.showFirstPost()} />);
    }

    return items;
  }

  replyCountItem() {
    const discussion = this.attrs.discussion;
    const showUnread = !this.showRepliesCount() && discussion.isUnread();

    if (showUnread) {
      return (
        <button className="Button--ua-reset DiscussionListItem-count" onclick={this.markAsRead.bind(this)}>
          <span aria-hidden="true">{abbreviateNumber(discussion.unreadCount())}</span>

          <span className="visually-hidden">
            {app.translator.trans('core.forum.discussion_list.unread_replies_a11y_label', { count: discussion.replyCount() })}
          </span>
        </button>
      );
    }

    return (
      <span className="DiscussionListItem-count">
        <span aria-hidden="true">{abbreviateNumber(discussion.replyCount())}</span>

        <span className="visually-hidden">
          {app.translator.trans('core.forum.discussion_list.total_replies_a11y_label', { count: discussion.replyCount() })}
        </span>
      </span>
    );
  }
}
