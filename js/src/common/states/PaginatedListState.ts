import app from '../../common/app';
import Model from '../Model';
import { ApiQueryParamsPlural, ApiResponsePlural } from '../Store';

export interface Page<TModel> {
  number: number;
  items: TModel[];

  hasPrev?: boolean;
  hasNext?: boolean;
}

export interface PaginationLocation {
  page: number;
  startIndex?: number;
  endIndex?: number;
}

export interface PaginatedListParams {
  [key: string]: any;
}

export interface PaginatedListRequestParams extends Omit<ApiQueryParamsPlural, 'include'> {
  include?: string | string[];
}

export default abstract class PaginatedListState<T extends Model, P extends PaginatedListParams = PaginatedListParams> {
  protected location!: PaginationLocation;
  protected pageSize: number;

  protected pages: Page<T>[] = [];
  protected params: P = {} as P;

  protected initialLoading: boolean = false;
  protected loadingPrev: boolean = false;
  protected loadingNext: boolean = false;

  protected constructor(params: P = {} as P, page: number = 1, pageSize: number = 20) {
    this.params = params;

    this.location = { page };
    this.pageSize = pageSize;
  }

  abstract get type(): string;

  public clear(): void {
    this.pages = [];

    m.redraw();
  }

  public loadPrev(): Promise<void> {
    if (this.loadingPrev || this.getLocation().page === 1) return Promise.resolve();

    this.loadingPrev = true;

    const page: number = this.getPrevPageNumber();

    return this.loadPage(page)
      .then(this.parseResults.bind(this, page))
      .finally(() => (this.loadingPrev = false));
  }

  public loadNext(): Promise<void> {
    if (this.loadingNext) return Promise.resolve();

    this.loadingNext = true;

    const page: number = this.getNextPageNumber();

    return this.loadPage(page)
      .then(this.parseResults.bind(this, page))
      .finally(() => (this.loadingNext = false));
  }

  protected parseResults(pg: number, results: ApiResponsePlural<T>): void {
    const pageNum = Number(pg);

    const links = results.payload?.links;
    const page = {
      number: pageNum,
      items: results,
      hasNext: !!links?.next,
      hasPrev: !!links?.prev,
    };

    if (this.isEmpty() || pageNum > this.getNextPageNumber() - 1) {
      this.pages.push(page);
    } else {
      this.pages.unshift(page);
    }

    this.location = { page: pageNum };

    m.redraw();
  }

  /**
   * 加载新的一页结果。
   */
  protected loadPage(page = 1): Promise<ApiResponsePlural<T>> {
    const reqParams = this.requestParams();

    const include = Array.isArray(reqParams.include) ? reqParams.include.join(',') : reqParams.include;

    const params: ApiQueryParamsPlural = {
      ...reqParams,
      page: {
        ...reqParams.page,
        offset: this.pageSize * (page - 1),
      },
      include,
    };

    return app.store.find<T[]>(this.type, params);
  }

  /**
   * 获取应传递给API请求的参数。
   * 除非子类重写了loadPage，否则不要包含页面偏移量。
   *
   * @abstract
   * @see loadPage
   */
  protected requestParams(): PaginatedListRequestParams {
    return this.params;
  }

  /**
   * 更新 `this.params` 对象，如果它们已更改则调用 `refresh` 。
   * 使用 `requestParams` 将 `this.params` 转换为API参数
   *
   * @param newParams
   * @param page
   * @see requestParams
   */
  public refreshParams(newParams: P, page: number): Promise<void> {
    if (this.isEmpty() || this.paramsChanged(newParams)) {
      this.params = newParams;

      return this.refresh(page);
    }

    return Promise.resolve();
  }

  public refresh(page: number = 1): Promise<void> {
    this.initialLoading = true;
    this.loadingPrev = false;
    this.loadingNext = false;

    this.clear();

    this.location = { page };

    return this.loadPage()
      .then((results) => {
        this.pages = [];
        this.parseResults(this.location.page, results);
      })
      .finally(() => (this.initialLoading = false));
  }

  public getPages(): Page<T>[] {
    return this.pages;
  }
  public getLocation(): PaginationLocation {
    return this.location;
  }

  public isLoading(): boolean {
    return this.initialLoading || this.loadingNext || this.loadingPrev;
  }
  public isInitialLoading(): boolean {
    return this.initialLoading;
  }
  public isLoadingPrev(): boolean {
    return this.loadingPrev;
  }
  public isLoadingNext(): boolean {
    return this.loadingNext;
  }

  /**
   * 当所有已加载页面上的项目数量不为0时，返回true。
   *
   * @see isEmpty
   */
  public hasItems(): boolean {
    return !!this.getAllItems().length;
  }

  /**
   * 当没有任何项目且状态已经完成其初始加载时，返回true。
   * 如果您想知道是否有项目，而不考虑加载状态，请使用 `hasItems()` 代替
   *
   * @see hasItems
   */
  public isEmpty(): boolean {
    return !this.isInitialLoading() && !this.hasItems();
  }

  public hasPrev(): boolean {
    return !!this.pages[0]?.hasPrev;
  }
  public hasNext(): boolean {
    return !!this.pages[this.pages.length - 1]?.hasNext;
  }

  /**
   * 获取存储的状态参数。
   */
  public getParams(): P {
    return this.params;
  }

  protected getNextPageNumber(): number {
    const pg = this.pages[this.pages.length - 1]?.number;

    if (pg && !isNaN(pg)) {
      return pg + 1;
    } else {
      return this.location.page;
    }
  }
  protected getPrevPageNumber(): number {
    const pg = this.pages[0]?.number;

    if (pg && !isNaN(pg)) {
      // 如果计算出的页码小于1
      // 返回1作为上一页（可能的第一页页码）
      return Math.max(pg - 1, 1);
    } else {
      return this.location.page;
    }
  }

  protected paramsChanged(newParams: P): boolean {
    return Object.keys(newParams).some((key) => this.getParams()[key] !== newParams[key]);
  }

  protected getAllItems(): T[] {
    return this.getPages()
      .map((pg) => pg.items)
      .flat();
  }
}
