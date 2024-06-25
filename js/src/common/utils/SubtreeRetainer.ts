/**
 * `SubtreeRetainer` 类用于跟踪一定数量的数据片段，并在每次迭代时比较这些片段的值。
 *
 * 这对于防止在没有任何值发生更改时，对仅依赖于极少数值的相对静态（或巨大）组件进行重新绘制非常有用。
 *
 * @example
 * // 在每次更新时检查两个回调是否有变化
 * this.subtree = new SubtreeRetainer(
 *   () => this.attrs.post.freshness,
 *   () => this.showing
 * );
 *
 * // 添加更多回调以检查更新
 * this.subtree.check(() => this.attrs.user.freshness);
 *
 * // 在组件的 onbeforeupdate() 方法中：
 * return this.subtree.needsRebuild()
 *
 * @see https://mithril.js.org/lifecycle-methods.html#onbeforeupdate
 */
export default class SubtreeRetainer {
  protected callbacks: (() => any)[];
  protected data: Record<string, any>;

  /**
   * 构造函数，接收一个或多个返回数据的函数作为参数。
   * 
   * @param callbacks 返回要跟踪的数据的函数
   */
  constructor(...callbacks: (() => any)[]) {
    this.callbacks = callbacks;
    this.data = {};

    // 初始化数据，以便在调用 onbeforeupdate 钩子中的 needsRebuild 时可以使用
    this.needsRebuild();
  }

  /**
   * 返回自上次检查以来是否有任何数据发生更改。
   * 如果有，Mithril 需要重新比较 vnode 及其子节点。
   */
  needsRebuild(): boolean {
    let needsRebuild = false;

    this.callbacks.forEach((callback, i) => {
      const result = callback();

      if (result !== this.data[i]) {
        this.data[i] = result;
        needsRebuild = true;
      }
    });

    return needsRebuild;
  }

  /**
   * 添加另一个要检查的回调函数。
   */
  check(...callbacks: (() => any)[]): void {
    this.callbacks = this.callbacks.concat(callbacks);
    // 当添加新检查时，更新数据缓存
    this.needsRebuild();
  }

  /**
   * 使子树失效，强制重新绘制。
   */
  invalidate(): void {
    this.data = {};
  }
}
