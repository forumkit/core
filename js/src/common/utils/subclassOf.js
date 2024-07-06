/**
 * 检查类A是否与类B相同，或者类A是否是类B的子类
 */
export default function subclassOf(A, B) {
  return A && (A === B || A.prototype instanceof B);
}
