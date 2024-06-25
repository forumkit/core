import Dropdown, { IDropdownAttrs } from './Dropdown';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import type Component from '../Component';
import type Mithril from 'mithril';

/**
 * 通过 vnode 判断当前元素是否“活跃” "active" 。
 * 由于 Mithril 2 的变化，只有在父组件的 view() 方法 首次被调用之后，attrs 才会被实例化，
 * 所以我们不能总是依赖 active 属性来确定哪个元素应该被显示为“活跃的子元素” "active child" 。
 *
 * 这是一个临时补丁，因此并未导出/放在工具类中。
 */
function isActive(vnode: Mithril.Children): boolean {
  if (!vnode || typeof vnode !== 'object' || vnode instanceof Array) return false;

  const tag = vnode.tag;

  // 允许添加不可选择的分割符/标题。
  if (typeof tag === 'string' && tag !== 'a' && tag !== 'button') return false;

  if ((typeof tag === 'object' || typeof tag === 'function') && 'initAttrs' in tag) {
    (tag as unknown as typeof Component).initAttrs(vnode.attrs);
  }

  return (typeof tag === 'object' || typeof tag === 'function') && 'isActive' in tag ? (tag as any).isActive(vnode.attrs) : vnode.attrs.active;
}

export interface ISelectDropdownAttrs extends IDropdownAttrs {
  defaultLabel: string;
}

/**
 * `SelectDropdown` 组件与 `Dropdown`, 类似，但切换按钮的标签被设置为第一个具有真实 `active` 属性的子元素的标签。
 */
export default class SelectDropdown<CustomAttrs extends ISelectDropdownAttrs = ISelectDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: ISelectDropdownAttrs) {
    attrs.caretIcon ??= 'fas fa-sort';

    super.initAttrs(attrs);

    attrs.className = classList(attrs.className, 'Dropdown--select');
  }

  getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray {
    const activeChild = children.find(isActive);
    let label = (activeChild && typeof activeChild === 'object' && 'children' in activeChild && activeChild.children) || this.attrs.defaultLabel;

    // @ts-ignore
    if (Array.isArray(label)) label = label[0];

    return [<span className="Button-label">{label}</span>, this.attrs.caretIcon ? icon(this.attrs.caretIcon, { className: 'Button-caret' }) : null];
  }
}
