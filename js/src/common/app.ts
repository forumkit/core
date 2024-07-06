import type Application from './Application';

// 用来修正类型检查
const w = window as any; // 将window对象强制转换为any类型，以便进行后续操作

/**
 * 代理app对象。Common JS（公共JS）首先运行，此时 `window.app` 尚未设置，因为这是由命名空间JS来完成的。
 *
 * 当设置了正确的值之后，如果直接通过变量引用来访问，可能仍会保留对原始无效值的引用。
 *
 * 通过使用代理，我们可以确保我们的 `window.app` 值始终与最新的引用保持同步。
 */
const appProxy = new Proxy(
  {},
  {
    get(_, properties) {
      return Reflect.get(w.app, properties, w.app);
    },
    set(_, properties, value) {
      return Reflect.set(w.app, properties, value, w.app);
    },
  }
);

/**
 * 在公共命名空间中的Application实例。
 */
export default appProxy as Application;
