/**
 * `Pane` 类管理页面的讨论列表侧边栏。侧边栏是内容视图（DiscussionPage 组件）的一部分，
 * 但其可见性由应用到外部页面元素的 CSS 类确定。这个类管理这些 CSS 类的应用。
 */
export default class Pane {
  constructor(element) {
    /**
     * localStorage 中存储侧边栏固定状态的键。
     *
     * @type {String}
     * @protected
     */
    this.pinnedKey = 'panePinned';

    /**
     * 页面元素。
     *
     * @type {jQuery}
     * @protected
     */
    this.$element = $(element);

    /**
     * 当前侧边栏是否已固定。
     *
     * @type {Boolean}
     * @protected
     */
    this.pinned = localStorage.getItem(this.pinnedKey) === 'true';

    /**
     * 当前侧边栏是否存在。
     *
     * @type {Boolean}
     * @protected
     */
    this.active = false;

    /**
     * 当前侧边栏是否正在显示，或隐藏在屏幕边缘之外。
     *
     * @type {Boolean}
     * @protected
     */
    this.showing = false;

    this.render();
  }

  /**
   * 启用侧边栏。
   */
  enable() {
    this.active = true;
    this.render();
  }

  /**
   * 禁用侧边栏。
   */
  disable() {
    this.active = false;
    this.showing = false;
    this.render();
  }

  /**
   * 显示侧边栏。
   */
  show() {
    clearTimeout(this.hideTimeout);
    this.showing = true;
    this.render();
  }

  /**
   * 隐藏侧边栏。
   */
  hide() {
    this.showing = false;
    this.render();
  }

  /**
   * 开始一个定时器以隐藏侧边栏，显示侧边栏时可以取消该定时器。
   */
  onmouseleave() {
    this.hideTimeout = setTimeout(this.hide.bind(this), 250);
  }

  /**
   * 切换侧边栏是否固定。
   */
  togglePinned() {
    this.pinned = !this.pinned;

    localStorage.setItem(this.pinnedKey, this.pinned ? 'true' : 'false');

    this.render();
  }

  /**
   * 将适当的 CSS 类应用到页面元素上。
   *
   * @protected
   */
  render() {
    this.$element.toggleClass('panePinned', this.pinned).toggleClass('hasPane', this.active).toggleClass('paneShowing', this.showing);
  }
}
