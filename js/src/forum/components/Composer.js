import app from '../../forum/app';
import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import ComposerButton from './ComposerButton';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';
import ComposerState from '../states/ComposerState';

/**
 * `Composer` 组件用于显示作曲家（即帖子或回复的编辑器）。
 * 它可以通过 `load` 方法加载内容组件，
 * 并通过 `show`、`hide`、`close`、`minimize`、`fullScreen` 和 `exitFullScreen` 方法来改变其位置或状态。
 */
export default class Composer extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * 作曲家的“状态”。
     *
     * @type {ComposerState}
     */
    this.state = this.attrs.state;

    /**
     * 作曲家当前是否拥有焦点。
     *
     * @type {Boolean}
     */
    this.active = false;

    // 存储初始位置，以便正确触发动画
    this.prevPosition = this.state.position;
  }

  view() {
    const body = this.state.body;
    const classes = {
      normal: this.state.position === ComposerState.Position.NORMAL,
      minimized: this.state.position === ComposerState.Position.MINIMIZED,
      fullScreen: this.state.position === ComposerState.Position.FULLSCREEN,
      active: this.active,
      visible: this.state.isVisible(),
    };

    // 如果当前是最小化状态，则点击内容区域显示作曲家
    const showIfMinimized = this.state.position === ComposerState.Position.MINIMIZED ? this.state.show.bind(this.state) : undefined;

    const ComposerBody = body.componentClass;

    return (
      <div className={'Composer ' + classList(classes)}>
        <div className="Composer-handle" oncreate={this.configHandle.bind(this)} />
        <ul className="Composer-controls">{listItems(this.controlItems().toArray())}</ul>
        <div className="Composer-content" onclick={showIfMinimized}>
          {ComposerBody && <ComposerBody {...body.attrs} composer={this.state} disabled={classes.minimized} />}
        </div>
      </div>
    );
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    if (this.state.position === this.prevPosition) {
      // 如果当前位置与上一个位置相同，则更新作曲家元素及其内容的高度，
      // 以防止在其DOM元素重新创建时丢失高度。
      this.updateHeight();
    } else {
      this.animatePositionChange();

      this.prevPosition = this.state.position;
    }
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.initializeHeight();
    this.$().hide().css('bottom', -this.state.computedHeight());

    // 当作曲家内部的任何输入获得焦点时，给作曲家添加一个类以吸引注意
    this.$().on('focus blur', ':input,.TextEditor-editorContainer', (e) => {
      this.active = e.type === 'focusin';
      m.redraw();
    });

    // 当在任何输入上按下Escape键时，关闭作曲家
    this.$().on('keydown', ':input,.TextEditor-editorContainer', 'esc', () => this.state.close());

    this.handlers = {};

    $(window)
      .on('resize', (this.handlers.onresize = this.updateHeight.bind(this)))
      .resize();

    $(document)
      .on('mousemove', (this.handlers.onmousemove = this.onmousemove.bind(this)))
      .on('mouseup', (this.handlers.onmouseup = this.onmouseup.bind(this)));
  }

  onremove(vnode) {
    super.onremove(vnode);

    $(window).off('resize', this.handlers.onresize);

    $(document).off('mousemove', this.handlers.onmousemove).off('mouseup', this.handlers.onmouseup);
  }

  /**
   * 为作曲家的句柄添加必要的事件处理程序，以便可以通过它来调整作曲家的大小。
   */
  configHandle(vnode) {
    const composer = this;

    $(vnode.dom)
      .css('cursor', 'row-resize')
      .bind('dragstart mousedown', (e) => e.preventDefault())
      .mousedown(function (e) {
        composer.mouseStart = e.clientY;
        composer.heightStart = composer.$().height();
        composer.handle = $(this);
        $('body').css('cursor', 'row-resize');
      });
  }

  /**
   * 根据鼠标移动来调整作曲家的大小。
   *
   * @param {MouseEvent} e
   */
  onmousemove(e) {
    if (!this.handle) return;

    // 计算鼠标移动了多少像素，并基于这个距离调整作曲家的高度
    // 然后更新内容的高度以填充作曲家的整个高度，并更新页面的底部内边距
    const deltaPixels = this.mouseStart - e.clientY;
    this.changeHeight(this.heightStart + deltaPixels);

    // 更新页面的底部内边距，确保页面上的内容不会永久隐藏在作曲家之后
    // 如果用户已经滚动到页面底部，则在更新内边距后保持他们滚动到底部
    const scrollTop = $(window).scrollTop();
    const anchorToBottom = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();
    this.updateBodyPadding(anchorToBottom);
  }

  /**
   * 当鼠标释放时，完成调整作曲家的大小。
   */
  onmouseup() {
    if (!this.handle) return;

    this.handle = null;
    $('body').css('cursor', '');
  }

  /**
   * 将焦点设置到第一个可聚焦的内容元素（文本编辑器）上。
   */
  focus() {
    this.$('.Composer-content :input:enabled:visible, .TextEditor-editor').first().focus();
  }

  /**
   * 更新DOM以反映作曲家的当前高度。这包括设置作曲家根元素的高度，
   * 并调整作曲家体内任何灵活元素的高度。
   */
  updateHeight() {
    const height = this.state.computedHeight();
    const $flexible = this.$('.Composer-flexible');

    this.$().height(height);

    if ($flexible.length) {
      const headerHeight = $flexible.offset().top - this.$().offset().top;
      const paddingBottom = parseInt($flexible.css('padding-bottom'), 10);
      const footerHeight = this.$('.Composer-footer').outerHeight(true);

      $flexible.height(this.$().outerHeight() - headerHeight - paddingBottom - footerHeight);
    }
  }

  /**
   * 更新页面body的底部内边距，以确保当页面滚动到底部时，页面的内容仍然可以在作曲家上方可见。
   */
  updateBodyPadding() {
    const visible =
      this.state.position !== ComposerState.Position.HIDDEN && this.state.position !== ComposerState.Position.MINIMIZED && app.screen() !== 'phone';

    const paddingBottom = visible ? this.state.computedHeight() - parseInt($('#app').css('padding-bottom'), 10) : 0;

    $('#content').css({ paddingBottom });
  }

  /**
   * 根据期望的新位置触发相应的动画。
   */
  animatePositionChange() {
    // 退出全屏模式时：聚焦内容
    if (this.prevPosition === ComposerState.Position.FULLSCREEN && this.state.position === ComposerState.Position.NORMAL) {
      this.focus();
      return;
    }

    switch (this.state.position) {
      case ComposerState.Position.HIDDEN:
        return this.hide();
      case ComposerState.Position.MINIMIZED:
        return this.minimize();
      case ComposerState.Position.FULLSCREEN:
        return this.focus();
      case ComposerState.Position.NORMAL:
        return this.show();
    }
  }

  /**
   * 通过改变高度将作曲家动画化为新位置。
   */
  animateHeightChange() {
    const $composer = this.$().stop(true);
    const oldHeight = $composer.outerHeight();
    const scrollTop = $(window).scrollTop();

    $composer.show();
    this.updateHeight();

    const newHeight = $composer.outerHeight();

    if (this.prevPosition === ComposerState.Position.HIDDEN) {
      $composer.css({ bottom: -newHeight, height: newHeight });
    } else {
      $composer.css({ height: oldHeight });
    }

    const animation = $composer.animate({ bottom: 0, height: newHeight }, 'fast').promise();

    this.updateBodyPadding();
    $(window).scrollTop(scrollTop);
    return animation;
  }

  /**
   * 显示作曲家的背景幕布。
   */
  showBackdrop() {
    this.$backdrop = $('<div/>').addClass('composer-backdrop').appendTo('body');
  }

  /**
   * 隐藏作曲家的背景幕布。
   */
  hideBackdrop() {
    if (this.$backdrop) this.$backdrop.remove();
  }

  /**
   * 使作曲家组件从底部滑动到其正常高度。
   *
   * @private
   */
  show() {
    this.animateHeightChange().then(() => this.focus());

    if (app.screen() === 'phone') {
      // 在Safari移动版上，固定定位（fixed position）不能正常工作，
      // 所以我们使用绝对定位并设置顶部值。

      // 由于Safari的另一个bug，当页面滚动到底部并打开作曲家时，
      // `scrollTop`的值是不可靠的。
      // 因此，我们回退到使用计算得到的`scrollTop`值。
      const scrollElement = document.documentElement;
      const topOfViewport = Math.min(scrollElement.scrollTop, scrollElement.scrollHeight - scrollElement.clientHeight);
      this.$().css('top', $('.App').is('.mobile-safari') ? topOfViewport : 0);
      this.showBackdrop();
    }
  }

  /**
   * 动画关闭作曲家。
   *
   * @private
   */
  hide() {
    const $composer = this.$();

    // 执行作曲家向下滑动的动画，并在动画完成后隐藏背景幕布和更新页面样式
    // 省略了动画完成后的回调函数实现
    $composer.stop(true).animate({ bottom: -$composer.height() }, 'fast', () => {
      $composer.hide();
      this.hideBackdrop();
      this.updateBodyPadding();
    });
  }

  /**
   * 缩小作曲家直到只有标题可见。
   *
   * @private
   */
  minimize() {
    this.animateHeightChange();

    this.$().css('top', 'auto');
    this.hideBackdrop();
  }

  /**
   * 为作曲家的控件构建项目列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  controlItems() {
    const items = new ItemList();

    if (this.state.position === ComposerState.Position.FULLSCREEN) {
      items.add(
        'exitFullScreen',
        <ComposerButton
          icon="fas fa-compress"
          title={app.translator.trans('core.forum.composer.exit_full_screen_tooltip')}
          onclick={this.state.exitFullScreen.bind(this.state)}
        />
      );
    } else {
      if (this.state.position !== ComposerState.Position.MINIMIZED) {
        items.add(
          'minimize',
          <ComposerButton
            icon="fas fa-minus minimize"
            title={app.translator.trans('core.forum.composer.minimize_tooltip')}
            onclick={this.state.minimize.bind(this.state)}
            itemClassName="App-backControl"
          />
        );

        items.add(
          'fullScreen',
          <ComposerButton
            icon="fas fa-expand"
            title={app.translator.trans('core.forum.composer.full_screen_tooltip')}
            onclick={this.state.fullScreen.bind(this.state)}
          />
        );
      }

      items.add(
        'close',
        <ComposerButton
          icon="fas fa-times"
          title={app.translator.trans('core.forum.composer.close_tooltip')}
          onclick={this.state.close.bind(this.state)}
        />
      );
    }

    return items;
  }

  /**
   * 初始化作曲家的默认高度。
   */
  initializeHeight() {
    this.state.height = localStorage.getItem('composerHeight');

    if (!this.state.height) {
      this.state.height = this.defaultHeight();
    }
  }

  /**
   * 如果没有保存作曲家的高度，则返回作曲家的默认高度。
   * @returns {number}
   */
  defaultHeight() {
    return this.$().height();
  }

  /**
   * 保存新的作曲家高度并更新DOM。
   * @param {number} height
   */
  changeHeight(height) {
    this.state.height = height;
    this.updateHeight();

    localStorage.setItem('composerHeight', this.state.height);
  }
}
