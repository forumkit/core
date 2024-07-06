import Tooltip from './Tooltip';
import Component, { ComponentAttrs } from '../Component';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import textContrastClass from '../helpers/textContrastClass';

export interface IBadgeAttrs extends ComponentAttrs {
  icon: string;
  type?: string;
  label?: string;
  color?: string;
}

/**
 * `Badge` 组件代表用户/讨论徽章，表示某种状态（例如，讨论被置顶，用户是管理员）。
 *
 * 徽章可能有以下特殊属性：
 *
 * - `type` 徽章的类型。这将被用于给徽章添加类名 `Badge--{type}`。
 * - `icon` 徽章内部显示的图标的名称。
 * - `label`
 *
 * 所有其他属性都将被作为徽章元素的属性分配。
 */
export default class Badge<CustomAttrs extends IBadgeAttrs = IBadgeAttrs> extends Component<CustomAttrs> {
  view() {
    const { type, icon: iconName, label, color, style = {}, ...attrs } = this.attrs;

    const className = classList('Badge', [type && `Badge--${type}`], attrs.className, textContrastClass(color));

    const iconChild = iconName ? icon(iconName, { className: 'Badge-icon' }) : m.trust('&nbsp;');

    const newStyle = { ...style, '--badge-bg': color };

    const badgeAttrs = {
      ...attrs,
      className,
      style: newStyle,
    };

    const badgeNode = <div {...badgeAttrs}>{iconChild}</div>;

    // 如果没有提示标签，则不渲染提示组件。
    if (!label) return badgeNode;

    return <Tooltip text={label}>{badgeNode}</Tooltip>;
  }
}
