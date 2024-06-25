import Component from '../Component';
import icon from '../helpers/icon';
import withAttr from '../utils/withAttr';
import classList from '../utils/classList';

/**
 * `Select` 组件用于显示一个 <select> 输入框，周围环绕着一些额外的元素用于样式设置。它接受以下属性：
 *
 * - `options` 一个映射选项值到标签的对象
 * - `onchange` 当所选值发生变化时运行的回调函数
 * - `value` 所选选项的值
 * - `disabled` 输入框的禁用状态
 * - `wrapperAttrs` 一个映射属性到 DOM 元素的对象，该元素包裹 `<select>`
 *
 * 其他属性将直接传递给渲染到 DOM 的 `<select>` 元素。
 */
export default class Select extends Component {
  view() {
    const {
      options,
      onchange,
      value,
      disabled,
      className,
      class: _class,

      // 解构 `wrapperAttrs` 对象以提取 `className` 以便传递给 `classList()` 函数
      // 当 `wrapperAttrs` 未定义时，`= {}` 可以防止出现错误
      wrapperAttrs: { className: wrapperClassName, class: wrapperClass, ...wrapperAttrs } = {},

      ...domAttrs
    } = this.attrs;

    return (
      <span className={classList('Select', wrapperClassName, wrapperClass)} {...wrapperAttrs}>
        <select
          className={classList('Select-input FormControl', className, _class)}
          onchange={onchange ? withAttr('value', onchange.bind(this)) : undefined}
          value={value}
          disabled={disabled}
          {...domAttrs}
        >
          {Object.keys(options).map((key) => (
            <option value={key}>{options[key]}</option>
          ))}
        </select>
        {icon('fas fa-sort', { className: 'Select-caret' })}
      </span>
    );
  }
}
