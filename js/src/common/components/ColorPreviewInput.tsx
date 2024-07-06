import type Mithril from 'mithril';

import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';
import icon from '../helpers/icon';

export default class ColorPreviewInput extends Component {
  view(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    const { className, id, ...attrs } = this.attrs;

    attrs.type ||= 'text';

    // 如果输入是一个3位十六进制代码，将其转换为6位。
    if (attrs.value.length === 4) {
      attrs.value = attrs.value.replace(/#([a-f0-9])([a-f0-9])([a-f0-9])/, '#$1$1$2$2$3$3');
    }

    return (
      <div className="ColorInput">
        <input className={classList('FormControl', className)} id={id} {...attrs} />

        <span className="ColorInput-icon" role="presentation">
          {icon('fas fa-exclamation-circle')}
        </span>

        <input className="ColorInput-preview" {...attrs} type="color" />
      </div>
    );
  }
}
