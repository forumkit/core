import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
import Separator from '../components/Separator';
import classList from '../utils/classList';

type ModdedVnodeAttrs = {
  itemClassName?: string;
  key?: string;
};

type ModdedTag = Mithril.Vnode['tag'] & {
  isListItem?: boolean;
  isActive?: (attrs: ComponentAttrs) => boolean;
};

type ModdedVnode = Mithril.Vnode<ModdedVnodeAttrs> & { itemName?: string; itemClassName?: string; tag: ModdedTag };

type ModdedChild = ModdedVnode | string | number | boolean | null | undefined;
type ModdedChildArray = ModdedChildren[];
type ModdedChildren = ModdedChild | ModdedChildArray;

/**
 * 这种类型表示由 `ItemList.toArray()` 返回的列表元素，
 * 与各种组件上使用的静态属性相结合。
 */
export type ModdedChildrenWithItemName = ModdedChildren & { itemName?: string };

function isVnode(item: ModdedChildren): item is Mithril.Vnode {
  return typeof item === 'object' && item !== null && 'tag' in item;
}

function isSeparator(item: ModdedChildren): boolean {
  return isVnode(item) && item.tag === Separator;
}

function withoutUnnecessarySeparators(items: ModdedChildrenWithItemName[]): ModdedChildrenWithItemName[] {
  const newItems: ModdedChildrenWithItemName[] = [];
  let prevItem: ModdedChildren;

  items.filter(Boolean).forEach((item, i: number) => {
    if (!isSeparator(item) || (prevItem && !isSeparator(prevItem) && i !== items.length - 1)) {
      prevItem = item;
      newItems.push(item);
    }
  });

  return newItems;
}

/**
 * `listItems` 辅助函数将一组组件包装在提供的标签中，
 * 并去除任何不必要的 `Separator` 组件。
 *
 * 默认情况下，这个标签是一个 `<li>` 标签，但可以通过第二个函数参数 `customTag` 进行自定义。
 */
export default function listItems<Attrs extends ComponentAttrs>(
  rawItems: ModdedChildrenWithItemName[],
  customTag: VnodeElementTag<Attrs> = 'li',
  attributes: Attrs = {} as Attrs
): Mithril.Vnode[] {
  const items = rawItems instanceof Array ? rawItems : [rawItems];
  const Tag = customTag;

  return withoutUnnecessarySeparators(items).map((item) => {
    const classes = [item.itemName && `item-${item.itemName}`];

    if (isVnode(item) && item.tag.isListItem) {
      item.attrs = item.attrs || {};
      item.attrs.key = item.attrs.key || item.itemName;
      item.key = item.attrs.key;

      return item;
    }

    if (isVnode(item)) {
      classes.push(item.attrs?.itemClassName || item.itemClassName);

      if (item.tag.isActive?.(item.attrs)) {
        classes.push('active');
      }
    }

    const key = (isVnode(item) && item?.attrs?.key) || item.itemName;

    return (
      <Tag className={classList(classes)} key={key} {...attributes}>
        {item}
      </Tag>
    );
  });
}
