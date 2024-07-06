export default class SearchState {
  protected cachedSearches: Set<string>;
  protected value: string = '';

  constructor(cachedSearches: string[] = []) {
    this.cachedSearches = new Set(cachedSearches);
  }

  /**
   * 如果我们正在显示搜索的全部结果（而不仅仅是预览），
   * 这个值应该返回触发该搜索的查询。
   *
   * 在这个通用类中，不支持全页搜索。
   * 这个方法应该由支持全页搜索的子类来实现。
   *
   * @see Search
   */
  getInitialSearch(): string {
    return '';
  }

  getValue(): string {
    return this.value;
  }

  setValue(value: string) {
    this.value = value;
  }

  /**
   * 清除搜索值。
   */
  clear() {
    this.setValue('');
  }

  /**
   * 标记我们已经搜索过这个查询，以便我们不必再次访问该端点。
   */
  cache(query: string) {
    this.cachedSearches.add(query);
  }

  /**
   * 检查此查询是否之前被搜索过。
   */
  isCached(query: string): boolean {
    return this.cachedSearches.has(query);
  }
}
