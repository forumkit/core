/**
 * 通过每次调用时将其输出传递给一个修改回调来扩展对象的方法。
 *
 * 回调接受方法的返回值，并应直接在此值上执行任何修改。因此，此函数对返回标量值（数字、字符串、布尔值）的方法将不会有效。
 *
 * 应注意扩展正确的对象——在大多数情况下，类的原型将是期望的扩展目标，而不是类本身。
 *
 * @example <caption> 扩展一个方法的示例用法。</caption>
 * extend(Discussion.prototype, 'badges', function(badges) {
 *   // 对 `badges` 进行某些操作
 * });
 *
 * @example <caption>扩展多个方法的示例用法。</caption>
 * extend(IndexPage.prototype, ['oncreate', 'onupdate'], function(vnode) {
 *   // 在创建和更新时需要运行的某些操作
 * });
 *
 * @param object 拥有该方法的对象
 * @param methods 要扩展的方法的名称或名称数组
 * @param callback 修改方法输出的回调
 */
export function extend<T extends Record<string, any>, K extends KeyOfType<T, Function>>(
  object: T,
  methods: K | K[],
  callback: (this: T, val: ReturnType<T[K]>, ...args: Parameters<T[K]>) => void
) {
  const allMethods = Array.isArray(methods) ? methods : [methods];

  allMethods.forEach((method: K) => {
    const original: Function | undefined = object[method];

    object[method] = function (this: T, ...args: Parameters<T[K]>) {
      const value = original ? original.apply(this, args) : undefined;

      callback.apply(this, [value, ...args]);

      return value;
    } as T[K];

    Object.assign(object[method], original);
  });
}

/**
 * 通过用一个新函数替换对象的方法来重写该方法，以便在每次调用对象的方法时都会运行新函数。
 *
 * 替换函数接受原始方法作为其第一个参数，这类似于对 `super` 的调用。传递给原始方法的任何参数也会传递给替换函数。
 *
 * 应注意扩展正确的对象——在大多数情况下，类的原型将是期望的扩展目标，而不是类本身。
 *
 * @example <caption>重写一个方法的示例用法。</caption>
 * override(Discussion.prototype, 'badges', function(original) {
 *   const badges = original();
 *   // 对 badges 进行某些操作
 *   return badges;
 * });
 *
 * @example <caption>重写多个方法的示例用法。</caption>
 * extend(Discussion.prototype, ['oncreate', 'onupdate'], function(original, vnode) {
 *   // 在创建和更新时需要运行的某些操作
 * });
 *
 * @param object  拥有该方法的对象
 * @param methods 要重写的方法的名称或名称数组
 * @param newMethod 替换该方法的新方法
 */
export function override<T extends Record<any, any>, K extends KeyOfType<T, Function>>(
  object: T,
  methods: K | K[],
  newMethod: (this: T, orig: T[K], ...args: Parameters<T[K]>) => void
) {
  const allMethods = Array.isArray(methods) ? methods : [methods];

  allMethods.forEach((method) => {
    const original: Function = object[method];

    object[method] = function (this: T, ...args: Parameters<T[K]>) {
      return newMethod.apply(this, [original.bind(this), ...args]);
    } as T[K];

    Object.assign(object[method], original);
  });
}
