import type Mithril from 'mithril';

import app from '../app';
import Page, { IPageAttrs } from '../../common/components/Page';
import Button from '../../common/components/Button';
import Switch from '../../common/components/Switch';
import Select from '../../common/components/Select';
import classList from '../../common/utils/classList';
import Stream from '../../common/utils/Stream';
import saveSettings from '../utils/saveSettings';
import AdminHeader from './AdminHeader';
import generateElementId from '../utils/generateElementId';
import ColorPreviewInput from '../../common/components/ColorPreviewInput';
import ItemList from '../../common/utils/ItemList';

export interface AdminHeaderOptions {
  title: Mithril.Children;
  description: Mithril.Children;
  icon: string;
  /**
   * 将被用作 AdminPage 的类名。
   *
   * 也会被附加 `-header` 后缀，并设置为 AdminHeader 组件的类名。
   */
  className: string;
}

/**
 * 一个类型，匹配 HTML `<input>` 元素上 `type` 属性的任何有效值。
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-type
 *
 */
export type HTMLInputTypes =
  | 'button'
  | 'checkbox'
  | 'color'
  | 'date'
  | 'datetime-local'
  | 'email'
  | 'file'
  | 'hidden'
  | 'image'
  | 'month'
  | 'number'
  | 'password'
  | 'radio'
  | 'range'
  | 'reset'
  | 'search'
  | 'submit'
  | 'tel'
  | 'text'
  | 'time'
  | 'url'
  | 'week';

export interface CommonSettingsItemOptions extends Mithril.Attributes {
  setting: string;
  label?: Mithril.Children;
  help?: Mithril.Children;
  className?: string;
}

/**
 * 设置组件构建器生成HTML输入元素的有效选项。
 */
export interface HTMLInputSettingsComponentOptions extends CommonSettingsItemOptions {
  /**
   * 任何有效的HTML输入 type 值。
   */
  type: HTMLInputTypes;
}

const BooleanSettingTypes = ['bool', 'checkbox', 'switch', 'boolean'] as const;
const SelectSettingTypes = ['select', 'dropdown', 'selectdropdown'] as const;
const TextareaSettingTypes = ['textarea'] as const;
const ColorPreviewSettingType = 'color-preview' as const;

/**
 * 设置组件构建器生成Switch的有效选项。
 */
export interface SwitchSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof BooleanSettingTypes[number];
}

/**
 * 设置组件构建器生成Select下拉菜单的有效选项。
 */
export interface SelectSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof SelectSettingTypes[number];
  /**
   * 值到标签的映射 
   */
  options: { [value: string]: Mithril.Children };
  default: string;
}

/**
 * 设置组件构建器生成Textarea的有效选项。
 */
export interface TextareaSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof TextareaSettingTypes[number];
}

/**
 * 设置组件构建器生成ColorPreviewInput的有效选项。
 */
export interface ColorPreviewSettingComponentOptions extends CommonSettingsItemOptions {
  type: typeof ColorPreviewSettingType;
}

export interface CustomSettingComponentOptions extends CommonSettingsItemOptions {
  type: string;
  [key: string]: unknown;
}

/**
 * 设置组件构建器的所有有效选项。
 */
export type SettingsComponentOptions =
  | HTMLInputSettingsComponentOptions
  | SwitchSettingComponentOptions
  | SelectSettingComponentOptions
  | TextareaSettingComponentOptions
  | ColorPreviewSettingComponentOptions
  | CustomSettingComponentOptions;

/**
 * `headerInfo` 函数可以返回的有效属性
 */
export type AdminHeaderAttrs = AdminHeaderOptions & Partial<Omit<Mithril.Attributes, 'class'>>;

export type SettingValue = string;
export type MutableSettings = Record<string, Stream<SettingValue>>;

export type SaveSubmitEvent = SubmitEvent & { redraw: boolean };

