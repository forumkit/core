import app from '../../site/app';
import Component, { ComponentAttrs } from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import extractText from '../../common/utils/extractText';
import KeyboardNavigatable from '../../common/utils/KeyboardNavigatable';
import icon from '../../common/helpers/icon';
import SearchState from '../states/SearchState';
import DiscussionsSearchSource from './DiscussionsSearchSource';
import UsersSearchSource from './UsersSearchSource';
import { fireDeprecationWarning } from '../../common/helpers/fireDebugWarning';
import type Mithril from 'mithril';

/**
 * `SearchSource` 接口定义了搜索下拉框中的搜索结果部分。
 *
 * 搜索源应通过扩展 `sourceItems` 方法在 `Search` 组件类中注册。当用户输入查询时，每个搜索源将被提示通过 `search` 方法加载搜索结果。当下拉框重新绘制时，它将通过组合每个源的 `view` 方法的输出来构建。
 */
export interface SearchSource {
  /**
   * 发起请求以获取给定查询的结果。
   * 结果将在搜索源内部更新，不暴露。
   */
  search(query: string): Promise<void>;

  /**
   * 获取一个虚拟的 <li>s 数组，用于列出给定查询的搜索结果。
   */
  view(query: string): Array<Mithril.Vnode>;
}

export interface SearchAttrs extends ComponentAttrs {
  /** 这是警报的类型。将用于给警报赋予类名 `Alert--{type}` */
  state: SearchState;
}

/**
 * `Search` 组件会显示一个下拉菜单，其中列出了从各种来源的实时键入结果。
 *
 * 如果应用程序的搜索状态的 getInitialSearch() 值为真值，则搜索框将被 'activated' 。如果是这种情况，将在搜索框旁边显示一个 'x' 按钮，点击它将清除搜索。
 *
 * ATTRS（属性）
 *
 * - state: SearchState 实例
 */
export default class Search<T extends SearchAttrs = SearchAttrs> extends Component<T, SearchState> {
  /**
   * 在开始搜索之前查询的最小长度。
   */
  protected static MIN_SEARCH_LEN = 2;

  /**
   * 此组件的  `SearchState` 实例。
   */
  protected searchState!: SearchState;

  /**
   * 此组件的  `SearchState` 实例。
   *
   * @deprecated 请改用`this.searchState` 属性。
   */
  get state() {
    fireDeprecationWarning('Search 组件的`state` 属性已弃用', '3212');
    return this.searchState;
  }
  set state(state: SearchState) {
    // 这是一个权宜之计，以防止在 Mithril 创建组件时将状态设置为 undefined 时触发弃用警告
    state !== undefined && fireDeprecationWarning('`state` 属性已弃用', '3212');
    this.searchState = state;
  }

  /**
   * 搜索输入框是否获得焦点。
   */
  protected hasFocus = false;

  /**
   * SearchSource 数组。
   */
  protected sources?: SearchSource[];

  /**
   * 仍在加载结果的源的数量。
   */
  protected loadingSources = 0;

  /**
   * 当前在结果列表中选中的 <li> 的索引。这可以是一个唯一的字符串（以考虑新结果加载时项的位置可能会发生变化），否则它将是数字（列表中的顺序位置）。
   */
  protected index: number = 0;

  protected navigator!: KeyboardNavigatable;

  protected searchTimeout?: number;

  private updateMaxHeightHandler?: () => void;

  oninit(vnode: Mithril.Vnode<T, this>) {
    super.oninit(vnode);

    this.searchState = this.attrs.state;
  }

