import setRouteWithForcedRefresh from '../../common/utils/setRouteWithForcedRefresh';
import IHistory, { HistoryEntry } from '../../common/IHistory';

/**
 * `History` 类跟踪并管理用户在其会话中导航到的路由堆栈。
 *
 * 可以使用 `push` 方法将项目推送到堆栈的顶部。堆栈中的每个项目都有一个名称和一个URL。名称不必是唯一的；如果它与之前的项目相同，那么旧的URL将被新的URL覆盖。通过这种方式，如果用户访问了一个讨论，然后访问了另一个讨论，弹出历史堆栈仍然会将他们带回到讨论列表，而不是上一个讨论。
 */
export default class History implements IHistory {
  /**
   * 已导航到的路由堆栈。
   */
  protected stack: HistoryEntry[] = [];

  /**
   * 获取堆栈顶部的项目。
   */
  getCurrent(): HistoryEntry {
    return this.stack[this.stack.length - 1];
  }

  /**
   * 获取堆栈上一个项目。
   */
  getPrevious(): HistoryEntry {
    return this.stack[this.stack.length - 2];
  }

  /**
   * 将项目推送到堆栈的顶部。
   *
   * @param {string} name 路由的名称
   * @param {string} title 路由的标题
   * @param {string} [url] 路由的URL。如果未提供，则使用当前URL
   */
  push(name: string, title: string, url = m.route.get()) {
    // 如果我们推送的项目名称与堆栈中倒数第二个项目的名称相同，
    // 我们将假设用户点击了浏览器中的 'back' 按钮。
    // 在这种情况下，我们不想推送新项目，所以我们将弹出顶部项目，
    // 然后下面的项目将被覆盖。
    const secondTop = this.stack[this.stack.length - 2];
    if (secondTop && secondTop.name === name) {
      this.stack.pop();
    }

    // 如果我们推送的项目名称与堆栈顶部项目的名称相同，
    // 那么我们将使用新的URL覆盖它。
    const top = this.getCurrent();
    if (top && top.name === name) {
      Object.assign(top, { url, title });
    } else {
      this.stack.push({ name, url, title });
    }
  }

  /**
   * 检查历史堆栈是否可以回退。
   */
  canGoBack(): boolean {
    return this.stack.length > 1;
  }

  /**
   * 返回到历史堆栈中的上一个路由。
   */
  back() {
    if (!this.canGoBack()) {
      return this.home();
    }

    this.stack.pop();

    m.route.set(this.getCurrent().url);
  }

  /**
   * 获取上一页的URL。
   */
  backUrl(): string {
    const secondTop = this.stack[this.stack.length - 2];

    return secondTop.url;
  }

  /**
   * 转到历史堆栈中的第一个路由。
   */
  home() {
    this.stack.splice(0);

    setRouteWithForcedRefresh('/');
  }
}
