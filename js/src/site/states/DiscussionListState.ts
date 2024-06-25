import app from '../../site/app';
import PaginatedListState, { Page, PaginatedListParams, PaginatedListRequestParams } from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';
import { ApiResponsePlural } from '../../common/Store';
import EventEmitter from '../../common/utils/EventEmitter';

export interface DiscussionListParams extends PaginatedListParams {
  sort?: string;
}

const globalEventEmitter = new EventEmitter();

export default class DiscussionListState<P extends DiscussionListParams = DiscussionListParams> extends PaginatedListState<Discussion, P> {
  protected extraDiscussions: Discussion[] = [];
  protected eventEmitter: EventEmitter;

  constructor(params: P, page: number = 1) {
    super(params, page, 20);

    this.eventEmitter = globalEventEmitter.on('discussion.deleted', this.deleteDiscussion.bind(this));
  }

  get type(): string {
    return 'discussions';
  }

  requestParams(): PaginatedListRequestParams {
    const params = {
      include: ['user', 'lastPostedUser'],
      filter: this.params.filter || {},
      sort: this.sortMap()[this.params.sort ?? ''],
    };

    if (this.params.q) {
      params.filter.q = this.params.q;
      params.include.push('mostRelevantPost', 'mostRelevantPost.user');
    }

    return params;
  }

  protected loadPage(page: number = 1): Promise<ApiResponsePlural<Discussion>> {
    const preloadedDiscussions = app.preloadedApiDocument<Discussion[]>();

    if (preloadedDiscussions) {
      this.initialLoading = false;

      return Promise.resolve(preloadedDiscussions);
    }

    return super.loadPage(page);
  }

  clear(): void {
    super.clear();

    this.extraDiscussions = [];
  }

  /**
   * 获取一个映射表，其中包含了排序关键字（出现在URL中，用于翻译）到它们所代表的API排序值的映射。
   */
  sortMap() {
    const map: any = {};

    if (this.params.q) {
      map.relevance = '';
    }
    map.latest = '-lastPostedAt';
    map.top = '-commentCount';
    map.newest = '-createdAt';
    map.oldest = 'createdAt';

    return map;
  }

  /**
   * 在上一次请求中，用户是否搜索了讨论？
   */
  isSearchResults(): boolean {
    return !!this.params.q;
  }

  removeDiscussion(discussion: Discussion): void {
    this.eventEmitter.emit('discussion.deleted', discussion);
  }

  deleteDiscussion(discussion: Discussion): void {
    for (const page of this.pages) {
      const index = page.items.indexOf(discussion);

      if (index !== -1) {
        page.items.splice(index, 1);
        break;
      }
    }

    const index = this.extraDiscussions.indexOf(discussion);

    if (index !== -1) {
      this.extraDiscussions.splice(index);
    }

    m.redraw();
  }

  /**
   * 将讨论添加到列表的顶部。
   */
  addDiscussion(discussion: Discussion): void {
    this.removeDiscussion(discussion);
    this.extraDiscussions.unshift(discussion);

    m.redraw();
  }

  protected getAllItems(): Discussion[] {
    return this.extraDiscussions.concat(super.getAllItems());
  }

  public getPages(): Page<Discussion>[] {
    const pages = super.getPages();

    if (this.extraDiscussions.length) {
      return [
        {
          number: -1,
          items: this.extraDiscussions,
        },
        ...pages,
      ];
    }

    return pages;
  }
}
