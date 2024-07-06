import subclassOf from '../../common/utils/subclassOf';

export default class PageState {
  constructor(type, data = {}) {
    this.type = type;
    this.data = data;
  }

  /**
   * 确定页面是否与给定的类和数据匹配。
   *
   * @param {object} type 要检查的页面类。也接受子类。
   * @param {Record<string, unknown>} data
   * @return {boolean}
   */
  matches(type, data = {}) {
    // 如果页面类型不同，则提前失败
    if (!subclassOf(this.type, type)) return false;

    // 现在已知类型是正确的，我们遍历提供的数据以查看其是否与状态中的数据匹配。
    return Object.keys(data).every((key) => this.data[key] === data[key]);
  }

  get(key) {
    return this.data[key];
  }

  set(key, value) {
    this.data[key] = value;
  }
}
