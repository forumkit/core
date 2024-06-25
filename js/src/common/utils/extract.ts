/**
 * `extract` 实用工具从对象中删除一个属性并返回其值。
 *
 * @param object 拥有该属性的对象
 * @param property 要提取的属性的名称
 * @return 该属性的值
 */
export default function extract<T, K extends keyof T>(object: T, property: K): T[K] {
  const value = object[property];

  delete object[property];

  return value;
}
