import app from '../../site/app';
import subclassOf from '../../common/utils/subclassOf';
import Stream from '../../common/utils/Stream';
import ReplyComposer from '../components/ReplyComposer';

class ComposerState {
  constructor() {
    /**
     * 编辑器当前的位置。
     *
     * @type {ComposerState.Position}
     */
    this.position = ComposerState.Position.HIDDEN;

    /**
     * 编辑器的预设高度，可由用户修改（通过拖动编辑器句柄）。
     *
     * @type {number}
     */
    this.height = null;

    /**
     * 编辑器内部显示的动态组件。
     *
     * @type {Object}
     */
    this.body = { attrs: {} };

    /**
     * 对文本编辑器的引用，允许进行文本操作。
     *
     * @type {import('../../common/utils/EditorDriverInterface')|null}
     */
    this.editor = null;

    this.clear();
  }

  /**
   * 将内容组件加载到编辑器中。
   *
   * @param {typeof import('../components/ComposerBody').default} componentClass
   */
  load(componentClass, attrs) {
    const body = { componentClass, attrs };

    if (this.preventExit()) return;

    // 如果我们向编辑器中加载了类似的组件，Mithril 将能够比较新旧内容，并且旧编辑器的一些与DOM相关的状态将保持不变。为了防止这种情况发生，我们清除组件并强制重新绘制，以便新组件将在空白的画布上工作。
    if (this.isVisible()) {
      this.clear();
      m.redraw.sync();
    }

    this.body = body;
  }

  /**
   * 清除编辑器的内容组件。
   */
  clear() {
    this.position = ComposerState.Position.HIDDEN;
    this.body = { attrs: {} };
    this.onExit = null;

    this.fields = {
      content: Stream(''),
    };

    if (this.editor) {
      this.editor.destroy();
    }
    this.editor = null;
  }

  /**
   * 显示编辑器。
   */
  show() {
    if (this.position === ComposerState.Position.NORMAL || this.position === ComposerState.Position.FULLSCREEN) return;

    this.position = ComposerState.Position.NORMAL;
    m.redraw.sync();
  }

  /**
   * 关闭编辑器。
   */
  hide() {
    this.clear();
    m.redraw();
  }

  /**
   * 在关闭编辑器之前向用户确认，以防止他们丢失内容。
   */
  close() {
    if (this.preventExit()) return;

    this.hide();
  }

  /**
   * 最小化编辑器。如果编辑器是隐藏的，则没有效果。
   */
  minimize() {
    if (!this.isVisible()) return;

    this.position = ComposerState.Position.MINIMIZED;
    m.redraw();
  }

  /**
   * 将编辑器设置为全屏模式。如果编辑器是隐藏的，则没有效果。
   */
  fullScreen() {
    if (!this.isVisible()) return;

    this.position = ComposerState.Position.FULLSCREEN;
    m.redraw();
  }

  /**
   * 退出全屏模式。
   */
  exitFullScreen() {
    if (this.position !== ComposerState.Position.FULLSCREEN) return;

    this.position = ComposerState.Position.NORMAL;
    m.redraw();
  }

  /**
   * 确定主体是否匹配给定的组件类和数据。
   *
   * @param {object} type 要检查的组件类。也接受子类。
   * @param {object} data
   * @return {boolean}
   */
  bodyMatches(type, data = {}) {
    // 如果主体的类型不匹配，则立即失败
    if (!subclassOf(this.body.componentClass, type)) return false;

    // 现在已知类型是正确的，我们遍历提供的数据，以查看它是否与主体属性中的数据匹配。
    return Object.keys(data).every((key) => this.body.attrs[key] === data[key]);
  }

  /**
   * 确定编辑器是否可见。
   *
   * 当编辑器在屏幕上显示并具有主体组件时，返回true。
   * 它可能以“正常”或全屏模式打开，甚至是最小化。
   *
   * @returns {boolean}
   */
  isVisible() {
    return this.position !== ComposerState.Position.HIDDEN;
  }

  /**
   * 确定编辑器是否覆盖屏幕。
   *
   * 如果在桌面上编辑器处于全屏模式，或者我们使用的是移动设备，我们总是将编辑器视为全屏模式，则此方法将返回true。
   *
   * @return {boolean}
   */
  isFullScreen() {
    return this.position === ComposerState.Position.FULLSCREEN || app.screen() === 'phone';
  }

  /**
   * 检查用户当前是否正在为某个讨论编写回复。
   *
   * @param {import('../../common/models/Discussion').default} discussion
   * @return {boolean}
   */
  composingReplyTo(discussion) {
    return this.isVisible() && this.bodyMatches(ReplyComposer, { discussion });
  }

  /**
   * 向用户确认他们是否希望关闭编辑器并丢失内容。
   *
   * @return {boolean} 是否取消了退出
   */
  preventExit() {
    if (!this.isVisible()) return;
    if (!this.onExit) return;

    if (this.onExit.callback()) {
      return !confirm(this.onExit.message);
    }
  }

  /**
   * 配置在关闭编辑器前何时/何询问用户。
   *
   * 提供的回调函数将用于确定是否需要请求确认。如果在关闭时回调返回true，则将在标准确认对话框中显示提供的文本。
   *
   * @param {() => boolean} callback
   * @param {string} message
   */
  preventClosingWhen(callback, message) {
    this.onExit = { callback, message };
  }

  /**
   * 编辑器的最小高度。
   * @returns {number}
   */
  minimumHeight() {
    return 200;
  }

  /**
   * 编辑器的最大高度。
   * @returns {number}
   */
  maximumHeight() {
    return $(window).height() - $('#header').outerHeight();
  }

  /**
   * 根据预定高度和编辑器的当前状态计算编辑器的当前高度，这将应用于编辑器内容的DOM元素。
   * @returns {number | string}
   */
  computedHeight() {
    // 如果编辑器是最小化状态，我们不想设置高度，我们将让CSS决定其高度。如果是全屏状态，我们需要将其设置为窗口的高度。
    if (this.position === ComposerState.Position.MINIMIZED) {
      return '';
    } else if (this.position === ComposerState.Position.FULLSCREEN) {
      return $(window).height();
    }

    // 否则，如果它是正常或隐藏状态，我们将使用预定高度。但是，我们不会让编辑器变得太小或太大。
    return Math.max(this.minimumHeight(), Math.min(this.height, this.maximumHeight()));
  }
}

ComposerState.Position = {
  HIDDEN: 'hidden',
  NORMAL: 'normal',
  MINIMIZED: 'minimized',
  FULLSCREEN: 'fullScreen',
};

export default ComposerState;
