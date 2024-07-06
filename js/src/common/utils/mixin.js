/**
 * `mixin` 工具函数将一组 'mixin' 对象的属性分配给父对象的原型。
 *
 * @example
 * class MyClass extends mixin(ExistingClass, evented, etc) {}
 *
 * @param {object} Parent 从哪个类扩展新类
 * @param {Record<string, any>[]} mixins 要混入的对象数组
 * @return {object} 一个新的类，它继承自 Parent 并包含 mixins
 */
export default function mixin(Parent, ...mixins) {
  class Mixed extends Parent {}

  mixins.forEach((object) => {
    Object.assign(Mixed.prototype, object);
  });

  return Mixed;
}
