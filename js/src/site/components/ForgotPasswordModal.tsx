import app from '../../site/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import extractText from '../../common/utils/extractText';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
import ItemList from '../../common/utils/ItemList';

export interface IForgotPasswordModalAttrs extends IInternalModalAttrs {
  email?: string;
}

/**
 * `ForgotPasswordModal` 组件显示一个模态窗口，允许用户输入他们的电子邮件地址并请求重置密码的链接。
 */
export default class ForgotPasswordModal<CustomAttrs extends IForgotPasswordModalAttrs = IForgotPasswordModalAttrs> extends Modal<CustomAttrs> {
  /**
   * 电子邮件输入框的值。
   */
  email!: Stream<string>;

  success: boolean = false;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.email = Stream(this.attrs.email || '');
  }

  className() {
    return 'ForgotPasswordModal Modal--small';
  }

  title() {
    return app.translator.trans('core.site.forgot_password.title');
  }

  content() {
    if (this.success) {
      return (
        <div className="Modal-body">
          <div className="Form Form--centered">
            <p className="helpText">{app.translator.trans('core.site.forgot_password.email_sent_message')}</p>
            <div className="Form-group">
              <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
                {app.translator.trans('core.site.forgot_password.dismiss_button')}
              </Button>
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{app.translator.trans('core.site.forgot_password.text')}</p>
          {this.fields().toArray()}
        </div>
      </div>
    );
  }

  fields() {
    const items = new ItemList();

    const emailLabel = extractText(app.translator.trans('core.site.forgot_password.email_placeholder'));

    items.add(
      'email',
      <div className="Form-group">
        <input
          className="FormControl"
          name="email"
          type="email"
          placeholder={emailLabel}
          aria-label={emailLabel}
          bidi={this.email}
          disabled={this.loading}
        />
      </div>,
      50
    );

    items.add(
      'submit',
      <div className="Form-group">
        <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
          {app.translator.trans('core.site.forgot_password.submit_button')}
        </Button>
      </div>,
      -10
    );

    return items;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    app
      .request({
        method: 'POST',
        url: app.site.attribute('apiUrl') + '/forgot',
        body: this.requestParams(),
        errorHandler: this.onerror.bind(this),
      })
      .then(() => {
        this.success = true;
        this.alertAttrs = null;
      })
      .catch(() => {})
      .then(this.loaded.bind(this));
  }

  requestParams(): Record<string, unknown> {
    const data = {
      email: this.email(),
    };

    return data;
  }

  onerror(error: RequestError) {
    if (error.status === 404 && error.alert) {
      error.alert.content = app.translator.trans('core.site.forgot_password.not_found_message');
    }

    super.onerror(error);
  }
}
