import Model from '../Model';

/**
 * `computed` 辅助工具创建一个函数，该函数将缓存其输出，直到任何依赖值变为“脏”状态。

 *
 * @param dependentKeys 依赖值的键
 * @param compute 使用依赖值计算值的函数
 */
export default function computed<T, M = Model>(...args: [...string[], (this: M, ...args: unknown[]) => T]): () => T {
  const keys = args.slice(0, -1) as string[];
  const compute = args.slice(-1)[0] as (this: M, ...args: unknown[]) => T;

  const dependentValues: Record<string, unknown> = {};
  let computedValue: T;

  return function (this: M) {
    let recompute = false;

    // 读取所有依赖值。如果其中任何一个自上次以来已更改，
    // 则我们将需要重新计算我们的输出
    keys.forEach((key) => {
      const attr = (this as Record<string, unknown | (() => unknown)>)[key];
      const value = typeof attr === 'function' ? attr.call(this) : attr;

      if (dependentValues[key] !== value) {
        recompute = true;
        dependentValues[key] = value;
      }
    });

    if (recompute) {
      computedValue = compute.apply(
        this,
        keys.map((key) => dependentValues[key])
      );
    }

    return computedValue;
  };
}
