import type Mithril from 'mithril';
import classList from '../utils/classList';

/**
 * `icon` 辅助函数用于显示图标。
 *
 * @param fontClass 完整的图标类名，包括前缀和图标名称
 * @param attrs 要应用的其他任何属性
 */
export default function icon(fontClass: string, attrs: Mithril.Attributes = {}): Mithril.Vnode {
  attrs.className = classList('icon', fontClass, attrs.className);

  return <i aria-hidden="true" {...attrs} />;
}
