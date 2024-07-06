import app from '../common/app';
import { ForumkitRequestOptions } from './Application';
import { fireDeprecationWarning } from './helpers/fireDebugWarning';
import Store, { ApiPayloadSingle, ApiResponseSingle, MetaInformation } from './Store';

export interface ModelIdentifier {
  type: string;
  id: string;
}

export interface ModelAttributes {
  [key: string]: unknown;
}

export interface ModelRelationships {
  [relationship: string]: {
    data: ModelIdentifier | ModelIdentifier[];
  };
}

export interface UnsavedModelData {
  type?: string;
  attributes?: ModelAttributes;
  relationships?: ModelRelationships;
}

export interface SavedModelData {
  type: string;
  id: string;
  attributes?: ModelAttributes;
  relationships?: ModelRelationships;
}

export type ModelData = UnsavedModelData | SavedModelData;

export interface SaveRelationships {
  [relationship: string]: null | Model | Model[];
}

export interface SaveAttributes {
  [key: string]: unknown;
  relationships?: SaveRelationships;
}

/**
 * `Model` 类表示一个本地数据资源。它提供了通过 API 持久化更改的方法。
 */
export default abstract class Model {
  /**
   * 来自 API 的资源对象。
   */
  data: ModelData = {};

  /**
   * 模型数据最后更新的时间。观察此属性的值是在数据未更改时保留/缓存子树的一种快捷方式。
   */
  freshness: Date = new Date();

  /**
   * 该资源是否在服务器上存在。
   */
  exists: boolean = false;

  /**
   * 此资源应该持久化到的数据存储。
   */
  protected store: Store;

  /**
   * @param data 来自 API 的资源对象
   * @param store 此模型应该持久化到的数据存储
   */
  constructor(data: ModelData = {}, store = app.store) {
    this.data = data;
    this.store = store;
  }

  /**
   * 获取模型的 ID。
   *
   * @final
   */
  id(): string | undefined {
    return 'id' in this.data ? this.data.id : undefined;
  }

  /**
   * 获取模型的一个属性。
   *
   * @final
   */
  attribute<T = unknown>(attribute: string): T {
    return this.data?.attributes?.[attribute] as T;
  }

  /**
   * 将新数据本地合并到该模型中。
   *
   * @param data 要合并到此模型中的资源对象
   */
  pushData(data: ModelData | { relationships?: SaveRelationships }): this {
    if ('id' in data) {
      (this.data as SavedModelData).id = data.id;
    }

    if ('type' in data) {
      this.data.type = data.type;
    }

    if ('attributes' in data) {
      this.data.attributes ||= {};

      // @deprecated
      // 过滤意外进入的关系数据
      for (const key in data.attributes) {
        const val = data.attributes[key];
        if (val && val instanceof Model) {
          fireDeprecationWarning('Providing models as attributes to `Model.pushData()` or `Model.pushAttributes()` is deprecated.', '3249');
          delete data.attributes[key];
          data.relationships ||= {};
          data.relationships[key] = { data: Model.getIdentifier(val) };
        }
      }

      Object.assign(this.data.attributes, data.attributes);
    }

    if ('relationships' in data) {
      const relationships = this.data.relationships ?? {};

      // 对于 data.relationships 中的每一个关系字段，我们需要检查是否传递了一个 Model 实例。如果是，我们将它转换为一个关系数据对象。
      for (const r in data.relationships) {
        const relationship = data.relationships[r];

        if (relationship === null) {
          delete relationships[r];
          delete data.relationships[r];
          continue;
        }

        let identifier: ModelRelationships[string];
        if (relationship instanceof Model) {
          identifier = { data: Model.getIdentifier(relationship) };
        } else if (relationship instanceof Array) {
          identifier = { data: relationship.map(Model.getIdentifier) };
        } else {
          identifier = relationship;
        }

        data.relationships[r] = identifier;
        relationships[r] = identifier;
      }

      this.data.relationships = relationships;
    }

    // 既然我们已经更新了数据，我们可以说这个模型是最新的。
    // 这是一个使保留的子树等失效的简单方法。
    this.freshness = new Date();

    return this;
  }

  /**
   * 将新属性本地合并到此模型中。
   *
   * @param attributes 要合并的属性
   */
  pushAttributes(attributes: ModelAttributes) {
    this.pushData({ attributes });
  }

