import app from '../../site/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import DiscussionList from './DiscussionList';
import WelcomeHero from './WelcomeHero';
import DiscussionComposer from './DiscussionComposer';
import LogInModal from './LogInModal';
import DiscussionPage from './DiscussionPage';
import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import LinkButton from '../../common/components/LinkButton';
import SelectDropdown from '../../common/components/SelectDropdown';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';
import type Discussion from '../../common/models/Discussion';

export interface IIndexPageAttrs extends IPageAttrs {}

/**
 * `IndexPage` 组件用于显示主页，包括欢迎横幅、侧边栏和讨论列表。
 */
export default class IndexPage<CustomAttrs extends IIndexPageAttrs = IIndexPageAttrs, CustomState = {}> extends Page<CustomAttrs, CustomState> {
  static providesInitialSearch = true; // 提供初始搜索功能
  lastDiscussion?: Discussion; // 上一个访问的讨论（可选）

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // 如果用户从讨论页面返回，则记录下他们刚刚访问过的讨论。在页面渲染后，我们会滚动页面以使这个讨论可见。
    if (app.previous.matches(DiscussionPage)) {
      this.lastDiscussion = app.previous.get('discussion');
    }

    // 如果用户从讨论列表页面返回，那么他们要么刚更改了参数（筛选器、排序、搜索），要么可能想刷新结果。我们会清除讨论列表的缓存，以便重新加载结果。
    if (app.previous.matches(IndexPage)) {
      app.discussions.clear();
    }

    app.discussions.refreshParams(app.search.params(), (m.route.param('page') && Number(m.route.param('page'))) || 1);

    app.history.push('index', extractText(app.translator.trans('core.site.header.back_to_index_tooltip')));

