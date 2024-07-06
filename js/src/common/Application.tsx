import app from '../common/app';

import ItemList from './utils/ItemList';
import Button from './components/Button';
import ModalManager from './components/ModalManager';
import AlertManager from './components/AlertManager';
import RequestErrorModal from './components/RequestErrorModal';
import Translator from './Translator';
import Store, { ApiPayload, ApiResponse, ApiResponsePlural, ApiResponseSingle, payloadIsPlural } from './Store';
import Session from './Session';
import extract from './utils/extract';
import extractText from './utils/extractText';
import Drawer from './utils/Drawer';
import mapRoutes from './utils/mapRoutes';
import RequestError, { InternalForumkitRequestOptions } from './utils/RequestError';
import ScrollListener from './utils/ScrollListener';
import liveHumanTimes from './utils/liveHumanTimes';
// 我们需要显式地使用前缀来区分 "extend" 文件夹中的内容。
import { extend } from './extend.ts';

import Site from './models/Site';
import User from './models/User';
import Discussion from './models/Discussion';
import Post from './models/Post';
import Group from './models/Group';
import Notification from './models/Notification';
import PageState from './states/PageState';
import ModalManagerState from './states/ModalManagerState';
import AlertManagerState from './states/AlertManagerState';

import type DefaultResolver from './resolvers/DefaultResolver';
import type Mithril from 'mithril';
import type Component from './Component';
import type { ComponentAttrs } from './Component';
import Model, { SavedModelData } from './Model';
import fireApplicationError from './helpers/fireApplicationError';
import IHistory from './IHistory';
import IExtender from './extenders/IExtender';
import AccessToken from './models/AccessToken';

export type ForumkitScreens = 'phone' | 'tablet' | 'desktop' | 'desktop-hd';

export type ForumkitGenericRoute = RouteItem<any, any, any>;

export interface ForumkitRequestOptions<ResponseType> extends Omit<Mithril.RequestOptions<ResponseType>, 'extract'> {
  errorHandler?: (error: RequestError) => void;
  url: string;

  extract?: (responseText: string) => string;

  modifyText?: (responseText: string) => string;
}

/**
 * 有效的路由定义
 */
export type RouteItem<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> = {

  path: `/${string}`;
} & (
  | {
      /**
       * 当此路由匹配时要渲染的组件
       */
      component: new () => Comp;
      /**
       * 自定义解析器类
       *
       * 这应该是类本身，而不是类的实例
       */
      resolverClass?: new (component: new () => Comp, routeName: string) => DefaultResolver<Attrs, Comp, RouteArgs>;
    }
  | {
      /**
       * 路由解析器的实例
       */
      resolver: RouteResolver<Attrs, Comp, RouteArgs>;
    }
);

export interface RouteResolver<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> {
  /**
   * 根据条件逻辑选择哪个组件进行渲染的方法
   *
   * 返回组件类，而不是Vnode或JSX表达式
   *
   * @see https://mithril.js.org/route.html#routeresolveronmatch
   */
  onmatch(this: this, args: RouteArgs, requestedPath: string, route: string): { new (): Comp };
  /**
   * 用于渲染提供的组件的函数
   *
   * 如果未指定，则路由将默认在片段内部独立渲染组件
   *
   * 返回一个Mithril Vnode或其他子元素
   *
   * @see https://mithril.js.org/route.html#routeresolverrender
   */
  render?(this: this, vnode: Mithril.Vnode<Attrs, Comp>): Mithril.Children;
}

export interface ApplicationData {
  apiDocument: ApiPayload | null;
  locale: string;
  locales: Record<string, string>;
  resources: SavedModelData[];
  session: { userId: number; csrfToken: string };
  [key: string]: unknown;
}

/**
 * `App` 类为应用程序提供了一个容器，以及应用程序其他部分可以使用的各种实用程序
 */
export default class Application {
  /**
   * 此应用程序的站点模型
   */
  site!: Site;

  /**
   * 路由映射，通过唯一的路由名称进行索引。每个路
   * 由都是一个包含以下属性的对象：
   *
   * - `path` 访问此路由的路径
   * - `component` 当此路由处于活动状态时要渲染的Mithril组件
   *
   * @example
   * app.routes.discussion = { path: '/discussion/:id', component: DiscussionPage };
   */
  routes: Record<string, ForumkitGenericRoute> = {};

  /**
   * 用于引导应用程序的有序初始化器列表。
   */
  initializers: ItemList<(app: this) => void> = new ItemList();

  /**
   * 应用程序的会话
   *
   * 存储当前用户的信息
   */
  session!: Session;

  /**
   * 应用程序的翻译器
   */
  translator: Translator = new Translator();

