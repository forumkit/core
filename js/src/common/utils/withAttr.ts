/**
 * 一个事件处理器工厂，它简化了为组件事件监听器实现数据绑定的过程。
 *
 * 此工厂创建的事件处理器会将 DOM 元素的属性（由第一个参数标识）传递给回调函数（通常是双向的 Mithril 流： https://mithril.js.org/stream.html#bidirectional-bindings).
 *
 * 替换了 Mithril 2.0 中的 m.withAttr
 * @see https://mithril.js.org/archive/v0.2.5/mithril.withAttr.html
 */
export default (key: string, cb: Function) =>
  function (this: Element) {
    cb(this.getAttribute(key) || (this as any)[key]);
  };