    this.bodyClass = 'App--index';
    this.scrollTopOnCreate = false;
  }

  view() {
    return (
      <div className="IndexPage">
        {this.hero()}
        <div className="container">
          <div className="sideNavContainer">
            <nav className="IndexPage-side side-left">
              <ul>{listItems(this.sidebarItems().toArray())}</ul>
            </nav>
            <div className="IndexPage-results sideNavOffset">
              <div className="IndexPage-toolbar">
                <ul className="IndexPage-toolbar-view">{listItems(this.viewItems().toArray())}</ul>
                <ul className="IndexPage-toolbar-action">{listItems(this.actionItems().toArray())}</ul>
              </div>
              <DiscussionList state={app.discussions} />
            </div>
            <div className="IndexPage-side side-right">
              <div>{this.rightbarItems().toArray()}</div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  LeftItems() {
    const items = new ItemList();
    return items;
  }

  rightbarItems() {
    const items = new ItemList<Mithril.Children>();
    const canStartDiscussion = app.site.attribute('canStartDiscussion') || !app.session.user;

    items.add(
      'newDiscussion',
      <div className='InNewDiscussion'>
      <Button
        icon="fas fa-edit"
        className="Button Button--primary IndexPage-newDiscussion"
        itemClassName="App-primaryControl"
        onclick={() => {
          // 如果用户未登录，则 Promise 会被拒绝，并显示登录模态框。
          // 由于这种情况已经被处理，因此我们不需要在控制台中显示错误消息。
          return this.newDiscussionAction().catch(() => {});
        }}
        disabled={!canStartDiscussion}
      >
        {app.translator.trans(`core.site.index.${canStartDiscussion ? 'start_discussion_button' : 'cannot_start_discussion_button'}`)}
      </Button>
      </div>
    );

    return items;
  }
  
  setTitle() {
    app.setTitle(extractText(app.translator.trans('core.site.index.meta_title_text')));
    app.setTitleCount(0);
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.setTitle();

    // 计算当前 hero 的高度与上一个 hero 的高度差。为了保持相对于 hero 底部的相同滚动位置，以防止侧边栏跳动。
    const oldHeroHeight = app.cache.heroHeight as number;
    const heroHeight = (app.cache.heroHeight = this.$('.Hero').outerHeight() || 0);
    const scrollTop = app.cache.scrollTop as number;

    $('#app').css('min-height', ($(window).height() || 0) + heroHeight);

    // 在页面重新加载时，让浏览器处理滚动。
    if (app.previous.type == null) return;

    // 仅在从讨论页面返回时保留滚动位置。
    // 否则，我们只是更改了过滤器，所以应该回到页面顶部。
    if (this.lastDiscussion) {
      $(window).scrollTop(scrollTop - oldHeroHeight + heroHeight);
    } else {
      $(window).scrollTop(0);
    }

    // 如果我们刚从讨论页面返回，那么构造函数将设置 `lastDiscussion` 属性。
    // 如果是这种情况，我们想要滚动到那个讨论，以便它可见。
    if (this.lastDiscussion) {
      const $discussion = this.$(`li[data-id="${this.lastDiscussion.id()}"] .DiscussionListItem`);

      if ($discussion.length) {
        const indexTop = $('#header').outerHeight() || 0;
        const indexBottom = $(window).height() || 0;
        const discussionOffset = $discussion.offset();
        const discussionTop = (discussionOffset && discussionOffset.top) || 0;
        const discussionBottom = discussionTop + ($discussion.outerHeight() || 0);

        if (discussionTop < scrollTop + indexTop || discussionBottom > scrollTop + indexBottom) {
          $(window).scrollTop(discussionTop - indexTop);
        }
      }
    }
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeremove(vnode);

    // 保存滚动位置，以便我们返回到讨论列表时可以恢复它。
    app.cache.scrollTop = $(window).scrollTop();
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    // 清除应用容器的最小高度样式。
    $('#app').css('min-height', '');
  }

  /**
   * 获取要显示为 hero 的组件。
   */
  hero() {
    return <WelcomeHero />;
  }

  /**
   * 构建索引页面侧边栏的项目列表。默认情况下，这是一个“新建讨论”按钮，然后是一个包含导航项目列表的 DropdownSelect 组件。
   */
  sidebarItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'nav',
      <SelectDropdown
        buttonClassName="Button"
        className="App-titleControl"
        accessibleToggleLabel={app.translator.trans('core.site.index.toggle_sidenav_dropdown_accessible_label')}
      >
        {this.navItems().toArray()}
      </SelectDropdown>
    );

    return items;
  }

  /**
   * 为索引页面侧边栏的导航构建项目列表。默认情况下，这只是一个 AllDiscussions 的链接。
   */
  navItems() {
    const items = new ItemList<Mithril.Children>(); // 创建一个项目列表
    const params = app.search.stickyParams(); // 获取粘性参数

    items.add(
      // 在项目列表中添加一个项目
      'allDiscussions',
       // 项目的唯一标识符
       // 使用 LinkButton 组件创建一个链接按钮
       // 使用翻译器翻译 all_discussions_link 的文本 ,
       // 设置项目的排序权重 为 100
      <LinkButton href={app.route('index', params)} icon="far fa-comments">
        {app.translator.trans('core.site.index.all_discussions_link')}
      </LinkButton>,
      100
    );

    // 返回项目列表
    return items;
  }

  /**
   * 为工具栏中负责显示结果方式的部分构建项目列表。默认情况下，这只是一个选择框，用于更改讨论的排序方式。
   */
  viewItems() {
    const items = new ItemList<Mithril.Children>();
    const sortMap = app.discussions.sortMap();

    const sortOptions = Object.keys(sortMap).reduce((acc: any, sortId) => {
      acc[sortId] = app.translator.trans(`core.site.index_sort.${sortId}_button`);
      return acc;
    }, {});

    items.add(
      'sort',
      <Dropdown
        buttonClassName="Button"
        label={sortOptions[app.search.params().sort] || Object.keys(sortMap).map((key) => sortOptions[key])[0]}
        accessibleToggleLabel={app.translator.trans('core.site.index_sort.toggle_dropdown_accessible_label')}
      >
        {Object.keys(sortOptions).map((value) => {
          const label = sortOptions[value];
          const active = (app.search.params().sort || Object.keys(sortMap)[0]) === value;

          return (
            <Button icon={active ? 'fas fa-check' : true} onclick={app.search.changeSort.bind(app.search, value)} active={active}>
              {label}
            </Button>
          );
        })}
      </Dropdown>
    );

    return items;
  }

  /**
   * 为工具栏中处理结果操作的部分构建项目列表。默认情况下，这只是一个“全部标记为已读”按钮。
   */
  actionItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'refresh',
      <Button
        title={app.translator.trans('core.site.index.refresh_tooltip')}
        icon="fas fa-sync"
        className="Button Button--icon"
        onclick={() => {
          app.discussions.refresh();
          if (app.session.user) {
            app.store.find('users', app.session.user.id()!);
            m.redraw();
          }
        }}
      />
    );

    if (app.session.user) {
      items.add(
        'markAllAsRead',
        <Button
          title={app.translator.trans('core.site.index.mark_all_as_read_tooltip')}
          icon="fas fa-check"
          className="Button Button--icon"
          onclick={this.markAllAsRead.bind(this)}
        />
      );
    }

    return items;
  }

  /**
   * 打开新的讨论编辑器或提示用户登录。
   */
  newDiscussionAction(): Promise<unknown> {
    return new Promise((resolve, reject) => {
      if (app.session.user) {
        app.composer.load(DiscussionComposer, { user: app.session.user });
        app.composer.show();

        return resolve(app.composer);
      } else {
        app.modal.show(LogInModal);

        return reject();
      }
    });
  }

  /**
   * 将所有讨论标记为已读。
   */
  markAllAsRead() {
    const confirmation = confirm(extractText(app.translator.trans('core.site.index.mark_all_as_read_confirmation')));

    if (confirmation) {
      app.session.user?.save({ markedAllAsReadAt: new Date() });
    }
  }
}
