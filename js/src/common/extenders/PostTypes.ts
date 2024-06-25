import IExtender, { IExtensionModule } from './IExtender';
import Application from '../Application';
import SiteApplication from '../../site/SiteApplication';

export default class PostTypes implements IExtender {
  private postComponents: Record<string, any> = {};

  /**
   * 注册新的帖子组件类型
   * 通常用于事件帖子
   *
   * @param name 帖子类型的名称
   * @param component 用于渲染帖子的组件类
   */
  add(name: string, component: any): PostTypes {
    this.postComponents[name] = component;

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    Object.assign((app as unknown as SiteApplication).postComponents, this.postComponents);
  }
}
