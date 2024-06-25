import { createFocusTrap } from './focusTrap';

/**
 * `Drawer` 类控制页面的抽屉。抽屉是在移动设备上从左侧滑出的区域，包含页眉和页脚。
 */
export default class Drawer {
  /**
   * @type {import('./focusTrap').FocusTrap}
   */
  focusTrap;

  /**
   * @type {HTMLDivElement}
   */
  appElement;

  constructor() {
    // 设置事件处理程序，以便每当内容区域被点击时，抽屉都会关闭。
    document.getElementById('content').addEventListener('click', (e) => {
      if (this.isOpen()) {
        e.preventDefault();
        this.hide();
      }
    });

    this.appElement = document.getElementById('app');
    // 尽管有 `focus-trap` 的文档说明，但 `clickOutsideDeactivates`
    // 和 `allowOutsideClick` 都是必要的，以便可以与从抽屉导航组件触发的模态窗口中的输入进行交互。
    this.focusTrap = createFocusTrap('#drawer', { allowOutsideClick: true, clickOutsideDeactivates: true });
    this.drawerAvailableMediaQuery = window.matchMedia(
      `(max-width: ${getComputedStyle(document.documentElement).getPropertyValue('--screen-phone-max')})`
    );
  }

  /**
   * `window` 上 `resize` 事件的处理程序。
   *
   * 这用于在视口扩大到超过 `phone` 大小时关闭抽屉。
   * 在这一点上，抽屉变成了我们在桌面上看到的标准页眉，但抽屉在内部仍然被注册为 'open' 。
   *
   * 这会导致焦点陷阱出现问题，导致焦点在桌面视口中被捕获在页眉内部。
   *
   * @internal
   */
  resizeHandler = ((e) => {
    if (!e.matches && this.isOpen()) {
      // 抽屉是打开的，但我们已经放大了窗口，所以隐藏它。
      this.hide();
    }
  }).bind(this);

  /**
   * @internal
   * @type {MediaQueryList}
   */
  drawerAvailableMediaQuery;

  /**
   * 检查抽屉当前是否打开。
   *
   * @return {boolean}
   */
  isOpen() {
    return this.appElement.classList.contains('drawerOpen');
  }

  /**
   * 隐藏抽屉。
   */
  hide() {
    /**
     * 作为隐藏抽屉的一部分，此函数还确保抽屉正确动画化退出，同时确保在屏幕外时它不是导航树的一部分。
     */

    this.focusTrap.deactivate();
    this.drawerAvailableMediaQuery.removeListener(this.resizeHandler);

    if (!this.isOpen()) return;

    const $drawer = $('#drawer');

    // 用于防止 `visibility: hidden` 破坏退出动画
    $drawer.css('visibility', 'visible').one('transitionend', () => $drawer.css('visibility', ''));

    this.appElement.classList.remove('drawerOpen');

    this.$backdrop?.remove?.();
  }

  /**
   * 显示抽屉。
   */
  show() {
    this.appElement.classList.add('drawerOpen');

    this.drawerAvailableMediaQuery.addListener(this.resizeHandler);

    this.$backdrop = $('<div/>').addClass('drawer-backdrop fade').appendTo('body').on('click', this.hide.bind(this));

    requestAnimationFrame(() => {
      this.$backdrop.addClass('in');

      this.focusTrap.activate();
    });
  }
}
