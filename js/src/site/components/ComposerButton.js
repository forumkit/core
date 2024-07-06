import Button from '../../common/components/Button';

/**
 * `ComposerButton` 组件显示一个适合编辑器控制栏的按钮。
 */
export default class ComposerButton extends Button {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = attrs.className || 'Button Button--icon Button--link';
  }
}
