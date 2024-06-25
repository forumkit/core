import type Mithril from 'mithril';

import app from '../../site/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import DiscussionHero from './DiscussionHero';
import DiscussionListPane from './DiscussionListPane';
import PostStream from './PostStream';
import PostStreamScrubber from './PostStreamScrubber';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import SplitDropdown from '../../common/components/SplitDropdown';
import listItems from '../../common/helpers/listItems';
import DiscussionControls from '../utils/DiscussionControls';
import PostStreamState from '../states/PostStreamState';
import Discussion from '../../common/models/Discussion';
import Post from '../../common/models/Post';
import { ApiResponseSingle } from '../../common/Store';

export interface IDiscussionPageAttrs extends IPageAttrs {
  id: string;
  near?: number;
}

/**
 * `DiscussionPage` 组件用于显示整个讨论页面，包括讨论列表面板、英雄元素、帖子和侧边栏。
 */
export default class DiscussionPage<CustomAttrs extends IDiscussionPageAttrs = IDiscussionPageAttrs> extends Page<CustomAttrs> {
  /**
   * 当前正在查看的讨论。
   */
  protected discussion: Discussion | null = null;

  /**
   * 与帖子流交互的公共API。
   */
  protected stream: PostStreamState | null = null;

  /**
   * 当前在视口中可见的第一个帖子的编号。
   */
  protected near: number = 0;

  protected useBrowserScrollRestoration = true;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.load();

    // 如果讨论列表已加载，则启用面板（并默认隐藏它）。此外，如果我们刚从另一个讨论页面跳转过来，
    // 我们不希望 Mithril 重新绘制整个页面，因为如果这样做，面板也会重新绘制，这将是缓慢的并且会导致事件处理器问题。
    // 如果讨论列表为空但正在加载，我们也会启用面板，因为 DiscussionComposer 会刷新列表并同时重定向到新讨论。
    if (app.discussions.hasItems() || app.discussions.isLoading()) {
      app.pane?.enable();
      app.pane?.hide();
    }

    this.bodyClass = 'App--discussion';
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    // 如果我们确实正在离开这个讨论，那么禁用讨论列表面板。此外，如果我们正在为这个讨论撰写回复，
    // 并且内容为空，则隐藏 Composer，否则最小化它。
    app.pane?.disable();

