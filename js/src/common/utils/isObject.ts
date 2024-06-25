/**
 * 判断传入的值是否为对象。
 *
 * 在这个上下文中，"object" 对象指的是任何非原始值，包括数组、函数、映射、日期等。
 *
 * @example
 * isObject({}); // true
 * @example
 * isObject([]); // true
 * @example
 * isObject(function () {}); // true
 * @example
 * isObject(Object(1)); // true
 * @example
 * isObject(null); // false
 * @example
 * isObject(1); // false
 * @example
 * isObject("hello world"); // false
 *
 * @see https://github.com/jashkenas/underscore/blob/943977e34e2279503528a71ddcc2dd5f96483945/underscore.js#L87-L91
 */
export default function isObject(obj: unknown): obj is object {
  const type = typeof obj;
  return type === 'function' || (type === 'object' && !!obj);
}
