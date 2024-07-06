import { RichMessageFormatter, mithrilRichHandler } from '@askvortsov/rich-icu-message-formatter';
import { pluralTypeHandler, selectTypeHandler } from '@ultraq/icu-message-formatter';
import username from './helpers/username';
import User from './models/User';
import extract from './utils/extract';

type Translations = Record<string, string>;
type TranslatorParameters = Record<string, unknown>;

export default class Translator {
  /**
   * 一个翻译键到其翻译值的映射。
   */
  translations: Translations = {};

  /**
   * 底层的ICU MessageFormatter工具。
   */
  protected formatter = new RichMessageFormatter(null, this.formatterTypeHandlers(), mithrilRichHandler);

  /**
   * 将格式化器的区域设置设置为提供的值。
   */
  setLocale(locale: string) {
    this.formatter.locale = locale;
  }

  /**
   * 返回格式化器的当前区域设置。
   */
  getLocale() {
    return this.formatter.locale;
  }

  /** 
   * 添加一组新的翻译键值对。
   */
  addTranslations(translations: Translations) {
    Object.assign(this.translations, translations);
  }

  /**
   * 一个可扩展的入口点，允许扩展程序为翻译注册类型处理器。
   */
  protected formatterTypeHandlers() {
    return {
      plural: pluralTypeHandler,
      select: selectTypeHandler,
    };
  }

  /**
   * 一个预处理参数的临时系统。
   * 不应由扩展程序使用。
   * TODO: 在v1.x版本中将添加一个扩展程序。
   *
   * @internal
   */
  protected preprocessParameters(parameters: TranslatorParameters) {
    // 如果我们已将用户模型作为输入参数之一，我们将提取用户名并用于翻译。在未来，
    // 这里应该有一个钩子来检查用户并更改翻译键。这将允许性别属性确定使用哪个翻译键。

    if ('user' in parameters) {
      const user = extract(parameters, 'user') as User;

      if (!parameters.username) parameters.username = username(user);
    }

    return parameters;
  }

  trans(id: string, parameters: TranslatorParameters = {}) {
    const translation = this.translations[id];

    if (translation) {
      parameters = this.preprocessParameters(parameters);
      return this.formatter.rich(translation, parameters);
    }

    return id;
  }
}
