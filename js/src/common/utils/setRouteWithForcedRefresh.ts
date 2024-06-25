import type Mithril from 'mithril';

/**
 * 如果路由变化导致的是相同的路由（或者相同的组件处理不同的路由），Mithril 2 不会完全重新渲染页面。
 * 这个工具函数会调用 m.route.set 来强制重新渲染。
 *
 * @see https://mithril.js.org/route.html#key-parameter
 */
export default function setRouteWithForcedRefresh(route: string, params = null, options: Mithril.RouteOptions = {}) {
  const newOptions = { ...options };
  newOptions.state = newOptions.state || {};
  newOptions.state.key = Date.now();

  m.route.set(route, params, newOptions);
}
