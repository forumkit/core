import app from '../common/app';
import { ForumkitRequestOptions } from './Application';
import { fireDeprecationWarning } from './helpers/fireDebugWarning';
import Model, { ModelData, SavedModelData } from './Model';

export interface MetaInformation {
  [key: string]: any;
}

export interface ApiQueryParamsSingle {
  fields?: string[];
  include?: string;
  bySlug?: boolean;
  meta?: MetaInformation;
}

export interface ApiQueryParamsPlural {
  fields?: string[];
  include?: string;
  filter?:
    | {
        q: string;
      }
    | Record<string, string>;
  page?: {
    near?: number;
    offset?: number;
    number?: number;
    limit?: number;
    size?: number;
  };
  sort?: string;
  meta?: MetaInformation;
}

export type ApiQueryParams = ApiQueryParamsPlural | ApiQueryParamsSingle;

export interface ApiPayloadSingle {
  data: SavedModelData;
  included?: SavedModelData[];
  meta?: MetaInformation;
}

export interface ApiPayloadPlural {
  data: SavedModelData[];
  included?: SavedModelData[];
  links?: {
    first: string;
    next?: string;
    prev?: string;
  };
  meta?: MetaInformation;
}

export type ApiPayload = ApiPayloadSingle | ApiPayloadPlural;

export type ApiResponseSingle<M extends Model> = M & { payload: ApiPayloadSingle };
export type ApiResponsePlural<M extends Model> = M[] & { payload: ApiPayloadPlural };
export type ApiResponse<M extends Model> = ApiResponseSingle<M> | ApiResponsePlural<M>;

interface ApiQueryRequestOptions<ResponseType> extends Omit<ForumkitRequestOptions<ResponseType>, 'url'> {}

interface StoreData {
  [type: string]: Partial<Record<string, Model>>;
}

export function payloadIsPlural(payload: ApiPayload): payload is ApiPayloadPlural {
  return Array.isArray((payload as ApiPayloadPlural).data);
}

/**
 * `Store` 类定义了一个本地数据存储，并提供了从 API 检索数据的方法。
 */
export default class Store {
  /**
   * 本地数据存储。一个资源类型到 ID 的树形结构，以便访问 data[type][id] 时返回该类型/ID 的模型 type/ID。
   */
  protected data: StoreData = {};

  /**
   * 模型注册表。一个资源类型到模型类的映射，该模型类应用于表示该类型的资源。
   */
  models: Record<string, { new (): Model }>;

  constructor(models: Record<string, { new (): Model }>) {
    this.models = models;
  }

  /**
   * 将 API 负载中包含的资源推送到存储中。
   *
   * @return  表示负载中 'data' 键包含的资源(s)的模型(s)
   */
  pushPayload<M extends Model>(payload: ApiPayloadSingle): ApiResponseSingle<M>;
  pushPayload<Ms extends Model[]>(payload: ApiPayloadPlural): ApiResponsePlural<Ms[number]>;
  pushPayload<M extends Model | Model[]>(payload: ApiPayload): ApiResponse<FlatArray<M, 1>> {
    if (payload.included) payload.included.map(this.pushObject.bind(this));

    const models = payload.data instanceof Array ? payload.data.map((o) => this.pushObject(o, false)) : this.pushObject(payload.data, false);
    const result = models as ApiResponse<FlatArray<M, 1>>;

    // 将原始负载附加到我们返回的模型上。这对于消费者来说很有用，因为它允许他们访问与他们的请求相关的元信息。
    result.payload = payload;

    return result;
  }

