import app from './app';

import History from './utils/History';
import Pane from './utils/Pane';
import DiscussionPage from './components/DiscussionPage';
import SignUpModal from './components/SignUpModal';
import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import Composer from './components/Composer';
import DiscussionRenamedNotification from './components/DiscussionRenamedNotification';
import CommentPost from './components/CommentPost';
import DiscussionRenamedPost from './components/DiscussionRenamedPost';
import routes, { SiteRoutes, makeRouteHelpers } from './routes';
import alertEmailConfirmation from './utils/alertEmailConfirmation';
import Application, { ApplicationData } from '../common/Application';
import Navigation from '../common/components/Navigation';
import NotificationListState from './states/NotificationListState';
import GlobalSearchState from './states/GlobalSearchState';
import DiscussionListState from './states/DiscussionListState';
import ComposerState from './states/ComposerState';
import isSafariMobile from './utils/isSafariMobile';

import type Notification from './components/Notification';
import type Post from './components/Post';
import type Discussion from '../common/models/Discussion';
import type NotificationModel from '../common/models/Notification';
import type PostModel from '../common/models/Post';
import extractText from '../common/utils/extractText';

export interface SiteApplicationData extends ApplicationData {}

export default class SiteApplication extends Application {
  /**
   * 一个将通知类型映射到它们各自组件的映射表。
   */
  notificationComponents: Record<string, ComponentClass<{ notification: NotificationModel }, Notification<{ notification: NotificationModel }>>> = {
    discussionRenamed: DiscussionRenamedNotification,
  };

  /**
   * 一个将帖子类型映射到它们各自组件的映射表。
   */
  postComponents: Record<string, ComponentClass<{ post: PostModel }, Post<{ post: PostModel }>>> = {
    comment: CommentPost,
    discussionRenamed: DiscussionRenamedPost,
  };

  /**
   * 一个控制页面侧边栏状态的对象。
   */
  pane: Pane | null = null;

  /**
   * T应用程序的历史堆栈，用于跟踪用户访问的路由，以便他们可以轻松地导航回之前的路由。
   */
  history: History = new History();

  /**
   * 一个控制用户通知状态的对象。
   */
  notifications: NotificationListState = new NotificationListState();

  /**
   * 一个存储先前搜索查询并提供方便工具以检索和管理搜索值的对象。
   */
  search: GlobalSearchState = new GlobalSearchState();

  /**
   * 一个控制编辑器状态的对象。 
   */
  composer: ComposerState = new ComposerState();

  /**
   * 一个控制缓存的讨论列表状态的对象，该列表用于索引页面和侧边栏。
   */
  discussions: DiscussionListState = new DiscussionListState({});

  route: typeof Application.prototype.route & SiteRoutes;

  data!: SiteApplicationData;

  constructor() {
    super();

    routes(this);

    this.route = Object.assign((Object.getPrototypeOf(Object.getPrototypeOf(this)) as Application).route.bind(this), makeRouteHelpers(this));
  }

  /**
   * @inheritdoc
   */
  mount() {
    // 获取配置的默认路由，并将该路由的路径更新为 '/'。
    // 将主页作为第一个路由推入，这样无论用户从哪个页面开始，他们都可以点击“返回”按钮回到主页。
    const defaultRoute = this.site.attribute('defaultRoute');
    let defaultAction = 'index';

    for (const i in this.routes) {
      if (this.routes[i].path === defaultRoute) defaultAction = i;
    }

    this.routes[defaultAction].path = '/';
    this.history.push(defaultAction, extractText(this.translator.trans('core.site.header.back_to_index_tooltip')), '/');

    this.pane = new Pane(document.getElementById('app'));

    m.route.prefix = '';
    super.mount(this.site.attribute('basePath'));

    // 我们在页面之后挂载导航和头部组件，以便像返回按钮这样的组件在渲染时可以访问更新的状态。
    m.mount(document.getElementById('app-navigation')!, { view: () => <Navigation className="App-backControl" drawer /> });
    m.mount(document.getElementById('header-navigation')!, Navigation);
    m.mount(document.getElementById('header-primary')!, HeaderPrimary);
    m.mount(document.getElementById('header-secondary')!, HeaderSecondary);
    m.mount(document.getElementById('composer')!, { view: () => <Composer state={this.composer} /> });

    alertEmailConfirmation(this);

    // 当点击主页链接时，将其路由回主页。但如果用户在新标签页中打开它，则不进行路由操作。
    document.getElementById('home-link')!.addEventListener('click', (e) => {
      if (e.ctrlKey || e.metaKey || e.button === 1) return;
      e.preventDefault();
      app.history.home();

      // 重新加载当前用户，以便刷新其未读通知计数。
      const userId = app.session.user?.id();
      if (userId) {
        app.store.find('users', userId);
        m.redraw();
      }
    });

    if (isSafariMobile()) {
      $(() => {
        $('.App').addClass('mobile-safari');
      });
    }
  }

  /**
   * 检查用户当前是否正在查看某个讨论。
   */
  public viewingDiscussion(discussion: Discussion): boolean {
    return this.current.matches(DiscussionPage, { discussion });
  }

  /**
   * 外部认证器（如社交登录）操作完成后的回调函数。
   *
   * 如果有效负载指示用户已登录，则重新加载页面。否则，打开一个已预填充详细信息的注册模态框。
   */
  public authenticationComplete(payload: Record<string, unknown>): void {
    if (payload.loggedIn) {
      window.location.reload();
    } else {
      this.modal.show(SignUpModal, payload);
    }
  }
}
