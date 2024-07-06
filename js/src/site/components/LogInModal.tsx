import app from '../../site/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import ForgotPasswordModal from './ForgotPasswordModal';
import SignUpModal from './SignUpModal';
import Button from '../../common/components/Button';
import LogInButtons from './LogInButtons';
import extractText from '../../common/utils/extractText';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
import type { LoginParams } from '../../common/Session';

export interface ILoginModalAttrs extends IInternalModalAttrs {
  identification?: string;
  password?: string;
  remember?: boolean;
}

export default class LogInModal<CustomAttrs extends ILoginModalAttrs = ILoginModalAttrs> extends Modal<CustomAttrs> {
  /**
   * 识别输入的值。
   */
  identification!: Stream<string>;
  /**
   * 密码输入的值。
   */
  password!: Stream<string>;
  /**
   * “记住我”输入的值。
   */
  remember!: Stream<boolean>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.identification = Stream(this.attrs.identification || '');
    this.password = Stream(this.attrs.password || '');
    this.remember = Stream(!!this.attrs.remember);
  }

  className() {
    return 'LogInModal Modal--small';
  }

  title() {
    return app.translator.trans('core.site.log_in.title');
  }

  content() {
    return [<div className="Modal-body">{this.body()}</div>, <div className="Modal-footer">{this.footer()}</div>];
  }

  body() {
    return [<LogInButtons />, <div className="Form Form--centered">{this.fields().toArray()}</div>];
  }

  fields() {
    const items = new ItemList();

    const identificationLabel = extractText(app.translator.trans('core.site.log_in.username_or_email_placeholder'));
    const passwordLabel = extractText(app.translator.trans('core.site.log_in.password_placeholder'));

    items.add(
      'identification',
      <div className="Form-group">
        <input
          className="FormControl"
          name="identification"
          type="text"
          placeholder={identificationLabel}
          aria-label={identificationLabel}
          bidi={this.identification}
          disabled={this.loading}
        />
      </div>,
      30
    );

    items.add(
      'password',
      <div className="Form-group">
        <input
          className="FormControl"
          name="password"
          type="password"
          autocomplete="current-password"
          placeholder={passwordLabel}
          aria-label={passwordLabel}
          bidi={this.password}
          disabled={this.loading}
        />
      </div>,
      20
    );

    items.add(
      'remember',
      <div className="Form-group">
        <div>
          <label className="checkbox">
            <input type="checkbox" bidi={this.remember} disabled={this.loading} />
            {app.translator.trans('core.site.log_in.remember_me_label')}
          </label>
        </div>
      </div>,
      10
    );

    items.add(
      'submit',
      <div className="Form-group">
        <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
          {app.translator.trans('core.site.log_in.submit_button')}
        </Button>
      </div>,
      -10
    );

    return items;
  }

  footer() {
    return (
      <>
        <p className="LogInModal-forgotPassword">
          <a onclick={this.forgotPassword.bind(this)}>{app.translator.trans('core.site.log_in.forgot_password_link')}</a>
        </p>
        {app.site.attribute<boolean>('allowSignUp') && (
          <p className="LogInModal-signUp">{app.translator.trans('core.site.log_in.sign_up_text', { a: <a onclick={this.signUp.bind(this)} /> })}</p>
        )}
      </>
    );
  }

  /**
   * 打开“忘记密码”模态框，如果用户已输入电子邮件，则预先填写该电子邮件。
   */
  forgotPassword() {
    const email = this.identification();
    const attrs = email.includes('@') ? { email } : undefined;

    app.modal.show(ForgotPasswordModal, attrs);
  }

  /**
   * 打开“注册”模态框，如果用户已输入电子邮件/用户名/密码，则预先填写这些信息。
   */
  signUp() {
    const identification = this.identification();

    const attrs = {
      [identification.includes('@') ? 'email' : 'username']: identification,
    };

    app.modal.show(SignUpModal, attrs);
  }

  onready() {
    this.$('[name=' + (this.identification() ? 'password' : 'identification') + ']').trigger('select');
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    app.session.login(this.loginParams(), { errorHandler: this.onerror.bind(this) }).then(() => window.location.reload(), this.loaded.bind(this));
  }

  loginParams(): LoginParams {
    const data = {
      identification: this.identification(),
      password: this.password(),
      remember: this.remember(),
    };

    return data;
  }

  onerror(error: RequestError) {
    if (error.status === 401 && error.alert) {
      error.alert.content = app.translator.trans('core.site.log_in.invalid_login_message');
      this.password('');
    }

    super.onerror(error);
  }
}
