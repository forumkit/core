import Mithril from 'mithril';

import app from '../../admin/app';

import EditUserModal from '../../common/components/EditUserModal';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';

import icon from '../../common/helpers/icon';
import listItems from '../../common/helpers/listItems';

import type User from '../../common/models/User';
import type { IPageAttrs } from '../../common/components/Page';

import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import extractText from '../../common/utils/extractText';
import AdminPage from './AdminPage';
import { debounce } from '../../common/utils/throttleDebounce';
import CreateUserModal from './CreateUserModal';

type ColumnData = {
  /**
   * 列标题
   */
  name: Mithril.Children;
  /**
   * 为此列显示的组件（或组件数组）。
   */
  content: (user: User) => Mithril.Children;
};

/**
 * 管理员页面，用于分页显示网站上的所有用户列表。
 */
export default class UserListPage extends AdminPage {
  private query: string = '';
  private throttledSearch = debounce(250, () => this.loadPage(0));

  /**
   * 每页加载的用户数量。
   */
  private numPerPage: number = 50;

  /**
   * 当前页码（从0开始）。
   */
  private pageNumber: number = 0;

  /**
   * 正在加载的页码（从0开始）。
   */
  private loadingPageNumber: number = 0;

  /**
   * 网站用户总数。
   *
   * 从活动的`AdminApplication` (`app`) 中获取，
   * 数据由 `AdminPayload.php` 提供，或者如果安装了 `forumkit/statistics` 插件则由其提供。
   */
  readonly userCount: number = app.data.modelStatistics.users.total;

  /**
   * 获取用户总页数。
   */
  private getTotalPageCount(): number {
    if (this.userCount === -1) return 0;

    return Math.ceil(this.userCount / this.numPerPage);
  }

  /**
   * 当前页面的用户数组。
   *
   * 页面加载时未定义，因为没有数据被获取。
   */
  private pageData: User[] | undefined = undefined;

  /**
   * 是否有更多用户数据可用？
   */
  private moreData: boolean = false;

  private isLoadingPage: boolean = false;

  oninit(vnode: Mithril.Vnode<IPageAttrs, this>) {
    super.oninit(vnode);

    // 从URL中获取页码参数
    const page = parseInt(m.route.param('page'));

    if (isNaN(page) || page < 1) {
      this.setPageNumberInUrl(1);
      this.pageNumber = 0;
    } else {
      this.pageNumber = page - 1;
    }

    this.loadingPageNumber = this.pageNumber;
  }

  /**
   * 要渲染的组件。
   */
  content() {
    if (typeof this.pageData === 'undefined') {
      this.loadPage(this.pageNumber);

      return [
        <section className="UserListPage-grid UserListPage-grid--loading">
          <LoadingIndicator containerClassName="LoadingIndicator--block" size="large" />
        </section>,
      ];
    }

    const columns = this.columns().toArray();

    return [
      <div className="UserListPage-header">{this.headerItems().toArray()}</div>,
      <section
        className={classList(['UserListPage-grid', this.isLoadingPage ? 'UserListPage-grid--loadingPage' : 'UserListPage-grid--loaded'])}
        style={{ '--columns': columns.length }}
        role="table"
        // +1 加上1以考虑表头
        aria-rowcount={this.pageData.length + 1}
        aria-colcount={columns.length}
        aria-live="polite"
        aria-busy={this.isLoadingPage ? 'true' : 'false'}
      >
        {/* 渲染列标题 */}
        {columns.map((column, colIndex) => (
          <div className="UserListPage-grid-header" role="columnheader" aria-colindex={colIndex + 1} aria-rowindex={1}>
            {column.name}
          </div>
        ))}

        {/* 渲染用户数据 */}
        {this.pageData.map((user, rowIndex) =>
          columns.map((col, colIndex) => {
            const columnContent = col.content && col.content(user);

            return (
              <div
                className={classList(['UserListPage-grid-rowItem', rowIndex % 2 > 0 && 'UserListPage-grid-rowItem--shaded'])}
                data-user-id={user.id()}
                data-column-name={col.itemName}
                aria-colindex={colIndex + 1}
                // +2 加2是为了考虑从0开始的索引以及表头行
                aria-rowindex={rowIndex + 2}
                role="cell"
              >
                {columnContent || app.translator.trans('core.admin.users.grid.invalid_column_content')}
              </div>
            );
          })
        )}

        {/* 当加载新页面时显示的加载指示器（旋转图标） */}
        {this.isLoadingPage && <LoadingIndicator size="large" />}
      </section>,
      <nav className="UserListPage-gridPagination">
        <Button
          disabled={this.pageNumber === 0}
          title={app.translator.trans('core.admin.users.pagination.first_page_button')}
          onclick={this.goToPage.bind(this, 1)}
          icon="fas fa-step-backward"
          className="Button Button--icon UserListPage-firstPageBtn"
        />
        <Button
          disabled={this.pageNumber === 0}
          title={app.translator.trans('core.admin.users.pagination.back_button')}
          onclick={this.previousPage.bind(this)}
          icon="fas fa-chevron-left"
          className="Button Button--icon UserListPage-backBtn"
        />
        <span className="UserListPage-pageNumber">
          {app.translator.trans('core.admin.users.pagination.page_counter', {
            // https://technology.blog.gov.uk/2020/02/24/why-the-gov-uk-design-system-team-changed-the-input-type-for-numbers/
            current: (
              <input
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                value={this.loadingPageNumber + 1}
                aria-label={extractText(app.translator.trans('core.admin.users.pagination.go_to_page_textbox_a11y_label'))}
                autocomplete="off"
                className="FormControl UserListPage-pageNumberInput"
                onchange={(e: InputEvent) => {
                  const target = e.target as HTMLInputElement;
                  let pageNumber = parseInt(target.value);

                  if (isNaN(pageNumber)) {
                    // 如果页码是无效值（不是一个数字），则重置为当前页码
                    target.value = (this.pageNumber + 1).toString();
                    return;
                  }

                  if (pageNumber < 1) {
                    // 如果页码小于1（即用户可能输入了0或负数），则将其设置为1
                    pageNumber = 1;
                  } else if (pageNumber > this.getTotalPageCount()) {
                    // 如果页码大于总页数，则将其设置为总页数
                    pageNumber = this.getTotalPageCount();
                  }

                  target.value = pageNumber.toString();

                  this.goToPage(pageNumber);
                }}
              />
            ),
            currentNum: this.pageNumber + 1,
            total: this.getTotalPageCount(),
          })}
        </span>
        <Button
          disabled={!this.moreData}
          title={app.translator.trans('core.admin.users.pagination.next_button')}
          onclick={this.nextPage.bind(this)}
          icon="fas fa-chevron-right"
          className="Button Button--icon UserListPage-nextBtn"
        />
        <Button
          disabled={!this.moreData}
          title={app.translator.trans('core.admin.users.pagination.last_page_button')}
          onclick={this.goToPage.bind(this, this.getTotalPageCount())}
          icon="fas fa-step-forward"
          className="Button Button--icon UserListPage-lastPageBtn"
        />
      </nav>,
    ];
  }

  headerItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'search',
      <div className="Search-input">
        <input
          className="FormControl SearchBar"
          type="search"
          placeholder={app.translator.trans('core.admin.users.search_placeholder')}
          oninput={(e: InputEvent) => {
            this.isLoadingPage = true;
            this.query = (e?.target as HTMLInputElement)?.value;
            this.throttledSearch();
          }}
        />
      </div>,
      100
    );

    items.add(
      'totalUsers',
      <p class="UserListPage-totalUsers">{app.translator.trans('core.admin.users.total_users', { count: this.userCount })}</p>,
      90
    );

    items.add('actions', <div className="UserListPage-actions">{this.actionItems().toArray()}</div>, 80);

    return items;
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'createUser',
      <Button className="Button UserListPage-createUserBtn" icon="fas fa-user-plus" onclick={() => app.modal.show(CreateUserModal)}>
        {app.translator.trans('core.admin.users.create_user_button')}
      </Button>,
      100
    );

    return items;
  }

  /**
   * 构建要显示给每个用户的列项列表。
   *
   *列表中的每个列都应该是一个对象，具有 name 和 content 两个键。
   *
   * `name` 是一个字符串，将用作列名。
   * `content` 是一个函数，其第一个也是唯一的参数是User模型。
   *
   * 请参考 `UserListPage.tsx` 中的示例。
   */
  columns(): ItemList<ColumnData> {
    const columns = new ItemList<ColumnData>();

    columns.add(
      'id',
      {
        name: app.translator.trans('core.admin.users.grid.columns.user_id.title'),
        content: (user: User) => user.id() ?? null,
      },
      100
    );

    columns.add(
      'username',
      {
        name: app.translator.trans('core.admin.users.grid.columns.username.title'),
        content: (user: User) => {
          const profileUrl = `${app.site.attribute('baseUrl')}/@${user.slug()}`;

          return (
            <a
              target="_blank"
              href={profileUrl}
              title={extractText(app.translator.trans('core.admin.users.grid.columns.username.profile_link_tooltip', { username: user.username() }))}
            >
              {user.username()}
            </a>
          );
        },
      },
      90
    );

    columns.add(
      'displayName',
      {
        name: app.translator.trans('core.admin.users.grid.columns.display_name.title'),
        content: (user: User) => user.displayName(),
      },
      85
    );

    columns.add(
      'joinDate',
      {
        name: app.translator.trans('core.admin.users.grid.columns.join_time.title'),
        content: (user: User) => (
          <span className="UserList-joinDate" title={user.joinTime()}>
            {dayjs(user.joinTime()).format('LLL')}
          </span>
        ),
      },
      80
    );

    columns.add(
      'groupBadges',
      {
        name: app.translator.trans('core.admin.users.grid.columns.group_badges.title'),
        content: (user: User) => {
          const badges = user.badges().toArray();

          if (badges.length) {
            return <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>;
          } else {
            return app.translator.trans('core.admin.users.grid.columns.group_badges.no_badges');
          }
        },
      },
      70
    );

    columns.add(
      'emailAddress',
      {
        name: app.translator.trans('core.admin.users.grid.columns.email.title'),
        content: (user: User) => {
          function setEmailVisibility(visible: boolean) {
            // 获取所需的 jQuery 元素引用
            const emailContainer = $(`[data-column-name=emailAddress][data-user-id=${user.id()}] .UserList-email`);
            const emailAddress = emailContainer.find('.UserList-emailAddress');
            const emailToggleButton = emailContainer.find('.UserList-emailIconBtn');
            const emailToggleButtonIcon = emailToggleButton.find('.icon');

            emailToggleButton.attr(
              'title',
              extractText(
                visible
                  ? app.translator.trans('core.admin.users.grid.columns.email.visibility_hide')
                  : app.translator.trans('core.admin.users.grid.columns.email.visibility_show')
              )
            );

            emailAddress.attr('aria-hidden', visible ? 'false' : 'true');

            if (visible) {
              emailToggleButtonIcon.addClass('fa-eye');
              emailToggleButtonIcon.removeClass('fa-eye-slash');
            } else {
              emailToggleButtonIcon.removeClass('fa-eye');
              emailToggleButtonIcon.addClass('fa-eye-slash');
            }

            // 需要使用字符串插值来避免 TypeScript 错误。
            emailContainer.attr('data-email-shown', `${visible}`);
          }

          function toggleEmailVisibility() {
            const emailContainer = $(`[data-column-name=emailAddress][data-user-id=${user.id()}] .UserList-email`);
            const emailShown = emailContainer.attr('data-email-shown') === 'true';

            if (emailShown) {
              setEmailVisibility(false);
            } else {
              setEmailVisibility(true);
            }
          }

          return (
            <div className="UserList-email" key={user.id()} data-email-shown="false">
              <span className="UserList-emailAddress" aria-hidden="true" onclick={() => setEmailVisibility(true)}>
                {user.email()}
              </span>
              <button
                onclick={toggleEmailVisibility}
                className="Button Button--text UserList-emailIconBtn"
                title={app.translator.trans('core.admin.users.grid.columns.email.visibility_show')}
              >
                {icon('far fa-eye-slash fa-fw', { className: 'icon' })}
              </button>
            </div>
          );
        },
      },
      70
    );

    columns.add(
      'editUser',
      {
        name: app.translator.trans('core.admin.users.grid.columns.edit_user.title'),
        content: (user: User) => (
          <Button
            className="Button UserList-editModalBtn"
            title={app.translator.trans('core.admin.users.grid.columns.edit_user.tooltip', { username: user.username() })}
            onclick={() => app.modal.show(EditUserModal, { user })}
          >
            {app.translator.trans('core.admin.users.grid.columns.edit_user.button')}
          </Button>
        ),
      },
      -90
    );

    return columns;
  }

  headerInfo() {
    return {
      className: 'UserListPage',
      icon: 'fas fa-users',
      title: app.translator.trans('core.admin.users.title'),
      description: app.translator.trans('core.admin.users.description'),
    };
  }

  /**
   * 异步获取下一组要渲染的用户。
   *
   * 返回一个用户数组以及原始的API响应数据。
   *
   * 使用 `this.numPerPage` 作为响应的限制，并自动从 `pageNumber` 计算所需的偏移量。
   *
   * @param pageNumber 要加载和显示的基于零的页码
   */
  async loadPage(pageNumber: number) {
    if (pageNumber < 0) pageNumber = 0;

    this.loadingPageNumber = pageNumber;
    this.setPageNumberInUrl(pageNumber + 1);

    app.store
      .find<User[]>('users', {
        filter: { q: this.query },
        page: {
          limit: this.numPerPage,
          offset: pageNumber * this.numPerPage,
        },
      })
      .then((apiData) => {
        // 如果没有更多数据，则不会存在下一个链接
        this.moreData = !!apiData.payload?.links?.next;

        let data = apiData;

        // 忽略TypeScript类型检查
        // @ts-ignore
        delete data.payload;

        const lastPage = this.getTotalPageCount();

        if (pageNumber > lastPage) {
          this.loadPage(lastPage - 1);
        } else {
          this.pageData = data;
          this.pageNumber = pageNumber;
          this.loadingPageNumber = pageNumber;
          this.isLoadingPage = false;
        }

        m.redraw();
      })
      .catch((err: Error) => {
        console.error(err);
        this.pageData = [];
      });
  }

  nextPage() {
    this.isLoadingPage = true;
    this.loadPage(this.pageNumber + 1);
  }

  previousPage() {
    this.isLoadingPage = true;
    this.loadPage(this.pageNumber - 1);
  }

  /**
   * @param page 基于1的页码
   */
  goToPage(page: number) {
    this.isLoadingPage = true;
    this.loadPage(page - 1);
  }

  private setPageNumberInUrl(pageNumber: number) {
    const search = window.location.hash.split('?', 2);
    const params = new URLSearchParams(search?.[1] ?? '');

    params.set('page', `${pageNumber}`);
    window.location.hash = search?.[0] + '?' + params.toString();
  }
}
