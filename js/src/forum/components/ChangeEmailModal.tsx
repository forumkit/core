import app from '../../forum/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
import ItemList from '../../common/utils/ItemList';

/**
 * `ChangeEmailModal` 组件展示一个模态对话框，允许用户更改他们的电子邮件地址。
 */
export default class ChangeEmailModal<CustomAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Modal<CustomAttrs> {
  /**
   * 电子邮件输入框的值。
   */
  email!: Stream<string>;

  /**
   * 密码输入框的值。
   */
  password!: Stream<string>;

  /**
   * 表示电子邮件是否已成功更改的布尔值。
   */
  success: boolean = false;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.email = Stream(app.session.user!.email() || '');
    this.password = Stream('');
  }

  className() {
    return 'ChangeEmailModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.change_email.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">{this.fields().toArray()}</div>
      </div>
    );
  }

  fields(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    if (this.success) {
      items.add(
        'help',
        <p className="helpText">
          {app.translator.trans('core.forum.change_email.confirmation_message', {
            email: <strong>{this.email()}</strong>,
          })}
        </p>
      );

      items.add(
        'dismiss',
        <div className="Form-group">
          <Button className="Button Button--primary Button--block" onclick={this.hide.bind(this)}>
            {app.translator.trans('core.forum.change_email.dismiss_button')}
          </Button>
        </div>
      );
    } else {
      items.add(
        'email',
        <div className="Form-group">
          <input
            type="email"
            name="email"
            className="FormControl"
            placeholder={app.session.user!.email()}
            bidi={this.email}
            disabled={this.loading}
          />
        </div>
      );

      items.add(
        'password',
        <div className="Form-group">
          <input
            type="password"
            name="password"
            className="FormControl"
            autocomplete="current-password"
            placeholder={app.translator.trans('core.forum.change_email.confirm_password_placeholder')}
            bidi={this.password}
            disabled={this.loading}
          />
        </div>
      );

      items.add(
        'submit',
        <div className="Form-group">
          <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
            {app.translator.trans('core.forum.change_email.submit_button')}
          </Button>
        </div>
      );
    }

    return items;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    // 如果用户实际上没有输入不同的电子邮件地址，我们不需要做任何事情。哇!
    if (this.email() === app.session.user!.email()) {
      this.hide();
      return;
    }

    this.loading = true;
    this.alertAttrs = null;

    app.session
      .user!.save(this.requestAttributes(), {
        errorHandler: this.onerror.bind(this),
        meta: { password: this.password() },
      })
      .then(() => {
        this.success = true;
      })
      .catch(() => {})
      .then(this.loaded.bind(this));
  }

  requestAttributes() {
    return { email: this.email() };
  }

  onerror(error: RequestError) {
    if (error.status === 401 && error.alert) {
      error.alert.content = app.translator.trans('core.forum.change_email.incorrect_password_message');
    }

    super.onerror(error);
  }
}
