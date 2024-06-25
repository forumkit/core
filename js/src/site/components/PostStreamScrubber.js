import app from '../../site/app';
import Component from '../../common/Component';
import icon from '../../common/helpers/icon';
import formatNumber from '../../common/utils/formatNumber';
import ScrollListener from '../../common/utils/ScrollListener';

/**
 * `PostStreamScrubber` 组件显示一个滚动条，用于在帖子流中进行导航/滚动。
 *
 * ### 属性（Attrs）
 *
 * - `stream` 帖子流
 * - `className` 类名
 */
export default class PostStreamScrubber extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.stream = this.attrs.stream;
    this.handlers = {};

    this.scrollListener = new ScrollListener(this.updateScrubberValues.bind(this, { fromScroll: true, forceHeightChange: true }));
  }

  view() {
    const count = this.stream.count();

    // 出于性能考虑，index在这里留空，它将在updateScrubberValues中填充
    const viewing = app.translator.trans('core.site.post_scrubber.viewing_text', {
      count,
      index: <span className="Scrubber-index"></span>,
      formattedCount: <span className="Scrubber-count">{formatNumber(count)}</span>,
    });

    const unreadCount = this.stream.discussion.unreadCount();
    const unreadPercent = count ? Math.min(count - this.stream.index, unreadCount) / count : 0;

    function styleUnread(vnode) {
      const $element = $(vnode.dom);
      const newStyle = {
        top: 100 - unreadPercent * 100 + '%',
        height: unreadPercent * 100 + '%',
        opacity: unreadPercent ? 1 : 0,
      };

      if (vnode.state.oldStyle) {
        $element.stop(true).css(vnode.state.oldStyle).animate(newStyle);
      } else {
        $element.css(newStyle);
      }

      vnode.state.oldStyle = newStyle;
    }
    const classNames = ['PostStreamScrubber', 'Dropdown'];
    if (this.attrs.className) classNames.push(this.attrs.className);

    return (
      <div className={classNames.join(' ')}>
        <button className="Button Dropdown-toggle" data-toggle="dropdown">
          {viewing} {icon('fas fa-sort')}
        </button>

        <div className="Dropdown-menu dropdown-menu">
          <div className="Scrubber">
            <a className="Scrubber-first" onclick={this.goToFirst.bind(this)}>
              {icon('fas fa-angle-double-up')} {app.translator.trans('core.site.post_scrubber.original_post_link')}
            </a>

            <div className="Scrubber-scrollbar">
              <div className="Scrubber-before" />
              <div className="Scrubber-handle">
                <div className="Scrubber-bar" />
                <div className="Scrubber-info">
                  <strong>{viewing}</strong>
                  <span className="Scrubber-description"></span>
                </div>
              </div>
              <div className="Scrubber-after" />

              <div className="Scrubber-unread" oncreate={styleUnread} onupdate={styleUnread}>
                {app.translator.trans('core.site.post_scrubber.unread_text', { count: unreadCount })}
              </div>
            </div>

            <a className="Scrubber-last" onclick={this.goToLast.bind(this)}>
              {icon('fas fa-angle-double-down')} {app.translator.trans('core.site.post_scrubber.now_link')}
            </a>
          </div>
        </div>
      </div>
    );
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    if (this.stream.forceUpdateScrubber) {
      this.stream.forceUpdateScrubber = false;
      this.stream.loadPromise.then(() => this.updateScrubberValues({ animate: true, forceHeightChange: true }));
    }
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // 当窗口大小调整时，调整滚动条的高度以填充侧边栏的高度。
    $(window)
      .on('resize', (this.handlers.onresize = this.onresize.bind(this)))
      .resize();

    // 当滚动条的任何部分被点击时，我们希望跳转到那个位置。
    this.$('.Scrubber-scrollbar')
      .bind('click', this.onclick.bind(this))

      // 现在我们想让滚动条句柄可拖动。首先，防止默认的浏览器事件干扰。
      .bind('dragstart mousedown touchstart', (e) => e.preventDefault());

    // 当鼠标在滚动条句柄上按下时，我们捕获一些关于其当前位置的信息。
    // 我们将这些信息存储在一个对象中，稍后会将其传递给文档的 mousemove/mouseup 事件。
    this.dragging = false;
    this.mouseStart = 0;
    this.indexStart = 0;

    this.$('.Scrubber-handle')
      .bind('mousedown touchstart', this.onmousedown.bind(this))

      // 滚动条句柄不受 '跳转到' 点击事件的影响。
      .click((e) => e.stopPropagation());

    // 当鼠标移动和释放时，我们将首次按下鼠标时捕获的信息传递给一些事件处理程序。
    // 这些处理程序会适当地移动滚动条/流内容。
    $(document)
      .on('mousemove touchmove', (this.handlers.onmousemove = this.onmousemove.bind(this)))
      .on('mouseup touchend', (this.handlers.onmouseup = this.onmouseup.bind(this)));

    setTimeout(() => this.scrollListener.start());

    this.stream.loadPromise.then(() => this.updateScrubberValues({ animate: false, forceHeightChange: true }));
  }

  onremove(vnode) {
    super.onremove(vnode);

    this.scrollListener.stop();
    $(window).off('resize', this.handlers.onresize);

    $(document).off('mousemove touchmove', this.handlers.onmousemove).off('mouseup touchend', this.handlers.onmouseup);
  }

  /**
   * 更新滚动条的位置以反映当前索引/可见属性的值。
   *
   * @param {Partial<{fromScroll: boolean, forceHeightChange: boolean, animate: boolean}>} options
   */
  updateScrubberValues(options = {}) {
    const index = this.stream.index;
    const count = this.stream.count();
    const visible = this.stream.visible || 1;
    const percentPerPost = this.percentPerPost();

    const $scrubber = this.$();
    $scrubber.find('.Scrubber-index').text(formatNumber(this.stream.sanitizeIndex(Math.max(1, index))));
    $scrubber.find('.Scrubber-description').text(this.stream.description);
    $scrubber.toggleClass('disabled', this.stream.disabled());

    const heights = {};
    heights.before = Math.max(0, percentPerPost.index * Math.min(index - 1, count - visible));
    heights.handle = Math.min(100 - heights.before, percentPerPost.visible * visible);
    heights.after = 100 - heights.before - heights.handle;

    // 如果流是暂停的，那么在滚动时不要改变高度，因为视口是由JS滚动的
    // 如果高度变化动画已经在进行中，除非被覆盖，否则不要调整高度 如果
    if ((options.fromScroll && this.stream.paused) || (this.adjustingHeight && !options.forceHeightChange)) return;

    const func = options.animate ? 'animate' : 'css';
    this.adjustingHeight = true;
    const animationPromises = [];
    for (const part in heights) {
      const $part = $scrubber.find(`.Scrubber-${part}`);
      animationPromises.push(
        $part
          .stop(true, true)
          [func]({ height: heights[part] + '%' }, 'fast')
          .promise()
      );

      // jQuery 喜欢设置overflow:hidden，但由于滚动条句柄有一个负的margin-left，我们需要覆盖这个设置
      if (func === 'animate') $part.css('overflow', 'visible');
    }
    Promise.all(animationPromises).then(() => (this.adjustingHeight = false));
  }

  /**
   * 跳转到讨论中的第一篇帖子。
   */
  goToFirst() {
    this.stream.goToFirst();
    this.updateScrubberValues({ animate: true, forceHeightChange: true });
  }

  /**
   * 跳转到讨论中的最后一篇帖子。
   */
  goToLast() {
    this.stream.goToLast();
    this.updateScrubberValues({ animate: true, forceHeightChange: true });
  }

  onresize() {
    // 调整滚动条的高度，使其填充侧边栏的高度，并不与页脚重叠。
    const scrubber = this.$();
    const scrollbar = this.$('.Scrubber-scrollbar');

    scrollbar.css(
      'max-height',
      $(window).height() -
        scrubber.offset().top +
        $(window).scrollTop() -
        parseInt($('#app').css('padding-bottom'), 10) -
        (scrubber.outerHeight() - scrollbar.outerHeight())
    );
  }

  onmousedown(e) {
    e.redraw = false;
    this.mouseStart = e.clientY || e.originalEvent.touches[0].clientY;
    this.indexStart = this.stream.index;
    this.dragging = true;
    $('body').css('cursor', 'move');
    this.$().toggleClass('dragging', this.dragging);
  }

  onmousemove(e) {
    if (!this.dragging) return;

    // 计算鼠标移动的距离，首先以像素为单位，然后转换为滚动条高度的百分比，最后转换为索引。
    // 将这个差值索引加到拖动开始时的索引上，然后滚动到那里。
    const deltaPixels = (e.clientY || e.originalEvent.touches[0].clientY) - this.mouseStart;
    const deltaPercent = (deltaPixels / this.$('.Scrubber-scrollbar').outerHeight()) * 100;
    const deltaIndex = deltaPercent / this.percentPerPost().index || 0;
    const newIndex = Math.min(this.indexStart + deltaIndex, this.stream.count() - 1);

    this.stream.index = Math.max(0, newIndex);
    this.updateScrubberValues();
  }

  onmouseup() {
    this.$().toggleClass('dragging', this.dragging);
    if (!this.dragging) return;

    this.mouseStart = 0;
    this.indexStart = 0;
    this.dragging = false;
    $('body').css('cursor', '');

    this.$().removeClass('open');

    //  如果我们落在了一个间隙上，则告诉帖子内容组件我们想要加载那些帖子。
    const intIndex = Math.floor(this.stream.index);
    this.stream.goToIndex(intIndex);
  }

  onclick(e) {
    // 根据点击位置计算想要跳转的索引。

    // 1. 获取点击位置相对于滚动条顶部的偏移量，作为滚动条高度的百分比。
    const $scrollbar = this.$('.Scrubber-scrollbar');
    const offsetPixels = (e.pageY || e.originalEvent.touches[0].pageY) - $scrollbar.offset().top + $('body').scrollTop();
    let offsetPercent = (offsetPixels / $scrollbar.outerHeight()) * 100;

    // 2. 我们希望滚动条的句柄以点击位置为中心。因此，我们计算句柄的高度百分比，并使用它来找到新的偏移百分比。
    offsetPercent = offsetPercent - parseFloat($scrollbar.find('.Scrubber-handle')[0].style.height) / 2;

    // 3. 现在我们可以将百分比转换为索引，并告诉流内容组件跳转到该索引。
    let offsetIndex = offsetPercent / this.percentPerPost().index;
    offsetIndex = Math.max(0, Math.min(this.stream.count() - 1, offsetIndex));
    this.stream.goToIndex(Math.floor(offsetIndex));
    this.updateScrubberValues({ animate: true, forceHeightChange: true });

    this.$().removeClass('open');
  }

  /**
   * 获取应分配给每个帖子的滚动条高度百分比。

   *
   * @return {{ index: number, visible: number }}
   * @property {Number} index 滚动条可见部分两侧帖子的每帖百分比
   * @property {Number} visible 滚动条可见部分的每帖百分比
   */
  percentPerPost() {
    const count = this.stream.count() || 1;
    const visible = this.stream.visible || 1;

    // 当有很多帖子时，为了防止滚动条的句柄变得太小，我们定义了基于50像素限制的句柄的最小百分比高度。基于这个限制，我们可以计算出每个可见帖子所需的最小百分比。如果这个最小百分比大于实际每个帖子的百分比，那么我们需要调整“之前”的百分比来适应它。
    const minPercentVisible = (50 / this.$('.Scrubber-scrollbar').outerHeight()) * 100;
    const percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
    const percentPerPost = count === visible ? 0 : (100 - percentPerVisiblePost * visible) / (count - visible);

    return {
      index: percentPerPost,
      visible: percentPerVisiblePost,
    };
  }
}
