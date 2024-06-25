import Button from './Button';
import Link from './Link';

/**
 * `LinkButton` 组件定义了一个链接到某个路由的 `Button`。
 *
 * ### 属性
 *
 * 除了 `Button` 组件接受的所有属性外，还包括：
 *
 * - `active`：此按钮链接到的页面是否当前处于活动状态。
 * - `href`：要链接到的 URL。如果当前 URL `m.route()` 与此匹配，`active` 属性将自动设置为 `true`。
 * - `force`：页面是否应完全重新渲染。默认为 `true`。
 */

export default class LinkButton extends Button {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.active = this.isActive(attrs);
    if (attrs.force === undefined) attrs.force = true;
  }

  view(vnode) {
    const vdom = super.view(vnode);

    vdom.tag = Link;
    vdom.attrs.active = String(vdom.attrs.active);
    delete vdom.attrs.type;

    return vdom;
  }

  /**
   * 确定具有给定属性的组件是否处于“活动”状态 'active' 。
   *
   * @param {object} attrs
   * @return {boolean}
   */
  static isActive(attrs) {
    return typeof attrs.active !== 'undefined' ? attrs.active : m.route.get()?.split('?')[0] === attrs.href?.split('?')[0];
  }
}