  /**
   * 将新属性合并到此模型中，既在本地也持久化。
   *
   * @param attributes T要保存的属性。如果存在 'relationships' 键, 它将被提取，并且关系也将被保存。
   */
  save(
    attributes: SaveAttributes,
    options: Omit<ForumkitRequestOptions<ApiPayloadSingle>, 'url'> & { meta?: MetaInformation } = {}
  ): Promise<ApiResponseSingle<this>> {
    const data: ModelData & { id?: string } = {
      type: this.data.type,
      attributes,
    };

    if ('id' in this.data) {
      data.id = this.data.id;
    }

    // 如果存在 'relationships' 键，则将其从 attributes 哈希中提取出来，
    // 并将其设置在顶级 data 对象上。我们将把这个 data 对象发送到 API 进行持久化。
    if (attributes.relationships) {
      data.relationships = {};

      for (const key in attributes.relationships) {
        const model = attributes.relationships[key];

        if (model === null) continue;

        data.relationships[key] = {
          data: model instanceof Array ? model.map(Model.getIdentifier) : Model.getIdentifier(model),
        };
      }

      delete attributes.relationships;
    }

    // 在我们更新模型的数据之前，我们应该复制模型的旧数据，
    // 这样如果在持久化过程中出现问题，我们可以回滚到旧数据。
    const oldData = this.copyData();

    this.pushData(data);

    const request = {
      data,
      meta: options.meta || undefined,
    };

    return app
      .request<ApiPayloadSingle>({
        method: this.exists ? 'PATCH' : 'POST',
        url: app.site.attribute('apiUrl') + this.apiEndpoint(),
        body: request,
        ...options,
      })
      .then(
        // 如果一切顺利，我们将确保存储（store）知道这个模型现在存在（如果它之前不存在），
        // 并且我们会将 API 返回的数据推送到存储（store）中。
        (payload) => {
          return this.store.pushPayload<this>(payload);
        },

        // 如果出现问题，不过幸好我们备份了模型的旧数据！
        // 我们将恢复到旧数据，并让其他人处理错误。
        (err: Error) => {
          this.pushData(oldData);
          m.redraw();
          throw err;
        }
      );
  }

  /**
   * 发送一个请求来删除资源。
   *
   * @param body 与 DELETE 请求一起发送的数据
   */
  delete(body: ForumkitRequestOptions<void>['body'] = {}, options: Omit<ForumkitRequestOptions<void>, 'url'> = {}): Promise<void> {
    if (!this.exists) return Promise.resolve();

    return app
      .request({
        method: 'DELETE',
        url: app.site.attribute('apiUrl') + this.apiEndpoint(),
        body,
        ...options,
      })
      .then(() => {
        this.exists = false;

        this.store.remove(this);
      });
  }

  /**
   * 为此资源构造一个指向 API 端点的路径。
   */
  protected apiEndpoint(): string {
    return '/' + this.data.type + ('id' in this.data ? '/' + this.data.id : '');
  }

  protected copyData(): ModelData {
    return JSON.parse(JSON.stringify(this.data));
  }

  protected rawRelationship<M extends Model>(relationship: string): undefined | ModelIdentifier;
  protected rawRelationship<M extends Model[]>(relationship: string): undefined | ModelIdentifier[];
  protected rawRelationship<_M extends Model | Model[]>(relationship: string): undefined | ModelIdentifier | ModelIdentifier[] {
    return this.data.relationships?.[relationship]?.data;
  }

  /**
   * 生成一个返回给定属性值的函数。
   *
   * @param transform 转换属性值的函数
   */
  static attribute<T>(name: string): () => T;
  static attribute<T, O = unknown>(name: string, transform: (attr: O) => T): () => T;
  static attribute<T, O = unknown>(name: string, transform?: (attr: O) => T): () => T {
    return function (this: Model) {
      if (transform) {
        return transform(this.attribute(name));
      }

      return this.attribute(name);
    };
  }

  /**
   * 生成一个函数，用于返回给定的具有“一对一”关系的值。
   *
   * @return 如果不存在关于该关系的信息，则返回 false；如果关系存在但模型尚未加载，则返回 undefined；如果模型已加载，则返回该模型。
   */
  static hasOne<M extends Model>(name: string): () => M | false;
  static hasOne<M extends Model | null>(name: string): () => M | null | false;
  static hasOne<M extends Model>(name: string): () => M | false {
    return function (this: Model) {
      const relationshipData = this.data.relationships?.[name]?.data;

      if (relationshipData && relationshipData instanceof Array) {
        throw new Error(`Relationship ${name} on model ${this.data.type} is plural, so the hasOne method cannot be used to access it.`);
      }

      if (relationshipData) {
        return this.store.getById<M>(relationshipData.type, relationshipData.id) as M;
      }

      return false;
    };
  }

  /**
   * 生成一个函数，该函数返回给定“具有多个”关系的值。
   *
   * @return  如果不存在关于该关系的信息，则返回 false；如果存在，则返回一个数组，其中包含已加载的模型（如果存在）和未加载的模型（用 undefined 表示）
   */
  static hasMany<M extends Model>(name: string): () => (M | undefined)[] | false {
    return function (this: Model) {
      const relationshipData = this.data.relationships?.[name]?.data;

      if (relationshipData && !(relationshipData instanceof Array)) {
        throw new Error(`Relationship ${name} on model ${this.data.type} is singular, so the hasMany method cannot be used to access it.`);
      }

      if (relationshipData) {
        return relationshipData.map((data) => this.store.getById<M>(data.type, data.id));
      }

      return false;
    };
  }

  /**
   * 将给定的值转换为 Date 对象。
   */
  static transformDate(value: string): Date;
  static transformDate(value: string | null): Date | null;
  static transformDate(value: string | undefined): Date | undefined;
  static transformDate(value: string | null | undefined): Date | null | undefined;
  static transformDate(value: string | null | undefined): Date | null | undefined {
    return value != null ? new Date(value) : value;
  }

  /**
   * 获取给定模型的资源标识符对象。
   */
  protected static getIdentifier(model: Model): ModelIdentifier;
  protected static getIdentifier(model?: Model): ModelIdentifier | null {
    if (!model || !('id' in model.data)) return null;

    return {
      type: model.data.type,
      id: model.data.id,
    };
  }
}
