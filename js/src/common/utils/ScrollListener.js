/**
 * `ScrollListener` 类设置了一个监听器，用于处理窗口滚动事件
 */
export default class ScrollListener {
  /**
   * @param {(top: number) => void} callback  滚动位置变化时运行的回调函数
   */
  constructor(callback) {
    this.callback = callback;
    this.ticking = false;
  }

  /**
   * 在每个动画帧上，只要监听器处于活动状态，就运行 `update` 方法
   *
   * @protected
   */
  loop() {
    // 节流：如果回调函数仍在运行（或尚未运行），我们忽略进一步的滚动事件
    if (this.ticking) return;

    // 安排回调函数尽快执行（TM），并在回调函数完成后停止节流
    requestAnimationFrame(() => {
      this.update();
      this.ticking = false;
    });

    this.ticking = true;
  }

  /**
   * 无论是否发生了滚动事件，都运行回调函数
   */
  update() {
    this.callback(window.pageYOffset);
  }

  /**
   * 开始监听和处理窗口的滚动位置
   */
  start() {
    if (!this.active) {
      window.addEventListener('scroll', (this.active = this.loop.bind(this)), { passive: true });
    }
  }

  /**
   * 停止监听和处理窗口的滚动位置
   */
  stop() {
    window.removeEventListener('scroll', this.active);

    this.active = null;
  }
}