  view() {
    // 获取当前的搜索查询（如果初始搜索被激活）
    const currentSearch = this.searchState.getInitialSearch();

    // 在视图中初始化搜索源，而不是在构造函数中，
    // 以便我们可以访问到 app.site（或其他可能在运行时确定的依赖项）。
    // 如果 this.sources 还没有被定义，那么我们将通过调用 this.sourceItems().toArray() 来初始化它。
    if (!this.sources) this.sources = this.sourceItems().toArray();

    // 如果没有加载任何搜索源，则隐藏搜索视图
    // 这里通过返回一个空的 div 来表示视图为空
    if (!this.sources.length) return <div></div>;

    const searchLabel = extractText(app.translator.trans('core.site.header.search_placeholder'));

    const isActive = !!currentSearch;
    const shouldShowResults = !!(this.searchState.getValue() && this.hasFocus);
    const shouldShowClearButton = !!(!this.loadingSources && this.searchState.getValue());

    return (
      <div
        role="search"
        aria-label={app.translator.trans('core.site.header.search_role_label')}
        className={classList('Search', {
          open: this.searchState.getValue() && this.hasFocus,
          focused: this.hasFocus,
          active: isActive,
          loading: !!this.loadingSources,
        })}
      >
        <div className="Search-input">
          <input
            aria-label={searchLabel}
            className="FormControl"
            type="search"
            placeholder={searchLabel}
            value={this.searchState.getValue()}
            oninput={(e: InputEvent) => this.searchState.setValue((e?.target as HTMLInputElement)?.value)}
            onfocus={() => (this.hasFocus = true)}
            onblur={() => (this.hasFocus = false)}
          />
          {!!this.loadingSources && <LoadingIndicator size="small" display="inline" containerClassName="Button Button--icon Button--link" />}
          {shouldShowClearButton && (
            <button
              className="Search-clear Button Button--icon Button--link"
              onclick={this.clear.bind(this)}
              aria-label={app.translator.trans('core.site.header.search_clear_button_accessible_label')}
              type="button"
            >
              {icon('fas fa-times-circle')}
            </button>
          )}
        </div>
        <ul
          className="Dropdown-menu Search-results"
          aria-hidden={!shouldShowResults || undefined}
          aria-live={shouldShowResults ? 'polite' : undefined}
        >
          {shouldShowResults && this.sources.map((source) => source.view(this.searchState.getValue()))}
        </ul>
      </div>
    );
  }

  updateMaxHeight() {
    // 由于扩展可能在手机端搜索框上方添加元素，
    // 因此我们需要动态地计算和设置最大高度。
    const resultsElementMargin = 14;
    const maxHeight =
      window.innerHeight - this.element.querySelector('.Search-input>.FormControl')!.getBoundingClientRect().bottom - resultsElementMargin;

    this.element.querySelector<HTMLElement>('.Search-results')?.style?.setProperty('max-height', `${maxHeight}px`);
  }

  onupdate(vnode: Mithril.VnodeDOM<T, this>) {
    super.onupdate(vnode);

    // 高亮当前选中的项目。
    this.setIndex(this.getCurrentNumericIndex());

    // 如果没有搜索源，则不显示搜索视图。
    if (!this.sources?.length) return;

    this.updateMaxHeight();
  }

  oncreate(vnode: Mithril.VnodeDOM<T, this>) {
    super.oncreate(vnode);

    // 如果没有搜索源，我们不应该初始化搜索元素的逻辑，因为它们将不会被显示。
    if (!this.sources?.length) return;

    const search = this;
    const state = this.searchState;

    // 高亮当前选中的项目。
    this.setIndex(this.getCurrentNumericIndex());

    this.$('.Search-results')
      .on('mousedown', (e) => e.preventDefault())
      .on('click', () => this.$('input').trigger('blur'))

      // 当鼠标悬停在搜索结果列表项（非 Dropdown-header）上时，高亮显示该项
      // 这里使用了 jQuery 的委托事件绑定，仅针对 .Search-results 下的直接子元素 li（且非 Dropdown-header 类）
      .on('mouseenter', '> li:not(.Dropdown-header)', function () {
        search.setIndex(search.selectableItems().index(this));
      });

    const $input = this.$('input') as JQuery<HTMLInputElement>;

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.selectResult.bind(this), true)
      .onCancel(this.clear.bind(this))
      .bindTo($input);

