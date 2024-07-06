// 基于 MDN 上的 polyfill
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/flat#reduce_concat_isarray_recursivity
//
// 需要为 iOS 12 以下的 Safari 提供支持

// ts-ignored 是因为我们可以在干净的类型定义背后封装一些混乱的逻辑。

if (!Array.prototype['flat']) {
  Array.prototype['flat'] = function flat<A, D extends number = 1>(this: A, depth?: D | unknown): any[] {
    // @ts-ignore 忽略 TypeScript 类型检查，因为我们知道这里逻辑是安全的
    return (depth ?? 1) > 0
      ? // @ts-ignore 忽略 TypeScript 类型检查
        Array.prototype.reduce.call(this, (acc, val): any[] => acc.concat(Array.isArray(val) ? flat.call(val, depth - 1) : val), [])
      : // 如果没有提供 depth 或者 depth 为 0，则直接返回数组的副本
        // 在所有主要浏览器（iOS 8+）中，扩展运算符（...）都是支持的
        // @ts-ignore 忽略 TypeScript 类型检查
        [...this];
  };
}