  /**
   * 创建一个模型来表示资源对象（或更新一个已存在的），并将其推送到存储中。
   *
   * @param data 资源对象
   * @return 模型，或者如果未为该资源类型注册模型类，则返回 null。
   */
  pushObject<M extends Model>(data: SavedModelData): M | null;
  pushObject<M extends Model>(data: SavedModelData, allowUnregistered: false): M;
  pushObject<M extends Model>(data: SavedModelData, allowUnregistered = true): M | null {
    if (!this.models[data.type]) {
      if (!allowUnregistered) {
        setTimeout(() =>
          fireDeprecationWarning(`不允许推送类型为 \`${data.type}\` 的对象，因为该类型尚未在存储中注册。`, '3206')
        );
      }

      return null;
    }

    const type = (this.data[data.type] = this.data[data.type] || {});

    // 对于 TypeScript 正确缩小类型是必要的。
    const curr = type[data.id] as M;
    const instance = curr ? curr.pushData(data) : this.createRecord<M>(data.type, data);

    type[data.id] = instance;
    instance.exists = true;

    return instance;
  }

  /**
   * 向 API 发出请求以查找特定类型的记录(s)。
   */
  async find<M extends Model>(type: string, params?: ApiQueryParamsSingle): Promise<ApiResponseSingle<M>>;
  async find<Ms extends Model[]>(type: string, params?: ApiQueryParamsPlural): Promise<ApiResponsePlural<Ms[number]>>;
  async find<M extends Model>(
    type: string,
    id: string,
    params?: ApiQueryParamsSingle,
    options?: ApiQueryRequestOptions<ApiPayloadSingle>
  ): Promise<ApiResponseSingle<M>>;
  async find<Ms extends Model[]>(
    type: string,
    ids: string[],
    params?: ApiQueryParamsPlural,
    options?: ApiQueryRequestOptions<ApiPayloadPlural>
  ): Promise<ApiResponsePlural<Ms[number]>>;
  async find<M extends Model | Model[]>(
    type: string,
    idOrParams: undefined | string | string[] | ApiQueryParams,
    query: ApiQueryParams = {},
    options: ApiQueryRequestOptions<M extends Array<infer _T> ? ApiPayloadPlural : ApiPayloadSingle> = {}
  ): Promise<ApiResponse<FlatArray<M, 1>>> {
    let params = query;
    let url = app.site.attribute('apiUrl') + '/' + type;

    if (idOrParams instanceof Array) {
      url += '?filter[id]=' + idOrParams.join(',');
    } else if (typeof idOrParams === 'object') {
      params = idOrParams;
    } else if (idOrParams) {
      url += '/' + idOrParams;
    }

    return app
      .request<M extends Array<infer _T> ? ApiPayloadPlural : ApiPayloadSingle>({
        method: 'GET',
        url,
        params,
        ...options,
      })
      .then((payload) => {
        if (payloadIsPlural(payload)) {
          return this.pushPayload<FlatArray<M, 1>[]>(payload);
        } else {
          return this.pushPayload<FlatArray<M, 1>>(payload);
        }
      });
  }

  /**
   * 根据 ID 从存储中获取记录。
   */
  getById<M extends Model>(type: string, id: string): M | undefined {
    return this.data?.[type]?.[id] as M;
  }

  /**
   * 根据模型属性的值从存储中获取记录。
   *
   * @param type 资源类型
   * @param key 模型上方法的名称
   * @param value 模型属性的值
   */
  getBy<M extends Model, T = unknown>(type: string, key: keyof M, value: T): M | undefined {
    // @ts-expect-error No way to do this safely, unfortunately.
    return this.all(type).filter((model) => model[key]() === value)[0] as M;
  }

  /**
   * 获取特定类型的所有已加载记录。
   */
  all<M extends Model>(type: string): M[] {
    const records = this.data[type];

    return records ? (Object.values(records) as M[]) : [];
  }

  /**
   * 从存储中移除给定的模型。
   */
  remove(model: Model): void {
    delete this.data[model.data.type as string][model.id() as string];
  }

  /**
   * 创建给定类型的新记录。
   *
   * @param type 资源类型
   * @param data 用于初始化模型的任何数据
   */
  createRecord<M extends Model>(type: string, data: ModelData = {}): M {
    data.type = data.type || type;

    // @ts-expect-error 这将抱怨关于初始化抽象模型的问题，
    // 但我们可以安全地假设与存储注册的所有模型都不是抽象的。
    return new this.models[type](data, this);
  }
}