export default abstract class AdminPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
  settings: MutableSettings = {};
  loading: boolean = false;

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const className = classList('AdminPage', this.headerInfo().className);

    return (
      <div className={className}>
        {this.header(vnode)}
        <div className="container">{this.content(vnode)}</div>
      </div>
    );
  }

  /**
   * 返回AdminPage的内容。
   */
  abstract content(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;

  /**
   * 返回这个AdminPage的提交按钮。
   *
   * 当按钮被点击时，调用`this.saveSettings`方法。
   */
  submitButton(): Mithril.Children {
    return (
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.isChanged()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  /**
   * 返回这个AdminPage的Header组件。
   */
  header(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { title, className, ...headerAttrs } = this.headerInfo();

    return (
      <AdminHeader className={className ? `${className}-header` : undefined} {...headerAttrs}>
        {title}
      </AdminHeader>
    );
  }

  /**
   * 返回传递给AdminHeader组件的选项。
   */
  headerInfo(): AdminHeaderAttrs {
    return {
      className: '',
      icon: '',
      title: '',
      description: '',
    };
  }

  /**
   * 通过 {@link AdminPage.buildSettingComponent} 提供的扩展定义的自定义设置组件列表。
   *
   * ItemList 的键表示在调用 {@link AdminPage.buildSettingComponent} 时要提供的 type 值。传递的其他属性作为参数提供给ItemList中添加的函数。
   *
   * ItemList 的优先级在这里没有影响。
   *
   * @example
   * ```tsx
   * extend(AdminPage.prototype, 'customSettingComponents', function (items) {
   *   // 你可以通过 `this` 访问AdminPage实例来访问其 `settings` 属性。
   *
   *   // 建议在键名前加上你的扩展ID以避免冲突。
   *   items.add('my-ext.setting-component', (attrs) => {
   *     return (
   *       <div className={attrs.className}>
   *         <label>{attrs.label}</label>
   *         {attrs.help && <p className="helpText">{attrs.help}</p>}
   *
   *         我的设置组件！
   *       </div>
   *     );
   *   })
   * })
   * ```
   */
  customSettingComponents(): ItemList<(attributes: CommonSettingsItemOptions) => Mithril.Children> {
    const items = new ItemList<(attributes: CommonSettingsItemOptions) => Mithril.Children>();

    return items;
  }

  /**
   * `buildSettingComponent` 接受一个设置对象并将其转换为组件。
   * 根据输入的类型，你可以将类型设置为 'bool', 'select', 或任何标准的 <input> 类型。'extra' 对象中的任何值都将作为属性添加到组件中。
   *
   * 或者，你可以传递一个将在 ExtensionPage 上下文中执行的回调函数，以包含自定义JSX元素。
   *
   * @example
   *
   * {
   *    setting: 'acme.checkbox',
   *    label: app.translator.trans('acme.admin.setting_label'),
   *    type: 'bool',
   *    help: app.translator.trans('acme.admin.setting_help'),
   *    className: 'Setting-item'
   * }
   *
   * @example
   *
   * {
   *    setting: 'acme.select',
   *    label: app.translator.trans('acme.admin.setting_label'),
   *    type: 'select',
   *    options: {
   *      'option1': '选项1的标签',
   *      'option2': '选项2的标签',
   *    },
   *    default: 'option1',
   * }
   *
   * @example
   *
   * () => {
   *   return <p>我的炫酷组件</p>;
   * }
   */
  buildSettingComponent(entry: ((this: this) => Mithril.Children) | SettingsComponentOptions): Mithril.Children {
    if (typeof entry === 'function') {
      return entry.call(this);
    }

    const customSettingComponents = this.customSettingComponents();

    const { setting, help, type, label, ...componentAttrs } = entry;

    const value = this.setting(setting)();

    const [inputId, helpTextId] = [generateElementId(), generateElementId()];

    let settingElement: Mithril.Children;

    // TypeScript 的特性
    // https://github.com/microsoft/TypeScript/issues/14520
    if ((BooleanSettingTypes as readonly string[]).includes(type)) {
      return (
        // TODO: 为切换帮助文本添加 aria-describedby 属性
        //? 需要对 Checkbox 组件进行修改，以允许直接为元素提供属性。
        <div className="Form-group">
          <Switch state={!!value && value !== '0'} onchange={this.settings[setting]} {...componentAttrs}>
            {label}
          </Switch>
          <div className="helpText">{help}</div>
        </div>
      );
    } else if ((SelectSettingTypes as readonly string[]).includes(type)) {
      const { default: defaultValue, options, ...otherAttrs } = componentAttrs;

      settingElement = (
        <Select
          id={inputId}
          aria-describedby={helpTextId}
          value={value || defaultValue}
          options={options}
          onchange={this.settings[setting]}
          {...otherAttrs}
        />
      );
    } else if (customSettingComponents.has(type)) {
      return customSettingComponents.get(type)({ setting, help, label, ...componentAttrs });
    } else {
      componentAttrs.className = classList('FormControl', componentAttrs.className);

      if ((TextareaSettingTypes as readonly string[]).includes(type)) {
        settingElement = <textarea id={inputId} aria-describedby={helpTextId} bidi={this.setting(setting)} {...componentAttrs} />;
      } else {
        let Tag: VnodeElementTag = 'input';

        if (type === ColorPreviewSettingType) {
          Tag = ColorPreviewInput;
        } else {
          componentAttrs.type = type;
        }

        settingElement = <Tag id={inputId} aria-describedby={helpTextId} bidi={this.setting(setting)} {...componentAttrs} />;
      }
    }

    return (
      <div className="Form-group">
        {label && <label for={inputId}>{label}</label>}
        <div id={helpTextId} className="helpText">
          {help}
        </div>
        {settingElement}
      </div>
    );
  }

  /**
   * 当 `saveSettings` 成功完成时调用。
   */
  onsaved(): void {
    this.loading = false;

    app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.settings.saved_message'));
  }

  /**
   * 返回一个从 `app` 全局变量中获取设置的函数。
   */
  setting(key: string, fallback: string = ''): Stream<string> {
    this.settings[key] = this.settings[key] || Stream<string>(app.data.settings[key] || fallback);

    return this.settings[key];
  }

  /**
   * 返回一个包含已修改但尚未保存的设置键和值的映射。
   */
  dirty(): Record<string, string> {
    const dirty: Record<string, string> = {};

    Object.keys(this.settings).forEach((key) => {
      const value = this.settings[key]();

      if (value !== app.data.settings[key]) {
        dirty[key] = value;
      }
    });

    return dirty;
  }

  /**
   * 返回已修改设置的数量。
   */
  isChanged(): number {
    return Object.keys(this.dirty()).length;
  }

  /**
   * 将已修改的设置保存到数据库中。
   */
  saveSettings(e: SaveSubmitEvent) {
    e.preventDefault();

    app.alerts.clear();

    this.loading = true;

    return saveSettings(this.dirty()).then(this.onsaved.bind(this));
  }
}
