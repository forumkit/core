import Application, { ForumkitGenericRoute } from '../Application';
import IExtender, { IExtensionModule } from './IExtender';

type HelperRoute = (...args: any) => string;

export default class Routes implements IExtender {
  private routes: Record<string, ForumkitGenericRoute> = {};
  private helpers: Record<string, HelperRoute> = {};

  /**
   * 向应用添加mithril路由
   *
   * @param name 路由名称
   * @param path 路由路径，需要以'/'开头
   * @param component 必须继承自`Page`组件的组件
   */
  add(name: string, path: `/${string}`, component: any): Routes {
    this.routes[name] = { path, component };

    return this;
  }

  helper(name: string, callback: HelperRoute): Routes {
    this.helpers[name] = callback;

    return this;
  }

  extend(app: Application, extension: IExtensionModule) {
    Object.assign(app.routes, this.routes);
    Object.assign(app.route, this.helpers);
  }
}