  /**
   * 应用程序的数据存储
   */
  store: Store = new Store({
    'access-tokens': AccessToken,
    sites: Site,
    users: User,
    discussions: Discussion,
    posts: Post,
    groups: Group,
    notifications: Notification,
  });

  /**
   * 可以在应用级别使用的本地缓存，用于存储数据，以便在不同的路由之间保持数据持久性
   */
  cache: Record<string, unknown> = {};

  /**
   * 应用程序是否已启动
   */
  booted: boolean = false;

  /**
   * 应用程序当前所在的页面
   *
   * 这个对象包含关于我们当前正在访问的页面类型的信息，以及可能与较低级别的组件相关的其他任意页面状态
   */
  current: PageState = new PageState(null);

  /**
   * 在进入当前页面之前，应用程序所在的页面
   *
   * 一旦应用程序导航到另一个页面，之前分配给 this.current 的对象将被移动到 this.previous, 而 this.current 将被重新初始化。
   */
  previous: PageState = new PageState(null);

  /**
   * 管理模态状态的对象
   */
  modal: ModalManagerState = new ModalManagerState();

  /**
   * 管理活动警报状态的对象
   */
  alerts: AlertManagerState = new AlertManagerState();

  /**
   * 管理导航抽屉状态的对象
   */
  drawer!: Drawer;

  history: IHistory | null = null;
  pane: any = null;

  data!: ApplicationData;

  private _title: string = '';
  private _titleCount: number = 0;

  private set title(val: string) {
    this._title = val;
  }

  get title() {
    return this._title;
  }

  private set titleCount(val: number) {
    this._titleCount = val;
  }

  get titleCount() {
    return this._titleCount; 
  }

  /**
   * 由于 AJAX 请求错误而显示的警报的键
   * 如果存在，它将在下一个成功的请求时被清除
   */
  private requestErrorAlert: number | null = null;

  initialRoute!: string;

  public load(payload: Application['data']) {
    this.data = payload;
    this.translator.setLocale(payload.locale);
  }

  public boot() {
    const caughtInitializationErrors: CallableFunction[] = [];

    this.initializers.toArray().forEach((initializer) => {
      try {
        initializer(this);
      } catch (e) {
        const extension = initializer.itemName.includes('/')
          ? initializer.itemName.replace(/(\/forumkit-ext-)|(\/forumkit-)/g, '-')
          : initializer.itemName;

        caughtInitializationErrors.push(() =>
          fireApplicationError(
            extractText(app.translator.trans('core.lib.error.extension_initialiation_failed_message', { extension })),
            `${extension} failed to initialize`,
            e
          )
        );
      }
    });

    this.store.pushPayload({ data: this.data.resources });

    this.site = this.store.getById('sites', '1')!;

    this.session = new Session(this.store.getById<User>('users', String(this.data.session.userId)) ?? null, this.data.session.csrfToken);

    this.mount();

    this.initialRoute = window.location.href;

    caughtInitializationErrors.forEach((handler) => handler());
  }

  public bootExtensions(extensions: Record<string, { extend?: IExtender[] }>) {
    Object.keys(extensions).forEach((name) => {
      const extension = extensions[name];

      // 如果扩展没有定义扩展器，则此处无需进行其他操作
      if (!extension.extend) return;

      const extenders = extension.extend.flat(Infinity);

      for (const extender of extenders) {
        extender.extend(this, { name, exports: extension });
      }
    });
  }

  protected mount(basePath: string = '') {
    // 使用一个具有可调用的view属性的对象来向组件传递参数；参见 https://mithril.js.org/mount.html
    m.mount(document.getElementById('modal')!, { view: () => <ModalManager state={this.modal} /> });
    m.mount(document.getElementById('alerts')!, { view: () => <AlertManager state={this.alerts} /> });

    this.drawer = new Drawer();

    m.route(document.getElementById('content')!, basePath + '/', mapRoutes(this.routes, basePath));

    const appEl = document.getElementById('app')!;
    const appHeaderEl = document.querySelector('.App-header')!;

    // 添加一个类到body上，表示页面已经向下滚动。
    // 当这种情况发生时，我们会在头部和应用程序主体上添加类，这将设置导航栏的位置为固定。
    // 我们不希望总是将其设置为固定，因为这可能会与自定义头部重叠。
    const scrollListener = new ScrollListener((top: number) => {
      const offset = appEl.getBoundingClientRect().top + document.body.scrollTop;

      appEl.classList.toggle('affix', top >= offset);
      appEl.classList.toggle('scrolled', top > offset);

      appHeaderEl.classList.toggle('navbar-fixed-top', top >= offset);
    });

    scrollListener.start();
    scrollListener.update();

    document.body.classList.add('ontouchstart' in window ? 'touch' : 'no-touch');

    liveHumanTimes();
  }

