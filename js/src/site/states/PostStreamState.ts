import app from '../../site/app';
import { throttle } from 'throttle-debounce';
import anchorScroll from '../../common/utils/anchorScroll';
import type Discussion from '../../common/models/Discussion';
import type Post from '../../common/models/Post';

export default class PostStreamState {
  /**
   * 每页要加载的帖子数量。
   */
  static loadCount = 20;

  /**
   * 显示帖子流的讨论。
   */
  discussion: Discussion;

  /**
   * 是否禁用了无限滚动自动加载功能。
   */
  paused = false;

  // 存储加载页面的超时对象
  loadPageTimeouts: Record<number, NodeJS.Timeout> = {};

  // 当前正在加载的页面数
  pagesLoading = 0;

  // 当前帖子在流中的索引
  index = 0;

  // 当前显示的页面号
  number = 1;

  /**
   * 当前在视口中可见的帖子数量。
   */
  visible = 1;

  // 可见帖子范围的起始索引
  visibleStart = 0;

  // 可见帖子范围的结束索引
  visibleEnd = 0;

  // 是否启用滚动动画
  animateScroll = false;

  // 是否需要滚动
  needsScroll = false;

  // 要滚动到的目标帖子（可以是帖子号或索引）
  targetPost: { number: number } | { index: number; reply?: boolean } | null = null;

  /**
   * 在滚动条上渲染的描述。
   */
  description = '';

  /**
   * 当页面滚动、调用 goToIndex 方法或页面加载时，
   * 各种监听器会导致滚动条更新其位置和值。
   * 但是，如果调用 goToNumber 方法，滚动条不会更新。
   * 因此，我们在滚动条的 onupdate 方法中添加逻辑以在需要时更新自身，
   * 这由本属性指示。
   *
   */
  forceUpdateScrubber = false;

  // 加载帖子的 Promise
  loadPromise: Promise<void> | null = null;

  // 加载下一页的方法
  loadNext: () => void;

  // 加载上一页的方法
  loadPrevious: () => void;

  // 构造函数，接收一个讨论对象和可选的已包含帖子数组
  constructor(discussion: Discussion, includedPosts: Post[] = []) {
    this.discussion = discussion;

    // 使用节流函数包装加载方法，确保在 300 毫秒内只执行一次
    this.loadNext = throttle(300, this._loadNext);
    this.loadPrevious = throttle(300, this._loadPrevious);

    // 显示传入的帖子
    this.show(includedPosts);
  }

  /**
   * 更新帖子流，以便在查看末尾时加载和包括讨论中的最新帖子。
   */
  update() {
    if (!this.viewingEnd()) return Promise.resolve();

    this.visibleEnd = this.count();

    return this.loadRange(this.visibleStart, this.visibleEnd);
  }

  /**
   * 加载并滚动到讨论中的第一个帖子。
   */
  goToFirst(): Promise<void> {
    return this.goToIndex(0);
  }

  /**
   * 加载并滚动到讨论中的最后一个帖子。
   */
  goToLast(): Promise<void> {
    return this.goToIndex(this.count() - 1, true);
  }

  /**
   * 加载并滚动到具有特定编号的帖子。
   *
   * @param number 要跳转到的帖子编号。如果为 'reply', 则跳转到最后一个帖子并将回复预览滚动到视图中。
   */
  goToNumber(number: number | 'reply', noAnimation = false): Promise<void> {
    // 如果我们想要跳转到回复预览，那么我们将跳转到讨论的末尾，然后将页面滚动到最底部。
    if (number === 'reply') {
      const resultPromise = this.goToLast();
      this.targetPost = { ...(this.targetPost as { index: number }), reply: true };
      return resultPromise;
    }

    this.paused = true;

    this.loadPromise = this.loadNearNumber(number);

    this.needsScroll = true;
    this.targetPost = { number };
    this.animateScroll = !noAnimation;
    this.number = number;

    // 在这种情况下，重新绘制仅在响应加载完成后才调用，因为我们需要知道帖子范围的索引，然后才能开始滚动到这些帖子。
    // 提前调用重新绘制会导致问题，由于这仅用于导航到帖子流的外部链接，因此流移动之前的延迟不是问题。
    // 返回在 loadPromise 完成后的重新绘制调用。 
    return this.loadPromise.then(() => m.redraw());
  }

  /**
   * 加载并滚动到讨论中的特定索引。
   */
  goToIndex(index: number, noAnimation = false): Promise<void> {
    this.paused = true;

    this.loadPromise = this.loadNearIndex(index);

    this.needsScroll = true;
    this.targetPost = { index };
    this.animateScroll = !noAnimation;
    this.index = index;

    m.redraw();

    return this.loadPromise;
  }

  /**
   * 清除流并加载特定编号附近的帖子。返回一个 Promise。
   * 如果具有给定编号的帖子已经加载，则 Promise 将立即解析。
   */
  loadNearNumber(number: number): Promise<void> {
    if (this.posts().some((post) => post && Number(post.number()) === Number(number))) {
      return Promise.resolve();
    }

    this.reset();

    return app.store
      .find<Post[]>('posts', {
        filter: { discussion: this.discussion.id() as string },
        page: { near: number },
      })
      .then(this.show.bind(this));
  }

