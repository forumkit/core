import isObject from './isObject';

export interface IItemObject<T> {
  content: T;
  itemName: string;
  priority: number;
}

class Item<T> {
  content: T;
  priority: number;

  constructor(content: T, priority: number) {
    this.content = content;
    this.priority = priority;
  }
}

/**
 * The `ItemList` class collects items and then arranges them into an array
 * by priority.
 */
export default class ItemList<T> {
  /**
   * The items in the list.
   */
  protected _items: Record<string, Item<T>> = {};

  /**
   * A **read-only copy** of items in the list.
   *
   * We don't allow adding new items to the ItemList via setting new properties,
   * nor do we allow modifying existing items directly.
   *
   * @deprecated Use {@link ItemList.toObject} instead.
   */
  get items(): DeepReadonly<Record<string, Item<T>>> {
    return new Proxy(this._items, {
      set() {
        console.warn('Modifying `ItemList.items` is not allowed.');
        return false;
      },
    });
  }

  /**
   * Check whether the list is empty.
   */
  isEmpty(): boolean {
    return Object.keys(this._items).length === 0;
  }

  /**
   * Check whether an item is present in the list.
   */
  has(key: string): boolean {
    return Object.keys(this._items).includes(key);
  }

  /**
   * Get the content of an item.
   */
  get(key: string): T {
    return this._items[key].content;
  }

  /**
   * Get the priority of an item.
   */
  getPriority(key: string): number {
    return this._items[key].priority;
  }

  /**
   * Add an item to the list.
   *
   * @param key A unique key for the item.
   * @param content The item's content.
   * @param priority The priority of the item. Items with a higher priority
   * will be positioned before items with a lower priority.
   */
  add(key: string, content: T, priority: number = 0): this {
    this._items[key] = new Item(content, priority);

    return this;
  }

  replace(key: string, content: T | null = null, priority: number | null = null): this {
    if (!this.has(key)) return this;

    if (content !== null) {
      this._items[key].content = content;
    }

    if (priority !== null) {
      this._items[key].priority = priority;
    }

    return this;
  }

  /**
   * Replaces an item's content, if the provided item key exists.
   *
   * If the provided `key` is not present, an error will be thrown.
   *
   * @param key The key of the item in the list
   * @param content The item's new content
   *
   * @example <caption>Replace item content.</caption>
   * items.setContent('myItem', <p>My new value.</p>);
   *
   * @example <caption>Replace item content and priority.</caption>
   *          items
   *            .setContent('myItem', <p>My new value.</p>)
   *            .setPriority('myItem', 10);
   *
   * @throws If the provided `key` is not present in the ItemList.
   */
  setContent(key: string, content: T): this {
    if (!this.has(key)) {
      throw new Error(`[ItemList] Cannot set content of Item. Key \`${key}\` is not present.`);
    }

    // Saves on bundle size to call the deprecated method internally
    return this.replace(key, content);
  }

  /**
   * Replaces an item's priority, if the provided item key exists.
   *
   * If the provided `key` is not present, an error will be thrown.
   *
   * @param key The key of the item in the list
   * @param priority The item's new priority
   *
   * @example <caption>Replace item priority.</caption>
   * items.setPriority('myItem', 10);
   *
   * @example <caption>Replace item priority and content.</caption>
   *          items
   *            .setPriority('myItem', 10)
   *            .setContent('myItem', <p>My new value.</p>);
   *
   * @throws If the provided `key` is not present in the ItemList.
   */
  setPriority(key: string, priority: number): this {
    if (!this.has(key)) {
      throw new Error(`[ItemList] Cannot set priority of Item. Key \`${key}\` is not present.`);
    }

    this._items[key].priority = priority;

    return this;
  }

  /**
   * Remove an item from the list.
   *
   * If the provided `key` is not present, nothing will happen.
   */
  remove(key: string): this {
    delete this._items[key];

    return this;
  }

  /**
   * Merge another list's items into this one.
   *
   * The list passed to this function will overwrite items which already exist
   * with the same key.
   */
  merge(otherList: ItemList<T>): ItemList<T> {
    Object.keys(otherList._items).forEach((key) => {
      const val = otherList._items[key];

      if (val instanceof Item) {
        this._items[key] = val;
      }
    });

    return this;
  }

