import Dropdown, { IDropdownAttrs } from './Dropdown';
import Button from './Button';
import icon from '../helpers/icon';
import Mithril from 'mithril';
import classList from '../utils/classList';

export interface ISplitDropdownAttrs extends IDropdownAttrs {}

/**
 * `SplitDropdown` 组件与 `Dropdown` 组件类似，但第一个子元素会在切换按钮之前作为独立的按钮显示。
 */
export default class SplitDropdown extends Dropdown {
  static initAttrs(attrs: ISplitDropdownAttrs) {
    super.initAttrs(attrs);

    attrs.className = classList(attrs.className, 'Dropdown--split');
    attrs.menuClassName = classList(attrs.menuClassName, 'Dropdown-menu--right');
  }

  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    // 复制第一个子组件的属性。这些属性将被分配给一个新的按钮，以便它与第一个子元素具有完全相同的行为。
    const firstChild = this.getFirstChild(children);
    const buttonAttrs = Object.assign({}, firstChild?.attrs);
    buttonAttrs.className = classList(buttonAttrs.className, 'SplitDropdown-button Button', this.attrs.buttonClassName);

    return (
      <>
        <Button {...buttonAttrs}>{firstChild.children}</Button>
        <button
          className={'Dropdown-toggle Button Button--icon ' + this.attrs.buttonClassName}
          aria-haspopup="menu"
          aria-label={this.attrs.accessibleToggleLabel}
          data-toggle="dropdown"
        >
          {this.attrs.icon ? icon(this.attrs.icon, { className: 'Button-icon' }) : null}
          {icon('fas fa-caret-down', { className: 'Button-caret' })}
        </button>
      </>
    );
  }

  /**
   * 获取第一个子元素。如果第一个子元素是数组，则返回该数组的第一个项目。
   */
  protected getFirstChild(children: Mithril.Children): Mithril.Vnode<any, any> {
    let firstChild = children;

    while (firstChild instanceof Array) firstChild = firstChild[0];

    return firstChild as Mithril.Vnode<any, any>;
  }
}
