import app from '../../site/app';
import setRouteWithForcedRefresh from '../../common/utils/setRouteWithForcedRefresh';
import SearchState from './SearchState';

type SearchParams = Record<string, string>;

export default class GlobalSearchState extends SearchState {
  private initialValueSet = false;

  constructor(cachedSearches = []) {
    super(cachedSearches);
  }

  getValue(): string {
    // 如果我们当前在搜索结果页面上，我们应该从当前的搜索中初始化值（如果存在的话）。
    // 我们不能在构造函数中这样做，因为这个类在页面渲染之前就被实例化了，我们需要使用 app.current。
    if (!this.initialValueSet && this.currPageProvidesSearch()) {
      this.intializeValue();
    }

    return super.getValue();
  }

  protected intializeValue() {
    this.setValue(this.getInitialSearch());
    this.initialValueSet = true;
  }

  protected currPageProvidesSearch(): boolean {
    return app.current.type && app.current.type.providesInitialSearch;
  }

  /**
   * @inheritdoc
   */
  getInitialSearch(): string {
    return this.currPageProvidesSearch() ? this.params().q : '';
  }

  /**
   * 清除搜索输入和当前控制器的活动搜索。
   */
  clear() {
    super.clear();

    if (this.getInitialSearch()) {
      this.clearInitialSearch();
    } else {
      m.redraw();
    }
  }

  /**
   * 重定向到没有搜索过滤器的索引页面。当点击头部搜索框中的'x'时调用此方法。
   */
  protected clearInitialSearch() {
    const { q, ...params } = this.params();

    setRouteWithForcedRefresh(app.route(app.current.get('routeName'), params));
  }

  /**
   * 获取在过滤器更改之间保持不变的URL参数。
   *
   * 这可以用于生成清除过滤器的链接。
   */
  stickyParams(): SearchParams {
    return {
      sort: m.route.param('sort'),
      q: m.route.param('q'),
    };
  }

  /**
   * 获取在当前页面中要使用的参数。
   */
  params(): SearchParams {
    const params = this.stickyParams();

    params.filter = m.route.param('filter');

    return params;
  }

  /**
   * 使用给定的排序参数重定向到索引页面。
   */
  changeSort(sort: string) {
    const params = this.params();

    if (sort === Object.keys(app.discussions.sortMap())[0]) {
      delete params.sort;
    } else {
      params.sort = sort;
    }

    setRouteWithForcedRefresh(app.route(app.current.get('routeName'), params));
  }
}