  /**
   * 获取已预加载到应用程序中的API响应文档
   */
  preloadedApiDocument<M extends Model>(): ApiResponseSingle<M> | null;
  preloadedApiDocument<Ms extends Model[]>(): ApiResponsePlural<Ms[number]> | null;
  preloadedApiDocument<M extends Model | Model[]>(): ApiResponse<FlatArray<M, 1>> | null {
    // 如果URL已更改，则预加载的API文档无效
    if (this.data.apiDocument && window.location.href === this.initialRoute) {
      const results = payloadIsPlural(this.data.apiDocument)
        ? this.store.pushPayload<FlatArray<M, 1>[]>(this.data.apiDocument)
        : this.store.pushPayload<FlatArray<M, 1>>(this.data.apiDocument);

      this.data.apiDocument = null;

      return results;
    }

    return null;
  }

  /**
   * 根据我们的媒体查询确定当前的屏幕模式
   */
  screen(): ForumkitScreens {
    const styles = getComputedStyle(document.documentElement);
    return styles.getPropertyValue('--forumkit-screen') as ReturnType<Application['screen']>;
  }

  /**
   * 设置页面的 `<title>` 
   *
   * @param title 新页面标题
   */
  setTitle(title: string): void {
    this.title = title;
    this.updateTitle();
  }

  /**
   * 在页面的 `<title>` 中显示一个数字
   *
   * @param count 在标题中显示的数字
   */
  setTitleCount(count: number): void {
    this.titleCount = count;
    this.updateTitle();
  }

  updateTitle(): void {
    const count = this.titleCount ? `(${this.titleCount}) ` : '';
    const onHomepage = m.route.get() === this.site.attribute('basePath') + '/';

    const params = {
      pageTitle: this.title,
      siteName: this.site.attribute('title'),
      // 在我们为前端添加页码之前，这个值始终为1，因此页码部分不会出现在URL中
      pageNumber: 1,
    };

    let title =
      onHomepage || !this.title
        ? extractText(app.translator.trans('core.lib.meta_titles.without_page_title', params))
        : extractText(app.translator.trans('core.lib.meta_titles.with_page_title', params));

    title = count + title;

    const parser = new DOMParser();
    document.title = parser.parseFromString(title, 'text/html').body.innerText;
  }

  protected transformRequestOptions<ResponseType>(forumkitOptions: ForumkitRequestOptions<ResponseType>): InternalForumkitRequestOptions<ResponseType> {
    const { background, deserialize, extract, modifyText, ...tmpOptions } = { ...forumkitOptions };

    // 除非另有指定，否则请求应在后台异步运行，以避免阻止重绘的发生
    const defaultBackground = true;

    // 当我们反序列化 JSON 数据时，如果出于某种原因服务器提供了无效响应，我们不希望应用程序崩溃。相反，我们会向用户显示错误消息

    const defaultDeserialize = (response: string) => response as ResponseType;

    // 当从响应中提取数据时，我们可以检查服务器响应码，并在出现问题时向用户显示错误消息
    const originalExtract = modifyText || extract;

    const options: InternalForumkitRequestOptions<ResponseType> = {
      background: background ?? defaultBackground,
      deserialize: deserialize ?? defaultDeserialize,
      ...tmpOptions,
    };

    extend(options, 'config', (_: undefined, xhr: XMLHttpRequest) => {
      xhr.setRequestHeader('X-CSRF-Token', this.session.csrfToken!);
    });

    // 如果请求的方法是类似 PATCH 或 DELETE 这样的方法（并非所有服务器和客户端都支持），
    // 那么我们将它作为 POST 请求发送，并在 X-HTTP-Method-Override 头中指定预期的方法
    if (options.method && !['GET', 'POST'].includes(options.method)) {
      const method = options.method;

      extend(options, 'config', (_: undefined, xhr: XMLHttpRequest) => {
        xhr.setRequestHeader('X-HTTP-Method-Override', method);
      });

      options.method = 'POST';
    }

    options.extract = (xhr: XMLHttpRequest) => {
      let responseText;

      if (originalExtract) {
        responseText = originalExtract(xhr.responseText);
      } else {
        responseText = xhr.responseText;
      }

      const status = xhr.status;

      if (status < 200 || status > 299) {
        throw new RequestError<ResponseType>(status, `${responseText}`, options, xhr);
      }

      if (xhr.getResponseHeader) {
        const csrfToken = xhr.getResponseHeader('X-CSRF-Token');
        if (csrfToken) app.session.csrfToken = csrfToken;
      }

      try {
        if (responseText === '') {
          return null;
        }

        return JSON.parse(responseText);
      } catch (e) {
        throw new RequestError<ResponseType>(500, `${responseText}`, options, xhr);
      }
    };

    return options;
  }

