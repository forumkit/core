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
 * `ItemList` 类收集项，然后按优先级将它们排列到数组中。
 */
export default class ItemList<T> {
  /**
   * 列表中的项。
   */
  protected _items: Record<string, Item<T>> = {};

  /**
   * 列表中项的 **只读副本** 。
   *
   * 我们不允许通过设置新属性向ItemList添加新项，
   * 也不允许直接修改现有项。
   *
   * @deprecated 已弃用 请使用 {@link ItemList.toObject} 代替。
   */
  get items(): DeepReadonly<Record<string, Item<T>>> {
    return new Proxy(this._items, {
      set() {
        console.warn('不允许修改 `ItemList.items` 。');
        return false;
      },
    });
  }

  /**
   * 检查列表是否为空。
   */
  isEmpty(): boolean {
    return Object.keys(this._items).length === 0;
  }

  /**
   * 检查列表中是否存在某个项。
   */
  has(key: string): boolean {
    return Object.keys(this._items).includes(key);
  }

  /**
   * 获取项的内容。
   */
  get(key: string): T {
    return this._items[key].content;
  }

  /**
   * 获取项的优先级。
   */
  getPriority(key: string): number {
    return this._items[key].priority;
  }

  /**
   * 向列表中添加一个项。
   *
   * @param key 项的唯一键
   * @param content 项的内容
   * @param priority 项的优先级，优先级更高的项将排在优先级较低的项之前
   */
  add(key: string, content: T, priority: number = 0): this {
    this._items[key] = new Item(content, priority);

    return this;
  }

  /**
   * 如果项已存在，则替换列表中的项和/或优先级。
   *
   * 如果 `content` 或 `priority` 是 `null`，则这些值不会被替换。
   *
   * 如果提供的 `key` 不存在，则什么都不会发生。
   *
   * @deprecated 已弃用 请使用 {@link ItemList.setContent} 和 {@link ItemList.setPriority}
   *
   * @param key 列表中项的键
   * @param content 项的新内容
   * @param priority 项的新优先级
   *
   * @example <caption>只替换优先级而不替换内容。</caption>
   * items.replace('myItem', null, 10);
   *
   * @example <caption>只替换内容而不替换优先级。</caption>
   * items.replace('myItem', <p>My new value.</p>);
   *
   * @example <caption>同时替换内容和优先级。</caption>
   * items.replace('myItem', <p>My new value.</p>, 10);
   */
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
   * 如果提供的项键存在，则替换项的内容。
   *
   * 如果提供的 `key` 不存在，则会抛出一个错误。
   *
   * @param key 列表中项的键
   * @param content 项的新内容
   *
   * @example <caption>替换项内容。</caption>
   * items.setContent('myItem', <p>My new value.</p>);
   *
   * @example <caption>同时替换项内容和优先级。</caption>
   *          items
   *            .setContent('myItem', <p>My new value.</p>)
   *            .setPriority('myItem', 10);
   *
   * @throws 如果提供的 `key` 不在ItemList中
   */
  setContent(key: string, content: T): this {
    if (!this.has(key)) {
      throw new Error(`[ItemList] 无法设置项的内容，键 \`${key}\` 不存在。`);
    }

    // 为了减少包大小，内部调用已弃用的方法
    return this.replace(key, content);
  }

  /**
   * 如果提供的项键存在，则替换项的优先级。
   *
   * 如果提供的`key` 不存在，则会抛出一个错误。
   *
   * @param key 列表中项的键
   * @param priority 项的新优先级
   *
   * @example <caption>替换项的优先级。</caption>
   * items.setPriority('myItem', 10);
   *
   * @example <caption>时替换项的优先级和内容。</caption>
   *          items
   *            .setPriority('myItem', 10)
   *            .setContent('myItem', <p>My new value.</p>);
   *
   * @throws 如果提供的 `key` 不在ItemList中。
   */
  setPriority(key: string, priority: number): this {
    if (!this.has(key)) {
      throw new Error(`[ItemList] 无法设置项的优先级，键 \`${key}\` 不存在。`);
    }

    this._items[key].priority = priority;

    return this;
  }

  /**
   * 从列表中移除一个项。
   *
   * 如果提供的 `key` 不存在，则什么都不会发生。
   */
  remove(key: string): this {
    delete this._items[key];

    return this;
  }

  /**
   * 将另一个列表的项合并到此列表中。
   *
   * 传递给此函数的列表将覆盖已存在且具有相同键的项。
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
   * 这**不会**保留原始类型的原始类型和代理，而是将所有内容值代理化以使 `itemName` 可访问。
   *
   * **注意**：如果你的ItemList包含原始类型（如数字、布尔值或字符串），
   * 如果你没有向此函数提供 `true`，则这些类型将被转换为它们的对象对应项。
   *
   * **注意**：修改最终数组中的任何对象也可能会更新原始ItemList的内容。
   *
   * @param keepPrimitives 将项内容转换为对象并在它们上设置 `itemName` 属性。
   *
   */
  toArray(keepPrimitives?: false): (T & { itemName: string })[];
  /**
   * 将列表转换为按优先级排列的项内容数组。
   *
   * 已经是对象的内容值将被代理化，并且 `itemName` 可在其上访问。
   * 原始值不会有 `itemName` 属性可访问。
   *
   * **注意：**修改最终数组中的任何对象也可能会更新原始ItemList的内容。
   *
   * @param keepPrimitives 将项内容转换为对象并在它们上设置 `itemName` 属性。
   */
  toArray(keepPrimitives: true): (T extends object ? T & Readonly<{ itemName: string }> : T)[];

  toArray(keepPrimitives: boolean = false): T[] | (T & Readonly<{ itemName: string }>)[] {
    const items: Item<T>[] = Object.keys(this._items).map((key, i) => {
      const item = this._items[key];

      if (!keepPrimitives || isObject(item.content)) {
        // 将内容转换为对象，然后代理它
        return {
          ...item,
          content: this.createItemContentProxy(isObject(item.content) ? item.content : Object(item.content), key),
        };
      } else {
        // ...否则只返回项的克隆。
        return { ...item };
      }
    });

    return items.sort((a, b) => b.priority - a.priority).map((item) => item.content);
  }

  /**
   * 一个无序的只读映射，其中包含了所有键及其对应的项。
   *
   * 我们不允许通过设置新属性向ItemList添加新项，
   * 也不允许直接修改现有项。你应该使用
   * {@link ItemList.add}, {@link ItemList.setContent} 和
   * {@link ItemList.setPriority} 方法来代替
   *
   * 要匹配 `ItemList.items` 属性的旧行为，请调用 `Object.values(ItemList.toObject())`
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
   * 为项的内容创建代理，并向其中添加 `itemName` 只读属性。
   *
   * @example
   * createItemContentProxy({ foo: 'bar' }, 'myItem');
   * // { foo: 'bar', itemName: 'myItem' }
   *
   * @param content 项的内容（仅对象）
   * @param key 项的键
   * @return 代理后的内容
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
          throw new Error('`itemName` 属性是只读的');
        }

        return Reflect.set(target, property, value, receiver);
      },
    }) as C & { itemName: string };
  }
}