    // 处理搜索输入框的键盘事件，触发结果加载（但这里代码被截断了，所以我们不知道具体实现）
    $input
      .on('input focus', function () {
        const query = this.value.toLowerCase();

        if (!query) return;

        if (search.searchTimeout) clearTimeout(search.searchTimeout);
        search.searchTimeout = window.setTimeout(() => {
          if (state.isCached(query)) return;

          if (query.length >= (search.constructor as typeof Search).MIN_SEARCH_LEN) {
            search.sources?.map((source) => {
              if (!source.search) return;

              search.loadingSources++;

              source.search(query).then(() => {
                search.loadingSources = Math.max(0, search.loadingSources - 1);
                m.redraw();
              });
            });
          }

          state.cache(query);
          m.redraw();
        }, 250);
      })

      .on('focus', function () {
        $(this)
          .one('mouseup', (e) => e.preventDefault())
          .trigger('select');
      });

    this.updateMaxHeightHandler = this.updateMaxHeight.bind(this);
    window.addEventListener('resize', this.updateMaxHeightHandler);
  }

  onremove(vnode: Mithril.VnodeDOM<T, this>) {
    super.onremove(vnode);

    if (this.updateMaxHeightHandler) {
      window.removeEventListener('resize', this.updateMaxHeightHandler);
    }
  }

  /**
   * 导航到当前选定的搜索结果并关闭列表。
   */
  selectResult() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);

    this.loadingSources = 0;

    const selectedUrl = this.getItem(this.index).find('a').attr('href');
    if (this.searchState.getValue() && selectedUrl) {
      m.route.set(selectedUrl);
    } else {
      this.clear();
    }

    this.$('input').blur();
  }

  /**
   * 清除搜索
   */
  clear() {
    this.searchState.clear();
  }

  /**
   * 生成 SearchSources 的项列表。
   */
  sourceItems(): ItemList<SearchSource> {
    const items = new ItemList<SearchSource>();

    if (app.site.attribute('canViewSite')) items.add('discussions', new DiscussionsSearchSource());
    if (app.site.attribute('canSearchUsers')) items.add('users', new UsersSearchSource());

    return items;
  }

  /**
   * 获取所有可选择的搜索结果项。
   */
  selectableItems(): JQuery {
    return this.$('.Search-results > li:not(.Dropdown-header)');
  }

  /**
   * 获取当前选中的搜索结果项的位置。
   * 如果未找到，则返回0。
   */
  getCurrentNumericIndex(): number {
    return Math.max(0, this.selectableItems().index(this.getItem(this.index)));
  }

  /**
   * 根据给定的索引（数字或命名）获取搜索结果中的元素。
   */
  getItem(index: number): JQuery {
    const $items = this.selectableItems();
    let $item = $items.filter(`[data-index="${index}"]`);

    if (!$item.length) {
      $item = $items.eq(index);
    }

    return $item;
  }

  /**
   * 将当前选中的搜索结果项设置为具有给定索引的项。
   */
  setIndex(index: number, scrollToItem: boolean = false) {
    const $items = this.selectableItems();
    const $dropdown = $items.parent();

    let fixedIndex = index;
    if (index < 0) {
      fixedIndex = $items.length - 1;
    } else if (index >= $items.length) {
      fixedIndex = 0;
    }

    const $item = $items.removeClass('active').eq(fixedIndex).addClass('active');

    this.index = parseInt($item.attr('data-index') as string) || fixedIndex;

    if (scrollToItem) {
      const dropdownScroll = $dropdown.scrollTop()!;
      const dropdownTop = $dropdown.offset()!.top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight()!;
      const itemTop = $item.offset()!.top;
      const itemBottom = itemTop + $item.outerHeight()!;

      let scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({ scrollTop }, 100);
      }
    }
  }
}
