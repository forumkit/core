import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
import fireDebugWarning from '../helpers/fireDebugWarning';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import extractText from '../utils/extractText';
import LoadingIndicator from './LoadingIndicator';

export interface IButtonAttrs extends ComponentAttrs {
  /**
   * 要在按钮内渲染的可选图标的类名。
   *
   * 如果提供，按钮将获得 `has-icon` 类。
   */
  icon?: string;
  /**
   * 禁用用户输入按钮。
   *
   * 默认值：`false`
   */
  disabled?: boolean;
  /**
   * 在按钮内显示加载动画。
   *
   * 如果为 `true`，则还会禁用按钮。
   *
   * 默认值：`false`
   */
  loading?: boolean;
  /**
   * **已弃用**： 请改用 `aria-label` 属性。对于工具提示，请使用 `<Tooltip>` 组件。
   *
   * 按钮的可访问文本。如果按钮仅包含图标，则此文本应始终存在。
   *
   * 此属性的文本内容将作为 `aria-label` 传递给 DOM 元素。
   *
   * @deprecated
   */
  title?: string | Mithril.ChildArray;
  /**
   * 按钮的可访问文本。如果按钮仅包含图标，则此文本应始终存在。
   *
   * 此属性的文本内容将作为 `aria-label` 传递给 DOM 元素。
   */
  'aria-label'?: string | Mithril.ChildArray;
  /**
   * 按钮类型。
   *
   * 默认值：`"button"`
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button#attr-type
   */
  type?: string;
}

/**
 * `Button` 组件定义了一个元素，当点击时执行一个操作。
 *
 * 其他属性将被作为 `<button>` 元素的属性分配。
 *
 * 请注意，Button 没有默认的类名。这是因为 Button 可以用来表示任何通用的可点击控件，如菜单项。可以通过向 Button 组件提供 `className="Button"` 来应用常见的样式。
 */
export default class Button<CustomAttrs extends IButtonAttrs = IButtonAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    let { type, title, 'aria-label': ariaLabel, icon: iconName, disabled, loading, className, class: _class, ...attrs } = this.attrs;

    // 如果没有提供 `type` 属性，则设置为 "button"
    type ||= 'button';

    // 如果没有提供 `aria-label` 属性，则使用 `title` 属性作为 `aria-label`
    ariaLabel ||= title;

    // 如果给定的 `ariaLabel` 是一个对象，则提取其中的文本。
    if (typeof ariaLabel === 'object') {
      ariaLabel = extractText(ariaLabel);
    }

    if (disabled || loading) {
      delete attrs.onclick;
    }

    className = classList(_class, className, {
      hasIcon: iconName,
      disabled: disabled || loading,
      loading: loading,
    });

    const buttonAttrs = {
      disabled,
      className,
      type,
      'aria-label': ariaLabel,
      ...attrs,
    };

    return <button {...buttonAttrs}>{this.getButtonContent(vnode.children)}</button>;
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    const { 'aria-label': ariaLabel } = this.attrs;

    if (this.view === Button.prototype.view && !ariaLabel && !extractText(vnode.children) && !this.element?.getAttribute?.('aria-label')) {
      fireDebugWarning(
        '[Forumkit 可访问性警告] 按钮没有内容且没有可访问的标签。这意味着屏幕阅读器将无法解释其含义，而只能读取按钮"Button"。请考虑通过 `aria-label` 属性提供可访问的文本。 https://web.dev/button-name',
        this.element
      );
    }
  }

  /**
   * 获取按钮内容的模板。
   */
  protected getButtonContent(children: Mithril.Children): Mithril.ChildArray {
    const iconName = this.attrs.icon;

    return [
      iconName && icon(iconName, { className: 'Button-icon' }),
      children && <span className="Button-label">{children}</span>,
      this.attrs.loading && <LoadingIndicator size="small" display="inline" />,
    ];
  }
}