  /**
   * 清除流并加载特定索引附近的帖子。将加载给定索引周围的一页帖子。
   * 返回一个 Promise。如果给定的索引已经加载，则 Promise 将立即解析。
   */
  loadNearIndex(index: number): Promise<void> {
    if (index >= this.visibleStart && index < this.visibleEnd) {
      return Promise.resolve();
    }

    const start = this.sanitizeIndex(index - PostStreamState.loadCount / 2);
    const end = start + PostStreamState.loadCount;

    this.reset(start, end);

    return this.loadRange(start, end).then(this.show.bind(this));
  }

  /**
   * 加载下一页帖子。 
   */
  _loadNext() {
    const start = this.visibleEnd;
    const end = (this.visibleEnd = this.sanitizeIndex(this.visibleEnd + PostStreamState.loadCount));

    // 卸载当前加载页面之前两页的帖子。
    const twoPagesAway = start - PostStreamState.loadCount * 2;
    if (twoPagesAway > this.visibleStart && twoPagesAway >= 0) {
      this.visibleStart = twoPagesAway + PostStreamState.loadCount + 1;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        delete this.loadPageTimeouts[twoPagesAway];
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end);
  }

  /**
   * 加载上一页帖子。 
   */
  _loadPrevious() {
    const end = this.visibleStart;
    const start = (this.visibleStart = this.sanitizeIndex(this.visibleStart - PostStreamState.loadCount));

    // 卸载当前加载页面之前两页的帖子。
    const twoPagesAway = start + PostStreamState.loadCount * 2;
    if (twoPagesAway < this.visibleEnd && twoPagesAway <= this.count()) {
      this.visibleEnd = twoPagesAway;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        delete this.loadPageTimeouts[twoPagesAway];
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end, true);
  }

  /**
   * 将一页帖子加载到流中并重新绘制。
   */
  loadPage(start: number, end: number, backwards = false) {
    this.pagesLoading++;

    const redraw = () => {
      if (start < this.visibleStart || end > this.visibleEnd) return;

      const anchorIndex = backwards ? this.visibleEnd - 1 : this.visibleStart;
      anchorScroll(`.PostStream-item[data-index="${anchorIndex}"]`, m.redraw.sync);
    };
    redraw();

    this.loadPageTimeouts[start] = setTimeout(
      () => {
        this.loadRange(start, end).then(() => {
          redraw();
          this.pagesLoading--;
        });
        delete this.loadPageTimeouts[start];
      },
      this.pagesLoading - 1 ? 1000 : 0
    );
  }

  /**
   * 加载并将指定范围的帖子注入到流中，但不清除它。
   */
  loadRange(start: number, end: number): Promise<Post[]> {
    const loadIds: string[] = [];
    const loaded: Post[] = [];

    this.discussion
      .postIds()
      .slice(start, end)
      .forEach((id) => {
        const post = app.store.getById<Post>('posts', id);

        if (post && post.discussion() && typeof post.canEdit() !== 'undefined') {
          loaded.push(post);
        } else {
          loadIds.push(id);
        }
      });

    if (loadIds.length) {
      return app.store.find<Post[]>('posts', loadIds).then((newPosts) => {
        return loaded.concat(newPosts).sort((a, b) => a.number() - b.number());
      });
    }

    return Promise.resolve(loaded);
  }

  /**
   * 使用给定的帖子数组设置流。
   */
  show(posts: Post[]) {
    this.visibleStart = posts.length ? this.discussion.postIds().indexOf(posts[0].id() ?? '0') : 0;
    this.visibleEnd = this.sanitizeIndex(this.visibleStart + posts.length);
  }

  /**
   * 重置流以显示特定范围的帖子。如果未指定范围，则将显示第一页的帖子。
   */
  reset(start?: number, end?: number) {
    this.visibleStart = start || 0;
    this.visibleEnd = this.sanitizeIndex(end || PostStreamState.loadCount);
  }

  /**
   * 获取可见页面的帖子。
   */
  posts(): (Post | null)[] {
    return this.discussion
      .postIds()
      .slice(this.visibleStart, this.visibleEnd)
      .map((id) => {
        const post = app.store.getById<Post>('posts', id);

        return post && post.discussion() && typeof post.canEdit() !== 'undefined' ? post : null;
      });
  }

  /**
   * 获取讨论中的帖子总数。
   */
  count(): number {
    return this.discussion.postIds().length;
  }

  /**
   * 检查是否应禁用滚动条，即是否所有帖子都已在视口中可见。
   */
  disabled(): boolean {
    return this.visible >= this.count();
  }

  /**
   * 我们当前是否正在查看讨论的末尾？
   */
  viewingEnd(): boolean {
    // I在某些情况下，如果我们置顶了帖子或添加了/删除了事件帖子，
    // 这意味着t `this.visibleEnd` 和 `this.count()` 可能会相差一个帖子，
    // 但我们仍然是在“查看帖子流的末尾，所以我们应该重新加载直到最后一个帖子。
    // 因此，如果两者之间的差值小于或等于1，则返回true
    return Math.abs(this.count() - this.visibleEnd) <= 1;
  }

  /**
   * 确保给定的索引不超出讨论中可能的索引范围。
   */
  sanitizeIndex(index: number) {
    return Math.max(0, Math.min(this.count(), Math.floor(index)));
  }
}
