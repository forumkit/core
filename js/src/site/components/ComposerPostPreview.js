/*global s9e*/

import Component from '../../common/Component';

/**
 * `ComposerPostPreview` 组件使用 TextFormatter 库将 Markdown 渲染为 HTML，每 50 毫秒轮询数据源以检查更改。
 * 这样做是为了防止在每次按键等操作时都进行昂贵的重绘，同时仍然保留用户感知到的实时更新。
 *
 * ### 属性（Attrs）
 *
 * - `composer` 控制此预览的编辑器状态
 * - `className` 包围预览的元素的 CSS 类
 * - `surround` 一个回调函数，可以在重新渲染之前和之后执行代码，例如用于滚动锚定
 */
export default class ComposerPostPreview extends Component {
  static initAttrs(attrs) {
    attrs.className = attrs.className || '';
    attrs.surround = attrs.surround || ((preview) => preview());
  }

  view() {
    return <div className={this.attrs.className} />;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // 每 50 毫秒，如果编辑器内容发生更改，则使用预览更新帖子的正文。
    let preview;
    const updatePreview = () => {
      // 由于我们采用轮询方式，在此期间编辑器可能已被关闭，因此在这种情况下我们直接返回。
      if (!this.attrs.composer.isVisible()) return;

      const content = this.attrs.composer.fields.content();

      if (preview === content) return;

      preview = content;

      this.attrs.surround(() => s9e.TextFormatter.preview(preview || '', vnode.dom));
    };
    updatePreview();

    this.updateInterval = setInterval(updatePreview, 50);
  }

  onremove(vnode) {
    super.onremove(vnode);

    clearInterval(this.updateInterval);
  }
}
