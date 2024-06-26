import Alert from '../../common/components/Alert';
import Button from '../../common/components/Button';
import icon from '../../common/helpers/icon';
import Component from '../../common/Component';

/**
 * 如果用户尚未确认其电子邮件地址，则显示一个警告。
 *
 * @param {import('../SiteApplication').default} app
 */
export default function alertEmailConfirmation(app) {
  const user = app.session.user;

  if (!user || user.isEmailConfirmed()) return;

  class ResendButton extends Component {
    oninit(vnode) {
      super.oninit(vnode);

      this.loading = false;
      this.sent = false;
    }

    view() {
      return (
        <Button className="Button Button--link" onclick={this.onclick.bind(this)} loading={this.loading} disabled={this.sent}>
          {this.sent
            ? [icon('fas fa-check'), ' ', app.translator.trans('core.site.user_email_confirmation.sent_message')]
            : app.translator.trans('core.site.user_email_confirmation.resend_button')}
        </Button>
      );
    }

    onclick() {
      this.loading = true;
      m.redraw();

      app
        .request({
          method: 'POST',
          url: app.site.attribute('apiUrl') + '/users/' + user.id() + '/send-confirmation',
        })
        .then(() => {
          this.loading = false;
          this.sent = true;
          m.redraw();
        })
        .catch(() => {
          this.loading = false;
          m.redraw();
        });
    }
  }

  class ContainedAlert extends Alert {
    view(vnode) {
      const vdom = super.view(vnode);
      return { ...vdom, children: [<div className="container">{vdom.children}</div>] };
    }
  }

  m.mount($('<div className="App-notices"/>').insertBefore('#content')[0], {
    view: () => (
      <ContainedAlert dismissible={false} controls={[<ResendButton />]} className="Alert--emailConfirmation">
        {app.translator.trans('core.site.user_email_confirmation.alert_message', { email: <strong>{user.email()}</strong> })}
      </ContainedAlert>
    ),
  });
}
