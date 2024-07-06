import classList from '../utils/classList';
import Checkbox, { ICheckboxAttrs } from './Checkbox';

/**
 * `Switch` 组件是一个 `Checkbox`，但它以开关的形式显示，而不是勾选/叉选的形式。
 */
export default class Switch extends Checkbox {
  static initAttrs(attrs: ICheckboxAttrs) {
    super.initAttrs(attrs);

    attrs.className = classList(attrs.className, 'Checkbox--switch');
  }

  getDisplay() {
    return !!this.attrs.loading && super.getDisplay();
  }
}
