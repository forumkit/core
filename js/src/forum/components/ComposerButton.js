import Button from '../../common/components/Button';

/**
 * `ComposerButton` 组件用于显示适合作曲家控件的按钮。
 * 它继承自 Button 组件，并为其添加了特定的样式类，以匹配作曲家界面的外观。
 */
export default class ComposerButton extends Button {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = attrs.className || 'Button Button--icon Button--link';
  }
}
