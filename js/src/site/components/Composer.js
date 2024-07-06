import app from '../../site/app';
import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import ComposerButton from './ComposerButton';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';
import ComposerState from '../states/ComposerState';

/**
 * `Composer` 组件用于显示编辑器。它可以通过 `load` 方法加载一个内容组件，然后通过 `show`、`hide`、`close`、`minimize`、`fullScreen` 和 `exitFullScreen` 来改变其位置/状态。
 */
export default class Composer extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * 编辑器的“状态”。
     *
     * @type {ComposerState}
     */
    this.state = this.attrs.state;

    /**
     * 编辑器当前是否获得焦点。
     *
     * @type {Boolean}
     */
    this.active = false;

    // 存储初始位置，以便我们可以正确地触发动画。
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

    // 设置一个处理器，以便点击内容时显示编辑器（如果它当前是最小化的）。
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
      // 在每次重绘时设置 Composer 元素及其内容的高度，
      // 以防它们的 DOM 元素被重新创建时丢失高度。
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

    // 当编辑器内的任何输入项获得焦点时，我们想在编辑器上添加一个类以吸引注意力。
    this.$().on('focus blur', ':input,.TextEditor-editorContainer', (e) => {
      this.active = e.type === 'focusin';
      m.redraw();
    });

    // 当在任何输入项上按下 escape 键时，关闭编辑器。
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
   * 为编辑器的句柄添加必要的事件处理程序，以便可以使用它调整编辑器的大小。
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
   * 根据鼠标移动调整编辑器的大小。
   *
   * @param {MouseEvent} e
   */
  onmousemove(e) {
    if (!this.handle) return;

    // 计算鼠标移动了多少像素，并基于旧的高度设置新的高度。
    // 然后更新内容的高度以填充编辑器的高度，并更新主体的内边距。
    const deltaPixels = this.mouseStart - e.clientY;
    this.changeHeight(this.heightStart + deltaPixels);

    // 更新 body 的 padding-bottom，以确保页面上的任何内容都不会被编辑器永久遮挡。
    // 如果用户已经滚动到页面底部，则在 padding 更新后，我们仍会保持他们滚动到底部的位置。
    const scrollTop = $(window).scrollTop();
    const anchorToBottom = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();
    this.updateBodyPadding(anchorToBottom);
  }

  /**
   * 当鼠标释放时，完成编辑器的尺寸调整。
   */
  onmouseup() {
    if (!this.handle) return;

    this.handle = null;
    $('body').css('cursor', '');
  }

  /**
   * 将焦点放在第一个可聚焦的内容元素（文本编辑器）上。
   */
  focus() {
    this.$('.Composer-content :input:enabled:visible, .TextEditor-editor').first().focus();
  }

  /**
   * 更新 DOM 以反映编辑器的当前高度。这包括设置编辑器根元素的高度，
   * 并调整编辑器体内任何灵活元素的高度。
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
   * 更新页面主体的底部内边距大小，以便当页面滚动到底部时，页面的内容仍然可以在编辑器上方可见。
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
    // 当退出全屏模式时：聚焦内容
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
   * 通过改变高度来使 Composer 动画过渡到新的位置。
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
   * 显示 Composer 的背景。
   */
  showBackdrop() {
    this.$backdrop = $('<div/>').addClass('composer-backdrop').appendTo('body');
  }

  /**
   * 隐藏 Composer 的背景。
   */
  hideBackdrop() {
    if (this.$backdrop) this.$backdrop.remove();
  }

  /**
   * 将 Composer 从底部滑动上来，达到其正常高度。
   *
   * @private
   */
  show() {
    this.animateHeightChange().then(() => this.focus());

    if (app.screen() === 'phone') {

      const scrollElement = document.documentElement;
      const topOfViewport = Math.min(scrollElement.scrollTop, scrollElement.scrollHeight - scrollElement.clientHeight);
      this.$().css('top', $('.App').is('.mobile-safari') ? topOfViewport : 0);
      this.showBackdrop();
    }
  }

  /**
   * 以动画形式关闭编辑器。
   *
   * @private
   */
  hide() {
    const $composer = this.$();

    // 将编辑器滑动到视口底部边缘之外进行动画处理。
    // 只有在动画完成后，才更新页面上的其他元素。
    $composer.stop(true).animate({ bottom: -$composer.height() }, 'fast', () => {
      $composer.hide();
      this.hideBackdrop();
      this.updateBodyPadding();
    });
  }

  /**
   * 缩小编辑器直到只有其标题可见。
   *
   * @private
   */
  minimize() {
    this.animateHeightChange();

    this.$().css('top', 'auto');
    this.hideBackdrop();
  }

  /**
   * 为编辑器的控件构建项目列表。
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
          title={app.translator.trans('core.site.composer.exit_full_screen_tooltip')}
          onclick={this.state.exitFullScreen.bind(this.state)}
        />
      );
    } else {
      if (this.state.position !== ComposerState.Position.MINIMIZED) {
        items.add(
          'minimize',
          <ComposerButton
            icon="fas fa-minus minimize"
            title={app.translator.trans('core.site.composer.minimize_tooltip')}
            onclick={this.state.minimize.bind(this.state)}
            itemClassName="App-backControl"
          />
        );

        items.add(
          'fullScreen',
          <ComposerButton
            icon="fas fa-expand"
            title={app.translator.trans('core.site.composer.full_screen_tooltip')}
            onclick={this.state.fullScreen.bind(this.state)}
          />
        );
      }

      items.add(
        'close',
        <ComposerButton
          icon="fas fa-times"
          title={app.translator.trans('core.site.composer.close_tooltip')}
          onclick={this.state.close.bind(this.state)}
        />
      );
    }

    return items;
  }

  /**
   * 初始化默认的 Composer 高度。
   */
  initializeHeight() {
    this.state.height = localStorage.getItem('composerHeight');

    if (!this.state.height) {
      this.state.height = this.defaultHeight();
    }
  }

  /**
   * 在没有保存的情况下，Composer 的默认高度。
   * @returns {number}
   */
  defaultHeight() {
    return this.$().height();
  }

  /**
   * 保存新的 Composer 高度并更新 DOM。
   * @param {number} height
   */
  changeHeight(height) {
    this.state.height = height;
    this.updateHeight();

    localStorage.setItem('composerHeight', this.state.height);
  }
}
