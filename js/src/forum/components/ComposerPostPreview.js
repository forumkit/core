/*global s9e*/

import Component from '../../common/Component';

/**
 * `ComposerPostPreview` 组件使用 TextFormatter 库将 Markdown 渲染为 HTML，并每 50 毫秒轮询数据源以检测更改。
 * 这样做是为了防止在例如每次击键时都进行昂贵的重绘操作，同时仍然保持对用户来说的实时更新感知。
 *
 * ### Attrs  属性
 *
 * - `composer` ：控制此预览的作曲家的状态。
 * - `className` ：围绕预览元素的 CSS 类。
 * - `surround` ：一个回调函数，可以在重新渲染前后执行代码，例如用于滚动锚定。
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

    // 初始化一个变量来存储上一次的内容预览，并定义 updatePreview 函数来更新预览。
    let preview;
    const updatePreview = () => {
      // 检查作曲家是否仍然可见，如果不可见则不进行更新。
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
