import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import routes, { AdminRoutes } from './routes';
import Application, { ApplicationData } from '../common/Application';
import Navigation from '../common/components/Navigation';
import AdminNav from './components/AdminNav';
import ExtensionData from './utils/ExtensionData';
import IHistory from '../common/IHistory';

export type Extension = {
  id: string;
  name: string;
  version: string;
  description?: string;
  icon?: {
    name: string;
  };
  links: {
    authors?: {
      name?: string;
      link?: string;
    }[];
    discuss?: string;
    documentation?: string;
    support?: string;
    website?: string;
    donate?: string;
    source?: string;
  };
  extra: {
    'forumkit-extension': {
      title: string;
    };
  };
};

export interface AdminApplicationData extends ApplicationData {
  extensions: Record<string, Extension>;
  settings: Record<string, string>;
  modelStatistics: Record<string, { total: number }>;
  displayNameDrivers: string[];
  slugDrivers: Record<string, string[]>;
  permissions: Record<string, string[]>;
}

export default class AdminApplication extends Application {
  extensionData = new ExtensionData();

  extensionCategories = {
    feature: 30,
    theme: 20,
    language: 10,
  };

  history: IHistory = {
    canGoBack: () => true,
    getCurrent: () => null,
    getPrevious: () => null,
    push: () => {},
    backUrl: () => this.site.attribute<string>('baseUrl'),
    back: function () {
      window.location.assign(this.backUrl());
    },
    home: () => {},
  };

  /**
   * 设置项被序列化为字符串并显示在管理员仪表盘上。
   * 额外的编码/解码是可能的，但必须在客户端进行。
   *
   * @inheritdoc
   */

  data!: AdminApplicationData;

  route: typeof Application.prototype.route & AdminRoutes;

  constructor() {
    super();

    routes(this);

    this.route = (Object.getPrototypeOf(Object.getPrototypeOf(this)) as Application).route.bind(this);
  }

  /**
   * @inheritdoc
   */
  mount() {
    // Mithril（可能是某种前端框架）不会在 https://example.com/admin 上渲染主页路由， 
    // 所以我们需要明确地跳转到 https://example.com/admin#/
    if (!document.location.hash) document.location.hash = '#/';

    m.route.prefix = '#';
    super.mount();

    m.mount(document.getElementById('app-navigation')!, {
      view: () => <Navigation className="App-backControl" drawer />,
    });
    m.mount(document.getElementById('header-navigation')!, Navigation);
    m.mount(document.getElementById('header-primary')!, HeaderPrimary);
    m.mount(document.getElementById('header-secondary')!, HeaderSecondary);
    m.mount(document.getElementById('admin-navigation')!, AdminNav);
  }

  getRequiredPermissions(permission: string) {
    const required = [];

    if (permission === 'startDiscussion' || permission.indexOf('discussion.') === 0) {
      required.push('viewSite');
    }
    if (permission === 'discussion.delete') {
      required.push('discussion.hide');
    }
    if (permission === 'discussion.deletePosts') {
      required.push('discussion.hidePosts');
    }
    if (permission === 'user.editGroups') {
      required.push('viewHiddenGroups');
    }

    return required;
  }
}
