import app from '../../common/app';
import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';

export interface LoadingIndicatorAttrs extends ComponentAttrs {
  /**
   * 加载指示器容器的自定义类名。
   */
  className?: string;
  /**
   * 加载指示器容器的自定义类名（此注释与className重复了，可能是一个错误）。
   * （注：此处可能需要修改为更准确的描述，比如'外层容器类名'）
   */
  containerClassName?: string;
  /**
   * 加载指示器的可选大小
   */
  size?: 'large' | 'medium' | 'small';
  /**
   * 应用于加载指示器容器的可选属性
   */
  containerAttrs?: Partial<ComponentAttrs>;
  /**
   * 旋转器的显示类型
   *
   * @default 'block'
   */
  display?: 'block' | 'inline' | 'unset';
}

/**
 * `LoadingIndicator` 组件显示一个简单的基于 CSS 的加载旋转器。
 *
 * 要设置自定义颜色，请使用 CSS `color` 属性。
 *
 * 要增加旋转器周围的间距，请在旋转器的 **容器** 上使用 CSS `height` 属性。将 `display` 属性设置为 `block` 将默认设置高度为 `100px`。
 *
 * 要将自定义大小应用于加载指示器，请在加载指示器容器上设置 `--size` 和 `--thickness` CSS 自定义属性。
 *
 * 如果您 *真的* 想在自定义主题中更改此外观，则可以覆盖 `border-radius` 和 `border`，然后设置背景图像，或者使用 `content: "\<glyph>"`（例如 `content: "\f1ce"`）和 `font-family: 'Font Awesome 5 Free'` 来设置 FA 图标（如果您愿意的话）。
 *
 * ### 属性
 *
 * - `containerClassName` 应用到指示器父元素的类名
 * - `className` 应用到指示器本身的类名
 * - `display` 确定旋转器的显示方式（`inline`，`block`（默认）或 `unset`）
 * - `size` 加载指示器的大小（`small`，`medium` 或 `large`）
 * - `containerAttrs` 应用于容器 DOM 元素的可选属性
 *
 * 所有其他属性都将作为属性分配给 DOM 元素。
 */
export default class LoadingIndicator extends Component<LoadingIndicatorAttrs> {
  view() {
    const { display = 'block', size = 'medium', containerClassName, className, ...attrs } = this.attrs;

    const completeClassName = classList('LoadingIndicator', className);
    const completeContainerClassName = classList(
      'LoadingIndicator-container',
      display !== 'unset' && `LoadingIndicator-container--${display}`,
      size && `LoadingIndicator-container--${size}`,
      containerClassName
    );

    return (
      <div
        aria-label={app.translator.trans('core.lib.loading_indicator.accessible_label')}
        role="status"
        {...attrs.containerAttrs}
        data-size={size}
        className={completeContainerClassName}
      >
        <div aria-hidden="true" className={completeClassName} {...attrs} />
      </div>
    );
  }
}
