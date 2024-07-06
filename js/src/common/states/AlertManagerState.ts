import type Mithril from 'mithril';
import Alert, { AlertAttrs } from '../components/Alert';

/**
 * 由 `AlertManagerState.show` 返回。用于关闭警告。
 */
export type AlertIdentifier = number;

export type AlertArray = { [id: AlertIdentifier]: AlertState };

export interface AlertState {
  componentClass: typeof Alert;
  attrs: AlertAttrs;
  children: Mithril.Children;
}

export default class AlertManagerState {
  protected activeAlerts: AlertArray = {};
  protected alertId: AlertIdentifier = 0;

  getActiveAlerts() {
    return this.activeAlerts;
  }

  /**
   * 在警告区域显示一个警告。
   *
   * @return 警告的ID，可用于关闭警告。
   */
  show(children: Mithril.Children): AlertIdentifier;
  show(attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;
  show(componentClass: typeof Alert, attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;

  show(arg1: any, arg2?: any, arg3?: any) {
    // 根据上面的签名分配变量
    let componentClass = Alert;
    let attrs: AlertAttrs = {};
    let children: Mithril.Children;

    if (arguments.length == 1) {
      children = arg1 as Mithril.Children;
    } else if (arguments.length == 2) {
      attrs = arg1 as AlertAttrs;
      children = arg2 as Mithril.Children;
    } else if (arguments.length == 3) {
      componentClass = arg1 as typeof Alert;
      attrs = arg2 as AlertAttrs;
      children = arg3;
    }

    this.activeAlerts[++this.alertId] = { children, attrs, componentClass };
    m.redraw();

    return this.alertId;
  }

  /**
   * 关闭一个警告。
   */
  dismiss(key: AlertIdentifier | null): void {
    if (!key || !(key in this.activeAlerts)) return;

    delete this.activeAlerts[key];
    m.redraw();
  }

  /**
   * 清除所有警告。
   */
  clear(): void {
    this.activeAlerts = {};
    m.redraw();
  }
}
