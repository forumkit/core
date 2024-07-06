import extractText from '../utils/extractText';
import Button from './Button';
import Tooltip from './Tooltip';

/**
 * `TextEditorButton` 组件显示一个适合文本编辑器工具栏的按钮。
 *
 * 自动使用Tooltip组件和提供的文本创建提示框。
 *
 * ## 属性（Attrs）
 * - `title` - 按钮的提示框文本
 */
export default class TextEditorButton extends Button {
  view(vnode) {
    const originalView = super.view(vnode);

    return <Tooltip text={this.attrs.tooltipText || extractText(vnode.children)}>{originalView}</Tooltip>;
  }

  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = attrs.className || 'Button Button--icon Button--link';
    attrs.tooltipText = attrs.title;
  }
}