  /**
   * 将列表转换为按优先级排列的项内容数组。
   *
   * 这 **不会** 保留原始的基本类型（如数字、布尔值或字符串）的类型，
   * 并且会将所有内容值转换为对象，以便可以在它们上访问 `itemName` 属性。
   *
   * **注意** 如果你的ItemList包含基本类型（如数字、布尔值或字符串），
   * 并且你没有向此函数传递 `true` ，则这些类型将被转换为它们的对象对应项。
   *
   * **注意** 修改最终数组中的任何对象也可能会更新原始ItemList的内容。
   *
   * @param keepPrimitives 一个可选参数，当设置为 `true` 时，将项目内容转换为对象并设置 `itemName` 属性。
   *                        如果未设置或设置为其他值，则基本类型将被转换为对象。
   */
  toArray(keepPrimitives?: false): (T & { itemName: string })[];
  /**
   * 将列表转换为按优先级排列的项内容数组。
   *
   * 已经是对象的内容值将被代理，并允许在它们上访问
   * `itemName` 属性。而基本值将无法在它们上访问
   * `itemName` 属性。
   *
   * **注意** 修改最终数组中的任何对象也可能会更新原始ItemList的内容。
   *
   * @param keepPrimitives 一个可选参数，当设置为 `true` 时，将项目内容转换为对象并设置 `itemName` 属性。
   */
  toArray(keepPrimitives: true): (T extends object ? T & Readonly<{ itemName: string }> : T)[];

  toArray(keepPrimitives: boolean = false): T[] | (T & Readonly<{ itemName: string }>)[] {
    const items: Item<T>[] = Object.keys(this._items).map((key, i) => {
      const item = this._items[key];

      if (!keepPrimitives || isObject(item.content)) {
        // Convert content to object, then proxy it
        return {
          ...item,
          content: this.createItemContentProxy(isObject(item.content) ? item.content : Object(item.content), key),
        };
      } else {
        // ...otherwise just return a clone of the item.
        return { ...item };
      }
    });

    return items.sort((a, b) => b.priority - a.priority).map((item) => item.content);
  }

  /**
   * A read-only map of all keys to their respective items in no particular order.
   *
   * We don't allow adding new items to the ItemList via setting new properties,
   * nor do we allow modifying existing items directly. You should use the
   * {@link ItemList.add}, {@link ItemList.setContent} and
   * {@link ItemList.setPriority} methods instead.
   *
   * To match the old behaviour of the `ItemList.items` property, call
   * `Object.values(ItemList.toObject())`.
   *
   * @example
   * const items = new ItemList();
   * items.add('b', 'My cool value', 20);
   * items.add('a', 'My value', 10);
   * items.toObject();
   * // {
   * //   a: { content: 'My value', priority: 10, itemName: 'a' },
   * //   b: { content: 'My cool value', priority: 20, itemName: 'b' },
   * // }
   */
  toObject(): DeepReadonly<Record<string, IItemObject<T>>> {
    return Object.keys(this._items).reduce((map, key) => {
      const obj = {
        content: this.get(key),
        itemName: key,
        priority: this.getPriority(key),
      };

      map[key] = obj;

      return map;
    }, {} as Record<string, IItemObject<T>>);
  }

  /**
   * Proxies an item's content, adding the `itemName` readonly property to it.
   *
   * @example
   * createItemContentProxy({ foo: 'bar' }, 'myItem');
   * // { foo: 'bar', itemName: 'myItem' }
   *
   * @param content The item's content (objects only)
   * @param key The item's key
   * @return Proxied content
   *
   * @internal
   */
  private createItemContentProxy<C extends object>(content: C, key: string): Readonly<C & { itemName: string }> {
    return new Proxy(content, {
      get(target, property, receiver) {
        if (property === 'itemName') return key;

        return Reflect.get(target, property, receiver);
      },
      set(target, property, value, receiver) {
        if (key !== null && property === 'itemName') {
          throw new Error('`itemName` property is read-only');
        }

        return Reflect.set(target, property, value, receiver);
      },
    }) as C & { itemName: string };
  }
}