    if (this.discussion && app.composer.composingReplyTo(this.discussion) && !app.composer?.fields?.content()) {
      app.composer.hide();
    } else {
      app.composer.minimize();
    }
  }

  view() {
    return (
      <div className="DiscussionPage">
        <DiscussionListPane state={app.discussions} />
        <div className="DiscussionPage-discussion">{this.discussion ? this.pageContent().toArray() : this.loadingItems().toArray()}</div>
      </div>
    );
  }

  /**
   * 当讨论正在加载时显示的组件列表。
   */
  loadingItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('spinner', <LoadingIndicator />, 100);

    return items;
  }

  /**
   * 渲染 `sidebarItems` ItemList 的函数。
   */
  sidebar(): Mithril.Children {
    return (
      <nav className="DiscussionPage-nav">
        <ul>{listItems(this.sidebarItems().toArray())}</ul>
      </nav>
    );
  }

  /**
   * 渲染讨论的英雄元素。
   */
  hero(): Mithril.Children {
    return <DiscussionHero discussion={this.discussion} />;
  }

  /**
   * 渲染作为主要页面内容的项目列表。
   */
  pageContent(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('hero', this.hero(), 100);
    items.add('main', <div className="container">{this.mainContent().toArray()}</div>, 10);

    return items;
  }

  /**
   * 在主要页面内容容器内渲染的项目列表。
   */
  mainContent(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('sidebar', this.sidebar(), 100);

    items.add(
      'poststream',
      <div className="DiscussionPage-stream">
        <PostStream discussion={this.discussion} stream={this.stream} onPositionChange={this.positionChanged.bind(this)} />
      </div>,
      10
    );

    return items;
  }

  /**
   * 从API加载讨论或使用预加载的讨论。
   */
  load(): void {
    const preloadedDiscussion = app.preloadedApiDocument<Discussion>();
    if (preloadedDiscussion) {
      //我们必须将其包装在setTimeout中，因为如果我们在页面加载时首次挂载此组件，
      // 那么任何对m.redraw的调用都会无效，因此任何配置（滚动代码）都会在内容绘制到页面之前运行。
      setTimeout(this.show.bind(this, preloadedDiscussion), 0);
    } else {
      const params = this.requestParams();

      app.store.find<Discussion>('discussions', m.route.param('id'), params).then(this.show.bind(this));
    }

    m.redraw();
  }

  /**
   * 获取获取讨论时应在API请求中传递的参数。
   */
  requestParams(): Record<string, unknown> {
    return {
      bySlug: true,
      page: { near: this.near },
    };
  }

  /**
   * 初始化组件以显示给定的讨论。
   */
  show(discussion: ApiResponseSingle<Discussion>): void {
    app.history.push('discussion', discussion.title());
    app.setTitle(discussion.title());
    app.setTitleCount(0);

    // 当API响应讨论时，它还会包含一些帖子。其中一些帖子被包含在内是因为它们是我们想显示的第一页帖子（由'near'参数确定）
    // 其他帖子可能由于扩展引入的其他关系而被包含。我们需要区分这两者，以便不会最终显示错误的帖子。 // 我们通过过滤出不具有'discussion'关系链接的帖子，然后进行排序和切片来实现这一点。
    let includedPosts: Post[] = [];
    if (discussion.payload && discussion.payload.included) {
      const discussionId = discussion.id();

      includedPosts = discussion.payload.included
        .filter(
          (record) =>
            record.type === 'posts' &&
            record.relationships &&
            record.relationships.discussion &&
            !Array.isArray(record.relationships.discussion.data) &&
            record.relationships.discussion.data.id === discussionId
        )
        // 我们可以做出这个断言，因为帖子应该在存储中，因为它们位于讨论的payload中。
        .map((record) => app.store.getById<Post>('posts', record.id) as Post)
        .sort((a: Post, b: Post) => a.number() - b.number())
        .slice(0, 20);
    }

    // 为此讨论设置帖子流，以及我们想要显示的第一页帖子。告诉流向下滚动并高亮显示路由到的特定帖子。
    this.stream = new PostStreamState(discussion, includedPosts);
    const rawNearParam = m.route.param('near');
    const nearParam = rawNearParam === 'reply' ? 'reply' : parseInt(rawNearParam);
    this.stream.goToNumber(nearParam || (includedPosts[0]?.number() ?? 0), true).then(() => {
      this.discussion = discussion;

      app.current.set('discussion', discussion);
      app.current.set('stream', this.stream);
    });
  }

  /**
   * 构建侧边栏内容的项目列表。
   */
  sidebarItems() {
    const items = new ItemList<Mithril.Children>();

    if (this.discussion) {
      items.add(
        'controls',
        <SplitDropdown
          icon="fas fa-ellipsis-v"
          className="App-primaryControl"
          buttonClassName="Button--primary"
          accessibleToggleLabel={app.translator.trans('core.site.discussion_controls.toggle_dropdown_accessible_label')}
        >
          {DiscussionControls.controls(this.discussion, this).toArray()}
        </SplitDropdown>,
        100
      );
    }

    items.add('scrubber', <PostStreamScrubber stream={this.stream} className="App-titleControl" />, -100);

    return items;
  }

  /**
   * 当帖子流中可见的帖子发生变化时（即用户向上或向下滚动），我们更新URL并将帖子标记为已读。
   */
  positionChanged(startNumber: number, endNumber: number): void {
    const discussion = this.discussion;

    if (!discussion) return;

    // 构造一个带有更新位置的讨论URL，然后将其替换到窗口的历史记录和我们自己的历史堆栈中。
    const url = app.route.discussion(discussion, (this.near = startNumber));

    window.history.replaceState(null, document.title, url);
    app.history.push('discussion', discussion.title());

    // 如果用户之前没有读过这里，则更新他们的阅读状态并重新绘制界面。
    if (app.session.user && endNumber > (discussion.lastReadPostNumber() || 0)) {
      discussion.save({ lastReadPostNumber: endNumber });
      m.redraw();
    }
  }
}
