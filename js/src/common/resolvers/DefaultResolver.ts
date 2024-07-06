import type Mithril from 'mithril';
import type { RouteResolver } from '../Application';
import type { default as Component, ComponentAttrs } from '../Component';

/**
 * 为给定的组件生成路由解析器。
 *
 * 除了常规的路由解析器功能外，还具有以下功能：
 * - 将当前路由名称作为属性提供
 * - 在组件上设置一个键，以便在路由更改时触发重新渲染。
 */
export default class DefaultResolver<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> implements RouteResolver<Attrs, Comp, RouteArgs>
{
  component: new () => Comp;
  routeName: string;

  constructor(component: new () => Comp, routeName: string) {
    this.component = component;
    this.routeName = routeName;
  }

  /**
   * 当路由更改导致键发生变化时，会发生整个页面的重新渲染。
   * 可以在子类中重写此方法，以防止在某些路由更改时进行重新渲染。
   */
  makeKey(): string {
    return this.routeName + JSON.stringify(m.route.param());
  }

  makeAttrs(vnode: Mithril.Vnode<Attrs, Comp>): Attrs & { routeName: string } {
    return {
      ...vnode.attrs,
      routeName: this.routeName,
    };
  }

  onmatch(args: RouteArgs, requestedPath: string, route: string): { new (): Comp } {
    return this.component;
  }

  render(vnode: Mithril.Vnode<Attrs, Comp>): Mithril.Children {
    return [{ ...vnode, attrs: this.makeAttrs(vnode), key: this.makeKey() }];
  }
}
