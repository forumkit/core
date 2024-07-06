import Component from '../Component';
import type Mithril from 'mithril';
import classList from '../utils/classList';
import extractText from '../utils/extractText';

export interface TooltipAttrs extends Mithril.CommonAttributes<TooltipAttrs, Tooltip> {
  /**
   * Tooltip 的文本内容。
   *
   * 类似于翻译器提供的字符串数组，将会被展平为字符串。
   */
  text: string | string[];
  /**
   * 用于手动显示或隐藏 tooltip。`undefined` 会根据光标事件显示。
   *
   * 默认值：`undefined`。
   */
  tooltipVisible?: boolean;
  /**
   * 是否在聚焦时显示。
   *
   * 默认值：`true`。
   */
  showOnFocus?: boolean;
  /**
   * Tooltip 在元素周围的位置。
   *
   * 默认值：`'top'`。
   */
  position?: 'top' | 'bottom' | 'left' | 'right';
  /**
   * Tooltip 是否允许 HTML 内容。
   *
   * **警告：**这是一个可能的 XSS 攻击向量。这个选项在可能的情况下不应该被使用，
   * 并且在我们切换到另一个 tooltip 库时可能不起作用。请为此在 Forumkit 稳定版中可能出现的中断做好准备。
   *
   * 默认值：`false`。
   *
   * @deprecated
   */
  html?: boolean;
  /**
   * 设置触发状态发生和 tooltip 出现在屏幕上的延迟时间。
   *
   * **警告**：这个选项在切换到另一个 tooltip 库时可能会被移除。请为此在 Forumkit 稳定版中可能出现的中断做好准备。
   *
   * 默认值：`0`。
   *
   * @deprecated
   */
  delay?: number;
  /**
   * 用于禁用将文本传递给 `title` 属性的警告。
   *
   * Tooltip 文本应该传递给 `text` 属性。
   */
  ignoreTitleWarning?: boolean;
}

/**
 * `Tooltip` 组件用于为元素创建 tooltip。
 * 它需要传递一个子元素给它。传递多个子元素或片段将会抛出错误。
 *
 * 你应该使用这个组件来创建任何 tooltip，以便在我们切换到另一个 tooltip 库而不是 Bootstrap tooltips 时保持向后兼容性。
 *
 * 如果你需要传递多个子元素，请使用另一个元素（如 `<span>` 或 `<div>`）将它们包围起来。
 *
 * **注意**：这个组件会覆盖你传递给它的第一个子元素的 `title` 属性，因为这就是当前 Forumkit 中 tooltip 系统的工作方式。
 * 如果你正确使用这个组件，这应该不是问题。
 *
 * @example <caption>基本用法</caption>
 *          <Tooltip text="你想得美！">
 *            <Button>
 *              点击领取免费的钱！
 *            </Button>
 *          </Tooltip>
 *
 * @example <caption>使用 `position` 和 `showOnFocus` 属性</caption>
 *          <Tooltip text="哇！真酷！" position="bottom" showOnFocus>
 *            <span>3 条回复</span>
 *          </Tooltip>
 *
 * @example <caption>错误用法</caption>
 *          // 这是错误的！用 <span> 或类似的元素包围子元素。
 *          <Tooltip text="这样不会工作">
 *            点击
 *            <a href="/">这里</a>
 *          </Tooltip>
 */
export default class Tooltip extends Component<TooltipAttrs> {
  private firstChild: Mithril.Vnode<any, any> | null = null;
  private childDomNode: HTMLElement | null = null;

  private oldText: string = '';
  private oldVisibility: boolean | undefined;

  private shouldRecreateTooltip: boolean = false;
  private shouldChangeTooltipVisibility: boolean = false;

