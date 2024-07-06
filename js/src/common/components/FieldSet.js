import Component from '../Component';
import listItems from '../helpers/listItems';

/**
 * `FieldSet` 组件定义了一组字段的集合，这些字段在标题下方的列表中显示。可接受的属性有：
 *
 * - `className`：fieldset 的类名。
 * - `label`：这组字段的标题。
 *
 * 子元素应该是要在 fieldset 中显示的项目的数组。
 */
export default class FieldSet extends Component {
  view(vnode) {
    return (
      <fieldset className={this.attrs.className}>
        <legend>{this.attrs.label}</legend>
        <ul>{listItems(vnode.children)}</ul>
      </fieldset>
    );
  }
}
