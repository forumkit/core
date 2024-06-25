import type { ForumkitGenericRoute, RouteResolver } from '../Application';
import type Component from '../Component';
import DefaultResolver from '../resolvers/DefaultResolver';

/**
 * `mapRoutes` 工具函数将命名的应用程序路由映射转换为 Mithril 可以理解的格式，
 * 并将它们包装在路由解析器中，以向每个路由提供当前路由名称。
 *
 * @see https://mithril.js.org/route.html#signature
 */
export default function mapRoutes(routes: Record<string, ForumkitGenericRoute>, basePath: string = '') {
  const map: Record<
    string,
    RouteResolver<Record<string, unknown>, Component<{ routeName: string; [key: string]: unknown }>, Record<string, unknown>>
  > = {};

  for (const routeName in routes) {
    const route = routes[routeName];

    if ('resolver' in route) {
      map[basePath + route.path] = route.resolver;
    } else if ('component' in route) {
      const resolverClass = 'resolverClass' in route ? route.resolverClass! : DefaultResolver;
      map[basePath + route.path] = new resolverClass(route.component, routeName);
    } else {
      throw new Error(`对于路由 [${routeName}] ，必须提供解析器或组件`);
    }
  }

  return map;
}
