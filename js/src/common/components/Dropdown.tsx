import app from '../../common/app';
import Component, { ComponentAttrs } from '../Component';
import icon from '../helpers/icon';
import listItems, { ModdedChildrenWithItemName } from '../helpers/listItems';
import extractText from '../utils/extractText';
import type Mithril from 'mithril';

export interface IDropdownAttrs extends ComponentAttrs {
  /** 应用于下拉菜单切换按钮的类名。 */
  buttonClassName?: string;
  /** 应用于下拉菜单的类名。 */
  menuClassName?: string;
  /** 在下拉菜单切换按钮中显示的图标名称。 */
  icon?: string;
  /** 在按钮右侧显示的图标名称。 */
  caretIcon?: string;
  /** 下拉菜单切换按钮的标签。默认为 'Controls'。 */
  label: Mithril.Children;
  /** 用于辅助阅读器描述下拉菜单切换按钮的标签。默认为 'Toggle dropdown menu'。 */
  accessibleToggleLabel?: string;
  /** 当下拉菜单折叠时执行的操作。 */
  onhide?: () => void;
  /** 当下拉菜单打开时执行的操作。 */
  onshow?: () => void;

  lazyDraw?: boolean;
}

/**
 * `Dropdown` 组件显示一个按钮，当点击时，在其下方显示一个下拉菜单。
 *
 * 子元素将在下拉菜单内部以列表的形式显示。
 */
export default class Dropdown<CustomAttrs extends IDropdownAttrs = IDropdownAttrs> extends Component<CustomAttrs> {
  protected showing = false;

  static initAttrs(attrs: IDropdownAttrs) {
    attrs.className ||= '';
    attrs.buttonClassName ||= '';
    attrs.menuClassName ||= '';
    attrs.label ||= '';
    attrs.caretIcon ??= 'fas fa-caret-down';
    attrs.accessibleToggleLabel ||= extractText(app.translator.trans('core.lib.dropdown.toggle_dropdown_accessible_label'));
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const items = vnode.children ? listItems(vnode.children as ModdedChildrenWithItemName[]) : [];
    const renderItems = this.attrs.lazyDraw ? this.showing : true;

    return (
      <div className={'ButtonGroup Dropdown dropdown ' + this.attrs.className + ' itemCount' + items.length + (this.showing ? ' open' : '')}>
        {this.getButton(vnode.children as Mithril.ChildArray)}
        {renderItems && this.getMenu(items)}
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // 当下拉菜单打开时，判断菜单是否超出了视窗的底部。
    // 如果超出了，我们会添加一个类来使菜单显示在切换按钮的上方而不是下方。
    this.$().on('shown.bs.dropdown', () => {
      const { lazyDraw, onshow } = this.attrs;

      this.showing = true;

      // 如果使用了延迟绘制（lazy drawing），在调用 `onshow` 函数之前重绘视图，
      // 以确保菜单的 DOM 元素存在，以防回调函数尝试使用它。
      if (lazyDraw) {
        m.redraw.sync();
      }

      if (typeof onshow === 'function') {
        onshow();
      }

      // 如果没有使用延迟绘制，则在调用 onshow() 后保持原有的重绘功能。
      if (!lazyDraw) {
        m.redraw();
      }

      const $menu = this.$('.Dropdown-menu');
      const isRight = $menu.hasClass('Dropdown-menu--right');

      const top = $menu.offset()?.top ?? 0;
      const height = $menu.height() ?? 0;
      const windowSrollTop = $(window).scrollTop() ?? 0;
      const windowHeight = $(window).height() ?? 0;

      $menu.removeClass('Dropdown-menu--top Dropdown-menu--right');

      $menu.toggleClass('Dropdown-menu--top', top + height > windowSrollTop + windowHeight);

      if (($menu.offset()?.top || 0) < 0) {
        $menu.removeClass('Dropdown-menu--top');
      }

      const left = $menu.offset()?.left ?? 0;
      const width = $menu.width() ?? 0;
      const windowScrollLeft = $(window).scrollLeft() ?? 0;
      const windowWidth = $(window).width() ?? 0;

      $menu.toggleClass('Dropdown-menu--right', isRight || left + width > windowScrollLeft + windowWidth);
    });

    this.$().on('hidden.bs.dropdown', () => {
      this.showing = false;

      if (this.attrs.onhide) {
        this.attrs.onhide();
      }

      m.redraw();
    });
  }

  /**
   * 获取按钮的模板。
   */
  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    return (
      <button
        className={'Dropdown-toggle ' + this.attrs.buttonClassName}
        aria-haspopup="menu"
        aria-label={this.attrs.accessibleToggleLabel}
        data-toggle="dropdown"
        onclick={this.attrs.onclick}
      >
        {this.getButtonContent(children)}
      </button>
    );
  }

  /**
   * 获取按钮内容的模板。
   */
  getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray {
    return [
      this.attrs.icon ? icon(this.attrs.icon, { className: 'Button-icon' }) : '',
      <span className="Button-label">{this.attrs.label}</span>,
      this.attrs.caretIcon ? icon(this.attrs.caretIcon, { className: 'Button-caret' }) : '',
    ];
  }

  getMenu(items: Mithril.Vnode<any, any>[]): Mithril.Vnode<any, any> {
    return <ul className={'Dropdown-menu dropdown-menu ' + this.attrs.menuClassName}>{items}</ul>;
  }
}
