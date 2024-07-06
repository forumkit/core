import Component from '../Component';

/**
 * `Placeholder` 组件显示一些带有操作调用的柔和文本，通常用作空状态。
 *
 * ### 属性（Attrs）
 *
 * - `text`
 */
export default class Placeholder extends Component {
  view() {
    return (
      <div className="Placeholder">
        <p>{this.attrs.text}</p>
      </div>
    );
  }
}
