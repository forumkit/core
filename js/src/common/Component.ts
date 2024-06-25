import type Mithril from 'mithril';

export interface ComponentAttrs extends Mithril.Attributes {}

/**
 * `Component` 类定义了一个用户界面的'构建块'。一个组件在每次重绘时生成一个虚拟DOM。
 *
 * 本质上，这是Mithril组件的一个包装器，它添加了一些有用的特性：
 *
 *  - 在 `oninit` 和 `onbeforeupdate` 生命周期钩子中，我们将vnode属性存储在`this.attrs`中，
 *    这使得我们可以在组件之间使用属性，而无需将vnode传递给每个方法。
 * 
 *  - 静态的 `initAttrs` 方法允许我们以方便的方式为传入的属性提供默认值（或以其他方式修改它们）。
 * 
 *  - 当组件在DOM中创建时，我们将其DOM元素存储在 `this.element` 下；
 *    这使得我们可以通过 `this.$()` 方法从内部方法使用jQuery来修改子DOM状态。
 * 
 *  - 一个方便的 `component` 方法，作为hyperscript和JSX的替代方案。
 *
 * 与其他Mithril组件一样，扩展Component的组件可以使用 JSX, hyperscript, 或两者的组合进行初始化和嵌套，
 * 也可以使用 `component` 方法。
 *
 * @example
 * return m('div', <MyComponent foo="bar"><p>Hello World</p></MyComponent>);
 *
 * @example
 * return m('div', MyComponent.component({foo: 'bar'), m('p', 'Hello World!')); // 注意：这里修正了原始代码中的括号错误
 *
 * @see https://mithril.js.org/components.html
 */
export default abstract class Component<Attrs extends ComponentAttrs = ComponentAttrs, State = undefined> implements Mithril.ClassComponent<Attrs> {
  /**
   * 组件的根DOM元素。
   */
  element!: Element;

  /**
   * 传入组件的属性。
   *
   * @see https://mithril.js.org/components.html#passing-data-to-components
   */
  attrs!: Attrs;

  /**
   * 类组件状态，在重绘之间持久化。
   *
   * 更新此状态 **not** 像其他框架那样自动触发重绘。
   *
   * 这与Vnode状态不同，Vnode状态始终是您类组件的实例。
   *
   * 默认情况下，这是 `undefined` 。
   */
  state!: State;

  /**
   * @inheritdoc
   */
  abstract view(vnode: Mithril.Vnode<Attrs, this>): Mithril.Children;

  /**
   * @inheritdoc
   */
  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  oncreate(vnode: Mithril.VnodeDOM<Attrs, this>) {
    this.element = vnode.dom;
  }

  /**
   * @inheritdoc
   */
  onbeforeupdate(vnode: Mithril.VnodeDOM<Attrs, this>) {
    this.setAttrs(vnode.attrs);
  }

  /**
   * @inheritdoc
   */
  onupdate(vnode: Mithril.VnodeDOM<Attrs, this>) {}

  /**
   * @inheritdoc
   */
  onbeforeremove(vnode: Mithril.VnodeDOM<Attrs, this>) {}

  /**
   * @inheritdoc
   */
  onremove(vnode: Mithril.VnodeDOM<Attrs, this>) {}

  /**
   * 返回此组件元素的jQuery对象。如果传入一个选择器字符串，此方法将返回一个jQuery对象，使用当前元素作为其缓冲。
   *
   * 例如，调用 `component.$('li')` 将返回一个包含此组件DOM元素内所有 `li` 元素的jQuery对象。
   *
   * @param [selector] 一个与jQuery兼容的选择器字符串
   * @returns DOM节点的jQuery对象
   * @final
   */
  $(selector?: string): JQuery {
    const $element = $(this.element) as JQuery<HTMLElement>;

    return selector ? $element.find(selector) : $element;
  }

  /**
   * 方便的方法，用于在不使用JSX的情况下附加组件。
   * 与调用 `m(THIS_CLASS, attrs, children)` 具有相同的效果。
   *
   * @see https://mithril.js.org/hyperscript.html#mselector,-attributes,-children
   */
  static component<SAttrs extends ComponentAttrs = ComponentAttrs>(attrs: SAttrs = {} as SAttrs, children: Mithril.Children = null): Mithril.Vnode {
    const componentAttrs = { ...attrs };

    return m(this as any, componentAttrs, children);
  }

  /**
   * 在运行initAttrs后，保存对vnode属性的引用，并检查常见问题。
   */
  private setAttrs(attrs: Attrs = {} as Attrs): void {
    (this.constructor as typeof Component).initAttrs(attrs);

    if (attrs) {
      if ('children' in attrs) {
        throw new Error(
          `[${
            (this.constructor as typeof Component).name
          }] 永远不要使用attrs的 "children" 属性。要么将children作为vnode的子元素传入，要么重命名该属性`
        );
      }

      if ('tag' in attrs) {
        throw new Error(`[${(this.constructor as typeof Component).name}] 在Mithril 2中，您不能使用 "tag" 属性名。`);
      }
    }

    this.attrs = attrs;
  }

  /**
   * 初始化组件的属性。
   *
   * 这可以用于为缺失的、可选的属性分配默认值。
   */
  static initAttrs(attrs: unknown): void {}
}
