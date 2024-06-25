import Component from '../Component';
import extract from '../utils/extract';

/**
 * Link 组件支持内部和外部链接。
 * 对于外部网站的链接，它将返回一个常规的 HTML 链接；
 * 对于内部链接，它将使用 Mithril 的 m.route.Link。
 *
 * 链接默认为内部链接；要使其成为外部链接，必须将 'external' 属性设置为 `true`。
 */
export default class Link extends Component {
  view(vnode) {
    let { options = {}, ...attrs } = vnode.attrs;

    attrs.href ||= '';

    // 出于某种原因，m.route.Link 不喜欢 vnode.text，因此如果存在，我们需要将其转换为文本 vnode 并存储在 children 中。
    const children = vnode.children || { tag: '#', children: vnode.text };

    if (attrs.external) {
      return <a {...attrs}>{children}</a>;
    }

    // 如果链接的 href URL 与当前页面路径相同，
    // 我们将不会向浏览器历史记录中添加新的条目。
    // 这允许我们仍然刷新 Page 组件，而不会添加无限的历史记录条目。
    if (attrs.href === m.route.get()) {
      if (!('replace' in options)) options.replace = true;
    }

    // 如果路由更改导致相同的路由（或相同的组件处理不同的路由），Mithril 2 不会完全重新渲染页面。
    // 在这里，'force' 参数将使用 Mithril 的 key 系统来强制完全重新渲染。
    // 参见 https://mithril.js.org/route.html#key-parameter
    if (extract(attrs, 'force')) {
      if (!('state' in options)) options.state = {};
      if (!('key' in options.state)) options.state.key = Date.now();
    }

    attrs.options = options;

    return <m.route.Link {...attrs}>{children}</m.route.Link>;
  }
}
