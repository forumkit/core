import type Mithril from 'mithril';
import app from '../app';
import Component from '../Component';
import PageState from '../states/PageState';

export interface IPageAttrs {
  key?: number;
  routeName: string;
}

/**
 * `Page` 组件
 *
 * @abstract
 */
export default abstract class Page<CustomAttrs extends IPageAttrs = IPageAttrs, CustomState = undefined> extends Component<CustomAttrs, CustomState> {
  /**
   * 当路由处于活动状态时，应用于 body 的类名。
   */
  protected bodyClass = '';

  /**
   * 在页面渲染时是否应滚动到页面顶部。
   */
  protected scrollTopOnCreate = true;

  /**
   * 浏览器在刷新时是否应恢复滚动状态。
   */
  protected useBrowserScrollRestoration = true;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    app.previous = app.current;
    app.current = new PageState(this.constructor, { routeName: this.attrs.routeName });

    app.drawer.hide();
    app.modal.close();
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    if (this.bodyClass) {
      $('#app').addClass(this.bodyClass);
    }

    if (this.scrollTopOnCreate) {
      $(window).scrollTop(0);
    }

    if ('scrollRestoration' in history) {
      history.scrollRestoration = this.useBrowserScrollRestoration ? 'auto' : 'manual';
    }
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    if (this.bodyClass) {
      $('#app').removeClass(this.bodyClass);
    }
  }
}
