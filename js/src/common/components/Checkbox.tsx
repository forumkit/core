import Component, { ComponentAttrs } from '../Component';
import LoadingIndicator from './LoadingIndicator';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import withAttr from '../utils/withAttr';
import type Mithril from 'mithril';

export interface ICheckboxAttrs extends ComponentAttrs {
  state?: boolean;
  loading?: boolean;
  disabled?: boolean;
  onchange: (checked: boolean, component: Checkbox<this>) => void;
}

/**
 * `Checkbox` 组件定义了一个复选框输入。
 *
 * ### 属性
 *
 * - `state`：复选框是否被选中。
 * - `className`：根元素的类名。
 * - `disabled`：复选框是否被禁用。
 * - `loading`：复选框是否正在加载。
 * - `onchange`：当复选框被选中或取消选中时要运行的回调函数。
 * - `children`：在复选框旁边显示的文本标签。
 */
export default class Checkbox<CustomAttrs extends ICheckboxAttrs = ICheckboxAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const className = classList([
      'Checkbox',
      this.attrs.state ? 'on' : 'off',
      this.attrs.className,
      this.attrs.loading && 'loading',
      this.attrs.disabled && 'disabled',
    ]);

    return (
      <label className={className}>
        <input type="checkbox" checked={this.attrs.state} disabled={this.attrs.disabled} onchange={withAttr('checked', this.onchange.bind(this))} />
        <div className="Checkbox-display" aria-hidden="true">
          {this.getDisplay()}
        </div>
        {vnode.children}
      </label>
    );
  }

  /**
   * 获取复选框显示的模板（打勾/叉号图标）。
   */
  protected getDisplay(): Mithril.Children {
    return this.attrs.loading ? <LoadingIndicator display="unset" size="small" /> : icon(this.attrs.state ? 'fas fa-check' : 'fas fa-times');
  }

  /**
   * 当复选框的状态改变时运行回调函数。
   */
  protected onchange(checked: boolean): void {
    if (this.attrs.onchange) this.attrs.onchange(checked, this);
  }
}