  /**
   * 发起 AJAX 请求，并处理可能出现的任何底层错误
   *
   * @see https://mithril.js.org/request.html
   */
  request<ResponseType>(originalOptions: ForumkitRequestOptions<ResponseType>): Promise<ResponseType> {
    const options = this.transformRequestOptions(originalOptions);

    if (this.requestErrorAlert) this.alerts.dismiss(this.requestErrorAlert);

    return m.request(options).catch((e) => this.requestErrorCatch(e, originalOptions.errorHandler));
  }

  /**
   * 默认情况下，显示错误警告，并将错误记录到控制台
   */
  protected requestErrorCatch<ResponseType>(error: RequestError, customErrorHandler: ForumkitRequestOptions<ResponseType>['errorHandler']) {
    // details 属性被解码以转换转义字符，如 '\n'
    const formattedErrors = error.response?.errors?.map((e) => decodeURI(e.detail ?? '')) ?? [];

    let content;
    switch (error.status) {
      case 422:
        content = formattedErrors
          .map((detail) => [detail, <br />])
          .flat()
          .slice(0, -1);
        break;

      case 401:
      case 403:
        content = app.translator.trans('core.lib.error.permission_denied_message');
        break;

      case 404:
      case 410:
        content = app.translator.trans('core.lib.error.not_found_message');
        break;

      case 413:
        content = app.translator.trans('core.lib.error.payload_too_large_message');
        break;

      case 429:
        content = app.translator.trans('core.lib.error.rate_limit_exceeded_message');
        break;

      default:
        if (this.requestWasCrossOrigin(error)) {
          content = app.translator.trans('core.lib.error.generic_cross_origin_message');
        } else {
          content = app.translator.trans('core.lib.error.generic_message');
        }
    }

    const isDebug: boolean = app.site.attribute('debug');

    error.alert = {
      type: 'error',
      content,
      controls: isDebug && [
        <Button className="Button Button--link" onclick={this.showDebug.bind(this, error, formattedErrors)}>
          {app.translator.trans('core.lib.debug_button')}
        </Button>,
      ],
    };

    if (customErrorHandler) {
      customErrorHandler(error);
    } else {
      this.requestErrorDefaultHandler(error, isDebug, formattedErrors);
    }

    return Promise.reject(error);
  }

  /**
   * 用于修改页面上显示的错误消息，以帮助进行故障排查。
   * 尽管不确定，但跨域请求失败可能表明缺少重定向到 Forumkit 规范 URL 的操作。
   * 由于XHR错误不会暴露CORS信息，我们只能将请求的URL源与页面源进行比较。
   *
   * @param error
   * @protected
   */
  protected requestWasCrossOrigin(error: RequestError): boolean {
    return new URL(error.options.url, document.baseURI).origin !== window.location.origin;
  }

  protected requestErrorDefaultHandler(e: unknown, isDebug: boolean, formattedErrors: string[]): void {
    if (e instanceof RequestError) {
      if (isDebug && e.xhr) {
        const { method, url } = e.options;
        const { status = '' } = e.xhr;

        console.group(`${method} ${url} ${status}`);

        if (formattedErrors.length) {
          console.error(...formattedErrors);
        } else {
          console.error(e);
        }

        console.groupEnd();
      }

      if (e.alert) {
        this.requestErrorAlert = this.alerts.show(e.alert, e.alert.content);
      }
    } else {
      throw e;
    }
  }

  private showDebug(error: RequestError, formattedError: string[]) {
    if (this.requestErrorAlert !== null) this.alerts.dismiss(this.requestErrorAlert);

    this.modal.show(RequestErrorModal, { error, formattedError });
  }

  /**
   * 构造一个到给定名称的路由的URL
   */
  route(name: string, params: Record<string, unknown> = {}): string {
    const route = this.routes[name];

    if (!route) throw new Error(`路由 '${name}' 不存在`);

    const url = route.path.replace(/:([^\/]+)/g, (m, key) => `${extract(params, key)}`);

    // 移除params中的假值（falsy values），以避免出现类似 '/?sort&q' 的URL
    for (const key in params) {
      if (params.hasOwnProperty(key) && !params[key]) delete params[key];
    }

    const queryString = m.buildQueryString(params as any);
    const prefix = m.route.prefix === '' ? this.site.attribute('basePath') : '';

    return prefix + url + (queryString ? '?' + queryString : '');
  }
}
