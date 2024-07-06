import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import { SettingsComponentOptions } from '../components/AdminPage';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';
import { PermissionConfig, PermissionType } from '../components/PermissionGrid';

type SettingConfigInput = SettingsComponentOptions | (() => Mithril.Children);

type SettingConfigInternal = SettingsComponentOptions | ((() => Mithril.Children) & { setting: string });

export type CustomExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> = new () => ExtensionPage<Attrs>;

type ExtensionConfig = {
  settings?: ItemList<SettingConfigInternal>;
  permissions?: {
    view?: ItemList<PermissionConfig>;
    start?: ItemList<PermissionConfig>;
    reply?: ItemList<PermissionConfig>;
    moderate?: ItemList<PermissionConfig>;
  };
  page?: CustomExtensionPage;
};

type InnerDataNoActiveExtension = {
  currentExtension: null;
  data: {
    [key: string]: ExtensionConfig | undefined;
  };
};

type InnerDataActiveExtension = {
  currentExtension: string;
  data: {
    [key: string]: ExtensionConfig;
  };
};

// 如果没有激活的扩展就抛出一个错误信息的常量
const noActiveExtensionErrorMessage = '在使用 extensionData 之前，您必须通过 `.for()` 方法选择一个激活的扩展。';

export default class ExtensionData {
  // 内部状态，可以是激活的扩展状态或没有激活的扩展状态
  protected state: InnerDataActiveExtension | InnerDataNoActiveExtension = {
    currentExtension: null, // 当前激活的扩展ID
    data: {},  // 扩展数据的映射
  };

  /**
   * 此函数仅接收扩展名 ID
   *
   * @example
   * app.extensionData.for('forumkit-tags')
   *
   * forumkit/flags -> forumkit-flags | acme/extension -> acme-extension
   */
  for(extension: string) {
    this.state.currentExtension = extension; // 设置当前激活的扩展ID
    this.state.data[extension] = this.state.data[extension] || {}; // 初始化或确保扩展的数据存在

    return this; // 返回当前实例以便链式调用
  }

  /**
   * 此函数将您的设置注册到Forumkit
   *
   * 它接收一个设置对象或一个回调函数。
   *
   * @example
   *
   * .registerSetting({
   *   setting: 'forumkit-flags.guidelines_url',
   *   type: 'text', // 这将作为该设置的输入标签的类型（文本/数字等）
   *   label: app.translator.trans('forumkit-flags.admin.settings.guidelines_url_label')
   * }, 15) // 优先级是可选的（ItemList）
   */
  registerSetting(content: SettingConfigInput, priority = 0): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const tmpContent = content as SettingConfigInternal;

    // 可以通过传递回调函数而不是设置来显示自定义内容。
    // 默认情况下，由于没有 `.setting` 属性，它们将以 `null` 作为键添加。
    // 为了支持一个扩展中的多个此类项，我们为其分配一个随机ID。
    // 36  是任意长度，但使冲突的可能性非常小。
    if (tmpContent instanceof Function) {
      tmpContent.setting = Math.random().toString(36);
    }

    const settings = this.state.data[this.state.currentExtension].settings || new ItemList();
    settings.add(tmpContent.setting, tmpContent, priority);

    this.state.data[this.state.currentExtension].settings = settings;

    return this;
  }

  /**
   * 此函数将您的权限注册到Forumkit
   *
   * @example
   *
   * .registerPermission('permissions', {
   *     icon: 'fas fa-flag',
   *     label: app.translator.trans('forumkit-flags.admin.permissions.view_flags_label'),
   *     permission: 'discussion.viewFlags'
   * }, 'moderate', 65)
   */
  registerPermission(content: PermissionConfig, permissionType: PermissionType, priority = 0): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    const permissions = this.state.data[this.state.currentExtension].permissions || {};

    const permissionsForType = permissions[permissionType] || new ItemList();

    permissionsForType.add(content.permission, content, priority);

    this.state.data[this.state.currentExtension].permissions = { ...permissions, [permissionType]: permissionsForType };

    return this;
  }

  /**
   * 用自定义组件替换默认的扩展页面。
   * 这个组件通常会继承自 ExtensionPage
   */
  registerPage(component: CustomExtensionPage): this {
    if (this.state.currentExtension === null) {
      throw new Error(noActiveExtensionErrorMessage);
    }

    this.state.data[this.state.currentExtension].page = component;

    return this;
  }

  /**
   * 获取一个扩展的已注册设置
   */
  getSettings(extensionId: string): SettingConfigInternal[] | undefined {
    return this.state.data[extensionId]?.settings?.toArray();
  }

  /**
   * 获取所有扩展的已注册权限的ItemList
   */
  getAllExtensionPermissions(type: PermissionType): ItemList<PermissionConfig> {
    const items = new ItemList<PermissionConfig>();

    Object.keys(this.state.data).map((extension) => {
      const extPerms = this.state.data[extension]?.permissions?.[type];
      if (this.extensionHasPermissions(extension) && extPerms !== undefined) {
        items.merge(extPerms);
      }
    });

    return items;
  }

  /**
   * 获取一个单独的扩展的已注册权限
   */
  getExtensionPermissions(extension: string, type: PermissionType): ItemList<PermissionConfig> {
    const extPerms = this.state.data[extension]?.permissions?.[type];
    if (this.extensionHasPermissions(extension) && extPerms != null) {
      return extPerms;
    }

    return new ItemList();
  }

  /**
   * 检查给定的扩展是否已注册了权限。
   */
  extensionHasPermissions(extension: string) {
    return this.state.data[extension]?.permissions !== undefined;
  }

  /**
   * 如果存在，返回扩展的自定义页面组件。
   */
  getPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs>(extension: string): CustomExtensionPage<Attrs> | undefined {
    return this.state.data[extension]?.page as CustomExtensionPage<Attrs> | undefined;
  }
}
