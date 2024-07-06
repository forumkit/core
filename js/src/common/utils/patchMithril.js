import bidi from './bidi';

export default function patchMithril(global) {
  const defaultMithril = global.m;

  const modifiedMithril = function (comp, ...args) {
    const node = defaultMithril.apply(this, arguments);

    if (!node.attrs) node.attrs = {};

    // 允许使用 bidi attr。
    if (node.attrs.bidi) {
      bidi(node, node.attrs.bidi);
    }

    return node;
  };

  Object.keys(defaultMithril).forEach((key) => (modifiedMithril[key] = defaultMithril[key]));

  global.m = modifiedMithril;
}