  view(vnode: Mithril.Vnode<TooltipAttrs, this>) {
    /**
     * 我们知道这将是一个 ChildArray 而不是原始值，因为这个 vnode 是一个组件，而不是文本或受信任的 HTML vnode。
     */
    const children = vnode.children as Mithril.ChildArray | undefined;

    // 我们移除这些属性以获取剩余的属性传递给 DOM 元素
    const { text, tooltipVisible, showOnFocus = true, position = 'top', ignoreTitleWarning = false, html = false, delay = 0, ...attrs } = this.attrs;

    if ((this.attrs as any).title && !ignoreTitleWarning) {
      console.warn(
        '`title` 属性被传递给了 Tooltip 组件。这是有意为之吗？Tooltip 的内容应该通过 `text` 属性来传递。'
      );
    }

    const realText = this.getRealText();

    // 如果文本发生了变化，我们需要重新创建 tooltip
    if (realText !== this.oldText) {
      this.oldText = realText;
      this.shouldRecreateTooltip = true;
    }

    if (tooltipVisible !== this.oldVisibility) {
      this.oldVisibility = this.attrs.tooltipVisible;
      this.shouldChangeTooltipVisibility = true;
    }

    // 我们会尽力在它们引起任何奇怪效果之前，检测由开发者创建的任何问题。
    // 抛出错误会阻止网站渲染，但能更好地提醒开发者出现问题。

    if (typeof children === 'undefined') {
      throw new Error(
        `Tooltip component was provided with no direct child DOM element. Tooltips must contain a single direct DOM node to attach to.`
      );
    }

    if (children.length !== 1) {
      throw new Error(
        `Tooltip component was either passed more than one or no child node.\n\nPlease wrap multiple children in another element, such as a <div> or <span>.`
      );
    }

    const firstChild = children[0];

    if (typeof firstChild !== 'object' || Array.isArray(firstChild) || firstChild === null) {
      throw new Error(
        `Tooltip component was provided with no direct child DOM element. Tooltips must contain a single direct DOM node to attach to.`
      );
    }

    if (typeof firstChild.tag === 'string' && ['#', '[', '<'].includes(firstChild.tag)) {
      throw new Error(
        `Tooltip component with provided with a vnode with tag "${firstChild.tag}". This is not a DOM element, so is not a valid child element. Please wrap this vnode in another element, such as a <div> or <span>.`
      );
    }

    this.firstChild = firstChild;

    return children;
  }

  oncreate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    super.oncreate(vnode);

    this.checkDomNodeChanged();
    this.recreateTooltip();
  }

  onupdate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    super.onupdate(vnode);

    this.checkDomNodeChanged();
    this.recreateTooltip();
  }

  private recreateTooltip() {
    if (this.shouldRecreateTooltip && this.childDomNode !== null) {
      $(this.childDomNode).tooltip(
        'destroy',
        // 我们不希望这个参数成为公共API的一部分。它仅用于在使用此组件中的$.tooltip时防止弃用警告。
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
      this.createTooltip();
      this.shouldRecreateTooltip = false;
    }

    if (this.shouldChangeTooltipVisibility) {
      this.shouldChangeTooltipVisibility = false;
      this.updateVisibility();
    }
  }

  private updateVisibility() {
    if (this.childDomNode === null) return;

    if (this.attrs.tooltipVisible === true) {
      $(this.childDomNode).tooltip(
        'show',
        // 我们不希望这个参数成为公共API的一部分。它仅用于在使用此组件中的$.tooltip时防止弃用警告。
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
    } else if (this.attrs.tooltipVisible === false) {
      $(this.childDomNode).tooltip(
        'hide',
        // 我们不希望这个参数成为公共API的一部分。它仅用于在使用此组件中的$.tooltip时防止弃用警告。
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
    }
  }

  private createTooltip() {
    if (this.childDomNode === null) return;

    const {
      showOnFocus = true,
      position = 'top',
      delay,
      // 切换到CSS提示框时这将没有效果
      html = false,
      tooltipVisible,
      text,
    } = this.attrs;

    // 巧妙的 "hack" 来组装触发字符串
    const trigger = typeof tooltipVisible === 'boolean' ? 'manual' : classList('hover', [showOnFocus && 'focus']);

    const realText = this.getRealText();
    this.childDomNode.setAttribute('title', realText);
    this.childDomNode.setAttribute('aria-label', realText);

    // https://getbootstrap.com/docs/3.3/javascript/#tooltips-options
    $(this.childDomNode).tooltip(
      {
        html,
        delay,
        placement: position,
        trigger,
      },
      // 我们不希望这个参数成为公共API的一部分。它仅用于在使用此组件中的$.tooltip时防止弃用警告。
      'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
    );
  }

  // 获取真实的文本内容
  private getRealText(): string {
    const { text } = this.attrs;

    return Array.isArray(text) ? extractText(text) : text;
  }

  /**
   * 检查提示框的DOM节点是否已更改。
   *
   * 如果已更改，它将更新 `this.childDomNode` 为新节点，并将 `shouldRecreateTooltip` 设置为 `true` 。
   */
  private checkDomNodeChanged() {
    const domNode = (this.firstChild as Mithril.VnodeDOM<any, any>).dom as HTMLElement;

    if (domNode && !domNode.isSameNode(this.childDomNode)) {
      this.childDomNode = domNode;
      this.shouldRecreateTooltip = true;
    }
  }
}
