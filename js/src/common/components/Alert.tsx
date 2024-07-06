import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import listItems from '../helpers/listItems';
import extract from '../utils/extract';
import type Mithril from 'mithril';
import classList from '../utils/classList';
import app from '../app';
import iconHelper from '../helpers/icon';

export interface AlertAttrs extends ComponentAttrs {
  /** 这是警告的类型。将用于给警告添加 `Alert--{type}` 的类名。 */
  type?: string;
  /** 警告的标题，可选。 */
  title?: Mithril.Children;
  /** 在标题旁边使用的图标，可选。 */
  icon?: string;
  /** 在警告中显示的一组控件。 */
  controls?: Mithril.Children;
  /** 警告是否可以被关闭。 */
  dismissible?: boolean;
  /** 当警告被关闭时运行的回调函数 */
  ondismiss?: Function;
}

/**
 * `Alert` 组件表示一个警告框，它包含一条消息、一些控件，并且可能是可关闭的。
 */
export default class Alert<T extends AlertAttrs = AlertAttrs> extends Component<T> {
  view(vnode: Mithril.VnodeDOM<T, this>) {
    const attrs = Object.assign({}, this.attrs);

    const type = extract(attrs, 'type');
    attrs.className = classList('Alert', `Alert--${type}`, attrs.className);

    const title = extract(attrs, 'title');
    const icon = extract(attrs, 'icon');
    const content = extract(attrs, 'content') || vnode.children;
    const controls = (extract(attrs, 'controls') || []) as Mithril.Vnode[];

    // 如果警告是可以关闭的（默认情况下是这样），
    // 那么我们将创建一个关闭按钮，并将其作为警告中的最后一个控件添加。
    const dismissible = extract(attrs, 'dismissible');
    const ondismiss = extract(attrs, 'ondismiss');
    const dismissControl: Mithril.Vnode[] = [];

    if (dismissible || dismissible === undefined) {
      dismissControl.push(
        <Button
          aria-label={app.translator.trans('core.lib.alert.dismiss_a11y_label')}
          icon="fas fa-times"
          className="Button Button--link Button--icon Alert-dismiss"
          onclick={ondismiss}
        />
      );
    }

    return (
      <div {...attrs}>
        {!!title && (
          <div className="Alert-title">
            {!!icon && <span className="Alert-title-icon">{iconHelper(icon)}</span>}
            <span className="Alert-title-text">{title}</span>
          </div>
        )}
        <span className="Alert-body">{content}</span>
        <ul className="Alert-controls">{listItems(controls.concat(dismissControl))}</ul>
      </div>
    );
  }
}
