import app from '../../site/app';
import Component from '../../common/Component';
import ScrollListener from '../../common/utils/ScrollListener';
import PostLoading from './LoadingPost';
import ReplyPlaceholder from './ReplyPlaceholder';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';

/**
 * `PostStream` 组件用于在一个讨论中显示一个无限滚动的帖子墙。尚未加载的帖子将显示为占位符。
 *
 * ### 属性 Attrs
 *
 * - `discussion`
 * - `stream`
 * - `targetPost`
 * - `onPositionChange`
 */
export default class PostStream extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.discussion = this.attrs.discussion;
    this.stream = this.attrs.stream;

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));
  }

  view() {
    let lastTime;

    const viewingEnd = this.stream.viewingEnd();
    const posts = this.stream.posts();
    const postIds = this.discussion.postIds();

    const postFadeIn = (vnode) => {
      $(vnode.dom).addClass('fadeIn');
      // 500 毫秒是 fadeIn CSS 动画的持续时间 + 100 毫秒，确保动画有足够的时间完成
      setTimeout(() => $(vnode.dom).removeClass('fadeIn'), 500);
    };

    const items = posts.map((post, i) => {
      let content;
      const attrs = { 'data-index': this.stream.visibleStart + i };

      if (post) {
        const time = post.createdAt();
        const PostComponent = app.postComponents[post.contentType()];
        content = !!PostComponent && <PostComponent post={post} />;

        attrs.key = 'post' + post.id();
        attrs.oncreate = postFadeIn;
        attrs['data-time'] = time.toISOString();
        attrs['data-number'] = post.number();
        attrs['data-id'] = post.id();
        attrs['data-type'] = post.contentType();

        // 如果此帖子之前的帖子是 4 天前的，我们将会显示一个“时间间隔”，以指示帖子之间的时间长度。
        const dt = time - lastTime;

        if (dt > 1000 * 60 * 60 * 24 * 4) {
          content = [
            <div className="PostStream-timeGap">
              <span>{app.translator.trans('core.site.post_stream.time_lapsed_text', { period: dayjs().add(dt, 'ms').fromNow(true) })}</span>
            </div>,
            content,
          ];
        }

        lastTime = time;
      } else {
        attrs.key = 'post' + postIds[this.stream.visibleStart + i];

        content = <PostLoading />;
      }

      return (
        <div className="PostStream-item" {...attrs}>
          {content}
        </div>
      );
    });

    if (!viewingEnd && posts[this.stream.visibleEnd - this.stream.visibleStart - 1]) {
      items.push(
        <div className="PostStream-loadMore" key="loadMore">
          <Button className="Button" onclick={this.stream.loadNext.bind(this.stream)}>
            {app.translator.trans('core.site.post_stream.load_more_button')}
          </Button>
        </div>
      );
    }

    // 允许扩展在帖子流的末尾添加项目。
    if (viewingEnd) {
      items.push(...this.endItems().toArray());
    }

    // 如果我们正在查看讨论的末尾，用户可以回复，并且尚未进行回复，则显示一个“编写回复”的占位符。
    if (viewingEnd && (!app.session.user || this.discussion.canReply())) {
      items.push(
        <div className="PostStream-item" key="reply" data-index={this.stream.count()} oncreate={postFadeIn}>
          <ReplyPlaceholder discussion={this.discussion} />
        </div>
      );
    }

    return (
      <div className="PostStream" role="feed" aria-live="off" aria-busy={this.stream.pagesLoading ? 'true' : 'false'}>
        {items}
      </div>
    );
  }

  /**
   * @returns {ItemList<import('mithril').Children>}
   */
  endItems() {
    const items = new ItemList();

    return items;
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    this.triggerScroll();
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.triggerScroll();

    // 这段代码被包裹在 中是因为 Mithril 的以下问题：
    // https://github.com/lhorie/mithril.js/issues/637
    setTimeout(() => this.scrollListener.start());
  }

  onremove(vnode) {
    super.onremove(vnode);

    this.scrollListener.stop();
    clearTimeout(this.calculatePositionTimeout);
  }

  /**
   * 如果合适，开始滚动到新的目标帖子。
   */
  triggerScroll() {
    if (!this.stream.needsScroll) return;

    const target = this.stream.targetPost;
    this.stream.needsScroll = false;

    if ('number' in target) {
      this.scrollToNumber(target.number, this.stream.animateScroll);
    } else if ('index' in target) {
      this.scrollToIndex(target.index, this.stream.animateScroll, target.reply);
    }
  }

  /**
   *
   * @param {number} top
   */
  onscroll(top = window.pageYOffset) {
    if (this.stream.paused || this.stream.pagesLoading) return;

    this.updateScrubber(top);

    this.loadPostsIfNeeded(top);

    // 将我们的位置（视口中帖子的开始/结束编号）的计算节流到100毫秒。
    clearTimeout(this.calculatePositionTimeout);
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this, top), 100);
  }

  /**
   * 检查帖子流的任一极端是否在视口中，
   * 如果是，则触发加载下一页/上一页。
   *
   * @param {number} top
   */
  loadPostsIfNeeded(top = window.pageYOffset) {
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;
    const loadAheadDistance = 300;

    if (this.stream.visibleStart > 0) {
      const $item = this.$('.PostStream-item[data-index=' + this.stream.visibleStart + ']');

      if ($item.length && $item.offset().top > viewportTop - loadAheadDistance) {
        this.stream.loadPrevious();
      }
    }

    if (this.stream.visibleEnd < this.stream.count()) {
      const $item = this.$('.PostStream-item[data-index=' + (this.stream.visibleEnd - 1) + ']');

      if ($item.length && $item.offset().top + $item.outerHeight(true) < viewportTop + viewportHeight + loadAheadDistance) {
        this.stream.loadNext();
      }
    }
  }

  updateScrubber(top = window.pageYOffset) {
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;

    // 在遍历所有帖子之前，我们将滚动条属性重置为“默认”状态。这些值反映了如果浏览器滚动到页面顶部，并且视口高度为0时将会看到的情况。
    const $items = this.$('.PostStream-item[data-index]');
    let visible = 0;
    let period = '';
    let indexFromViewPort = null;

    // 现在遍历讨论中的每个项目。一个“项目”要么是一个单独的帖子，要么是一个或多个尚未加载的帖子的“间隙”。
    $items.each(function () {
      const $this = $(this);
      const top = $this.offset().top;
      const height = $this.outerHeight(true);

      // 如果这个项目在视口上方，则跳过它。如果它在视口下方，则退出循环。
      if (top + height < viewportTop) {
        return true;
      }
      if (top > viewportTop + viewportHeight) {
        return false;
      }

      // 计算这个项目在视口中可见的部分有多少像素。然后将这个项目总高度的比例添加到索引中。
      const visibleTop = Math.max(0, viewportTop - top);
      const visibleBottom = Math.min(height, viewportTop + viewportHeight - top);
      const visiblePost = visibleBottom - visibleTop;

      // 我们取通过前面检查的第一个项目的索引。它是视口中首先可见的项目。
      if (indexFromViewPort === null) {
        indexFromViewPort = parseFloat($this.data('index')) + visibleTop / height;
      }

      if (visiblePost > 0) {
        visible += visiblePost / height;
      }

      // 如果这个项目有时间与之关联，则将滚动条的当前时间段设置为该时间的格式化版本。
      const time = $this.data('time');
      if (time) period = time;
    });

    // 如果 indexFromViewPort 为 null，则意味着视口中没有可见的帖子。这种情况在撰写长回复帖子时可能会发生。在那种情况下，将索引设置为最后一个帖子。
    this.stream.index = indexFromViewPort !== null ? indexFromViewPort + 1 : this.stream.count();
    this.stream.visible = visible;
    if (period) this.stream.description = dayjs(period).format('MMMM YYYY');
  }

  /**
   * 计算当前在视口中可见的帖子（按编号），并触发一个带有这些信息的事件。
   */
  calculatePosition(top = window.pageYOffset) {
    const marginTop = this.getMarginTop();
    const $window = $(window);
    const viewportHeight = $window.height() - marginTop;
    const scrollTop = $window.scrollTop() + marginTop;
    const viewportTop = top + marginTop;

    let startNumber;
    let endNumber;

    this.$('.PostStream-item').each(function () {
      const $item = $(this);
      const top = $item.offset().top;
      const height = $item.outerHeight(true);
      const visibleTop = Math.max(0, viewportTop - top);

      const threeQuartersVisible = visibleTop / height < 0.75;
      const coversQuarterOfViewport = (height - visibleTop) / viewportHeight > 0.25;
      if (startNumber === undefined && (threeQuartersVisible || coversQuarterOfViewport)) {
        startNumber = $item.data('number');
      }

      if (top + height > scrollTop) {
        if (top + height < scrollTop + viewportHeight) {
          if ($item.data('number')) {
            endNumber = $item.data('number');
          }
        } else return false;
      }
    });

    if (startNumber) {
      this.attrs.onPositionChange(startNumber || 1, endNumber, startNumber);
    }
  }

  /**
   * 获取从视口顶部到我们认为帖子为第一个可见帖子的点的距离。
   *
   * @return {number}
   */
  getMarginTop() {
    const headerId = app.screen() === 'phone' ? '#app-navigation' : '#header';

    return this.$() && $(headerId).outerHeight() + parseInt(this.$().css('margin-top'), 10);
  }

  /**
   * 滚动到指定编号的帖子并“闪烁”它。
   *
   * @param {number} number
   * @param {boolean} animate
   * @return {JQueryDeferred}
   */
  scrollToNumber(number, animate) {
    const $item = this.$(`.PostStream-item[data-number=${number}]`);

    return this.scrollToItem($item, animate).then(this.flashItem.bind(this, $item));
  }

  /**
   * 通过索引滚动到某个帖子。
   *
   * @param {number} index 帖子索引
   * @param {boolean} animate 是否带有动画效果
   * @param {boolean} reply 是否滚动到回复占位符
   * @return {JQueryDeferred}
   */
  scrollToIndex(index, animate, reply) {
    const $item = reply ? $('.PostStream-item:last-child') : this.$(`.PostStream-item[data-index=${index}]`);

    this.scrollToItem($item, animate, true, reply);

    if (reply) {
      this.flashItem($item);
    }
  }

  /**
   * 滚动到指定的帖子。
   *
   * @param {JQuery} $item 对象 
   * @param {boolean} animate 是否带有动画效果
   * @param {boolean} force 是否强制滚动到该帖子，即使它已经在视口中
   * @param {boolean} reply 是否滚动到回复占位符
   * @return {JQueryDeferred}
   */
  scrollToItem($item, animate, force, reply) {
    const $container = $('html, body').stop(true);
    const index = $item.data('index');

    if ($item.length) {
      const itemTop = $item.offset().top - this.getMarginTop();
      const itemBottom = $item.offset().top + $item.height();
      const scrollTop = $(document).scrollTop();
      const scrollBottom = scrollTop + $(window).height();

      // 如果帖子已经在视口中，我们可能不需要滚动。
      //  如果我们滚动到回复占位符，我们会确保它的底部与编辑器顶部对齐。
      if (force || itemTop < scrollTop || itemBottom > scrollBottom) {
        const top = reply ? itemBottom - $(window).height() + app.composer.computedHeight() : $item.is(':first-child') ? 0 : itemTop;

        if (!animate) {
          $container.scrollTop(top);
        } else if (top !== scrollTop) {
          $container.animate({ scrollTop: top }, 'fast');
        }
      }
    }

    const updateScrubberHeight = () => {
      // 我们手动设置索引，因为我们想显示我们滚动到的确切帖子的索引，而不仅仅是视口中第一个帖子的索引。
      this.updateScrubber();
      if (index !== undefined) this.stream.index = index + 1;
    };

    // 如果我们在滚动前不更新这个，滚动条会从顶部开始并向下动画，这可能会令人困惑
    updateScrubberHeight();
    this.stream.forceUpdateScrubber = true;

    return Promise.all([$container.promise(), this.stream.loadPromise]).then(() => {
      m.redraw.sync();

      // 渲染帖子内容可能会使我们的位置偏离。
      // 为了抵消这一点，我们将滚动到：
      //   - 回复占位符（与编辑器顶部对齐）
      //   - 如果我们在第一个帖子，则滚动到页面顶部
      //   - 帖子的顶部（如果帖子存在）
      // 如果帖子当前不存在，它可能在我们加载的范围之外，
      // 所以我们不会进行任何调整，因为它很快就会被“加载更多”系统渲染。
      let itemOffset;
      if (reply) {
        const $placeholder = $('.PostStream-item:last-child');
        $(window).scrollTop($placeholder.offset().top + $placeholder.height() - $(window).height() + app.composer.computedHeight());
      } else if (index === 0) {
        $(window).scrollTop(0);
      } else if ((itemOffset = $(`.PostStream-item[data-index=${index}]`).offset())) {
        $(window).scrollTop(itemOffset.top - this.getMarginTop());
      }

      // 在帖子加载完成并位置调整后，我们希望再次调整这个，以确保滚动条的高度是准确的。
      updateScrubberHeight();

      this.calculatePosition();
      this.stream.paused = false;
      // 取消暂停状态
      // 检查滚动后是否需要加载更多帖子。
      this.loadPostsIfNeeded();
    });
  }

  /**
   * “闪烁”指定的帖子，以吸引用户的注意力。
   *
   * @param {JQuery} $item
   */
  flashItem($item) {
    // 这可能会在 PostStreamItem 的 oncreate 中删除 fadeIn 类之前执行，所以我们先删除它以确保安全并避免双重动画。
    $item.removeClass('fadeIn');
    $item.addClass('flash').on('animationend webkitAnimationEnd', (e) => {
      $item.removeClass('flash');
    });
  }
}
