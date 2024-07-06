import type Mithril from 'mithril';

/**
 * `Fragment` 类表示使用 Mithril 渲染一次的 DOM 片段，然后接管其自己的 DOM 和生命周期。
 *
 * 这与 `Component` 包装类非常相似，但用于对 DOM 的某些重要片段的渲染和显示进行更精细的控制。与组件不同，片段不提供 Mithril 的生命周期钩子。
 *
 * 当您想享受 JSX / VDOM 在初始渲染中的好处，并结合直接更新该 DOM 的小型辅助方法时，而不是通过 Mithril 完全重绘所有内容时，请使用此功能。
 *
 * 这只应在必要时使用，并且仅与 `m.render` 一起使用。如果您不确定是否需要这个还是 `Component 那么您可能需要 `Component`。
 */
export default abstract class Fragment {
  /**
   * 该片段的根 DOM 元素。
   */
  protected element!: Element;

  /**
   * 返回此片段元素的 jQuery 对象。如果传入一个选择器字符串，该方法将返回一个 jQuery 对象，使用当前元素作为其缓冲区。
   *
   * 例如，调用 `fragment.$('li')` 将返回一个 jQuery 对象，
   * 该对象包含此片段 DOM 元素内部的所有 `li` 元素。
   *
   * @param [selector] 一个与 jQuery 兼容的选择器字符串
   * @returns DOM 节点的 jQuery 对象
   * @final
   */
  public $(selector?: string): JQuery {
    const $element = $(this.element) as JQuery<HTMLElement>;

    return selector ? $element.find(selector) : $element;
  }

  /**
   * 获取表示片段视图的可渲染虚拟 DOM。
   *
   * 子类不应重写此方法。希望定义其虚拟 DOM 的子类应重写 Fragment#view 而不是此方法。
   *
   * @example
   * const fragment = new MyFragment();
   * m.render(document.body, fragment.render());
   *
   * @final
   */
  public render(): Mithril.Vnode<Mithril.Attributes, this> {
    const vdom = this.view();

    vdom.attrs = vdom.attrs || {};

    const originalOnCreate = vdom.attrs.oncreate;

    vdom.attrs.oncreate = (vnode) => {
      this.element = vnode.dom;
      if (originalOnCreate) originalOnCreate.apply(this, [vnode]);
    };

    return vdom;
  }

  /**
   * 从虚拟元素中创建视图。
   */
  abstract view(): Mithril.Vnode<Mithril.Attributes, this>;
}
