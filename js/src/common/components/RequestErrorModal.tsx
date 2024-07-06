import type RequestError from '../utils/RequestError';
import Modal, { IInternalModalAttrs } from './Modal';

export interface IRequestErrorModalAttrs extends IInternalModalAttrs {
  error: RequestError;
  formattedError: string[];
}

export default class RequestErrorModal<CustomAttrs extends IRequestErrorModalAttrs = IRequestErrorModalAttrs> extends Modal<CustomAttrs> {
  className() {
    return 'RequestErrorModal Modal--large';
  }

  title() {
    return !!this.attrs.error.xhr && `${this.attrs.error.xhr.status} ${this.attrs.error.xhr.statusText}`;
  }

  content() {
    const { error, formattedError } = this.attrs;

    let responseText;

    // 如果错误已经被格式化，则只需添加换行符；
    // 否则，尝试将其解析为 JSON 并使用缩进进行字符串化
    if (formattedError.length) {
      responseText = formattedError.join('\n\n');
    } else if (error.response) {
      responseText = JSON.stringify(error.response, null, 2);
    } else {
      responseText = error.responseText;
    }

    return (
      <div className="Modal-body">
        <pre>
          {this.attrs.error.options.method} {this.attrs.error.options.url}
          <br />
          <br />
          {responseText}
        </pre>
      </div>
    );
  }
}
