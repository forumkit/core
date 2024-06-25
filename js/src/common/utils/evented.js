import { fireDeprecationWarning } from '../helpers/fireDebugWarning';

const deprecatedNotice = '`evented` 实用程序已弃用且不再支持。';
const deprecationIssueId = '2547';

/**
 * `evented` 混入提供了允许对象触发事件的方法，运行外部注册的事件处理程序。
 *
 * @deprecated v1.2 版本开始弃用，将在 v2.0 版本中移除
 */
export default {
  /**
   * 按事件名称分组注册的事件处理程序数组。
   *
   * @type {Record<string, unknown>}
   * @protected
   *
   * @deprecated
   */
  handlers: null,

  /**
   * 获取某个事件的所有已注册处理程序。
   *
   * @param {string} event 事件名称
   * @return {Function[]}
   * @protected
   *
   * @deprecated
   */
  getHandlers(event) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    this.handlers = this.handlers || {};

    this.handlers[event] = this.handlers[event] || [];

    return this.handlers[event];
  },

  /**
   * 触发一个事件。
   *
   * @param {string} event 事件名称
   * @param {any[]} args 传递给事件处理程序的参数
   *
   * @deprecated
   */
  trigger(event, ...args) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    this.getHandlers(event).forEach((handler) => handler.apply(this, args));
  },

  /**
   * 注册一个事件处理程序。
   *
   * @param {string} event 事件名称
   * @param {Function} handler 处理事件的函数
   *
   * @deprecated
   */
  on(event, handler) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    this.getHandlers(event).push(handler);
  },

  /**
   * 注册一个事件处理程序，以便它只运行一次，然后自行注销。
   *
   * @param {string} event 事件名称
   * @param {Function} handler 处理事件的函数
   *
   * @deprecated
   */
  one(event, handler) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    const wrapper = function () {
      handler.apply(this, arguments);

      this.off(event, wrapper);
    };

    this.getHandlers(event).push(wrapper);
  },

  /**
   * 注销一个事件处理程序。
   *
   * @param {string} event 事件名称
   * @param {Function} handler 处理事件的函数
   *
   * @deprecated
   */
  off(event, handler) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    const handlers = this.getHandlers(event);
    const index = handlers.indexOf(handler);

    if (index !== -1) {
      handlers.splice(index, 1);
    }
  },
};
