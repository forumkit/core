import app from '../../site/app';
import UserPage, { IUserPageAttrs } from './UserPage';
import ItemList from '../../common/utils/ItemList';
import FieldSet from '../../common/components/FieldSet';
import listItems from '../../common/helpers/listItems';
import extractText from '../../common/utils/extractText';
import AccessTokensList from './AccessTokensList';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import NewAccessTokenModal from './NewAccessTokenModal';
import { camelCaseToSnakeCase } from '../../common/utils/string';
import type AccessToken from '../../common/models/AccessToken';
import type Mithril from 'mithril';
import Tooltip from '../../common/components/Tooltip';
import UserSecurityPageState from '../states/UserSecurityPageState';

/**
 * `UserSecurityPage` 组件在用户个人资料的上下文中显示用户的安全控制面板。
 */
export default class UserSecurityPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs, UserSecurityPageState> {
  state = new UserSecurityPageState();

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const routeUsername = m.route.param('username');

    if (routeUsername !== app.session.user?.slug() && !app.site.attribute<boolean>('canModerateAccessTokens')) {
      m.route.set('/');
    }

    this.loadUser(routeUsername);

    app.setTitle(extractText(app.translator.trans('core.site.security.title')));

    this.loadTokens();
  }

  content() {
    return (
      <div className="UserSecurityPage">
        <ul>{listItems(this.settingsItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * 为用户的设置控件构建项目列表。
   */
  settingsItems() {
    const items = new ItemList<Mithril.Children>();

    if (
      app.site.attribute('canCreateAccessToken') ||
      app.site.attribute('canModerateAccessTokens') ||
      (this.state.hasLoadedTokens() && this.state.getDeveloperTokens()?.length)
    ) {
      items.add(
        'developerTokens',
        <FieldSet className="UserSecurityPage-developerTokens" label={app.translator.trans(`core.site.security.developer_tokens_heading`)}>
          {this.developerTokensItems().toArray()}
        </FieldSet>
      );
    } else if (!this.state.hasLoadedTokens()) {
      items.add('developerTokens', <LoadingIndicator />);
    }

    items.add(
      'sessions',
      <FieldSet className="UserSecurityPage-sessions" label={app.translator.trans(`core.site.security.sessions_heading`)}>
        {this.sessionsItems().toArray()}
      </FieldSet>
    );

    if (this.user!.id() === app.session.user!.id()) {
      items.add(
        'globalLogout',
        <FieldSet className="UserSecurityPage-globalLogout" label={app.translator.trans('core.site.security.global_logout.heading')}>
          <span className="helpText">{app.translator.trans('core.site.security.global_logout.help_text')}</span>
          <Button
            className="Button"
            icon="fas fa-sign-out-alt"
            onclick={this.globalLogout.bind(this)}
            loading={this.state.loadingGlobalLogout}
            disabled={this.state.loadingTerminateSessions}
          >
            {app.translator.trans('core.site.security.global_logout.log_out_button')}
          </Button>
        </FieldSet>
      );
    }

    return items;
  }

  /**
   * 为用户的访问 accessToken 设置构建项目列表。
   */
  developerTokensItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'accessTokenList',
      !this.state.hasLoadedTokens() ? (
        <LoadingIndicator />
      ) : (
        <AccessTokensList
          type="developer_token"
          ondelete={(token: AccessToken) => {
            this.state.removeToken(token);
            m.redraw();
          }}
          tokens={this.state.getDeveloperTokens()}
          icon="fas fa-key"
          hideTokens={false}
        />
      )
    );

    if (this.user!.id() === app.session.user!.id()) {
      items.add(
        'newAccessToken',
        <Button
          className="Button"
          disabled={!app.site.attribute<boolean>('canCreateAccessToken')}
          onclick={() =>
            app.modal.show(NewAccessTokenModal, {
              onsuccess: (token: AccessToken) => {
                this.state.pushToken(token);
                m.redraw();
              },
            })
          }
        >
          {app.translator.trans('core.site.security.new_access_token_button')}
        </Button>
      );
    }

    return items;
  }

  /**
   * 为用户的访问 accessToken 设置构建项目列表。
   */
  sessionsItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'sessionsList',
      !this.state.hasLoadedTokens() ? (
        <LoadingIndicator />
      ) : (
        <AccessTokensList
          type="session"
          ondelete={(token: AccessToken) => {
            this.state.removeToken(token);
            m.redraw();
          }}
          tokens={this.state.getSessionTokens()}
          icon="fas fa-laptop"
          hideTokens={true}
        />
      )
    );

    if (this.user!.id() === app.session.user!.id()) {
      const isDisabled = !this.state.hasOtherActiveSessions();

      let terminateAllOthersButton = (
        <Button
          className="Button"
          onclick={this.terminateAllOtherSessions.bind(this)}
          loading={this.state.loadingTerminateSessions}
          disabled={this.state.loadingGlobalLogout || isDisabled}
        >
          {app.translator.trans('core.site.security.terminate_all_other_sessions')}
        </Button>
      );

      if (isDisabled) {
        terminateAllOthersButton = (
          <Tooltip text={app.translator.trans('core.site.security.cannot_terminate_current_session')}>
            <span tabindex="0">{terminateAllOthersButton}</span>
          </Tooltip>
        );
      }

      items.add('terminateAllOtherSessions', terminateAllOthersButton);
    }

    return items;
  }

  loadTokens() {
    return app.store
      .find<AccessToken[]>('access-tokens', {
        filter: { user: this.user!.id()! },
      })
      .then((tokens) => {
        this.state.setTokens(tokens);
        m.redraw();
      });
  }

  terminateAllOtherSessions() {
    if (!confirm(extractText(app.translator.trans('core.site.security.terminate_all_other_sessions_confirmation')))) return;

    this.state.loadingTerminateSessions = true;

    return app
      .request({
        method: 'DELETE',
        url: app.site.attribute('apiUrl') + '/sessions',
      })
      .then(() => {
        // 首先计算已终止的会话数量。
        const count = this.state.getOtherSessionTokens().length;

        this.state.removeOtherSessionTokens();

        app.alerts.show({ type: 'success' }, app.translator.trans('core.site.security.session_terminated', { count }));
      })
      .catch(() => {
        app.alerts.show({ type: 'error' }, app.translator.trans('core.site.security.session_termination_failed'));
      })
      .finally(() => {
        this.state.loadingTerminateSessions = false;
        m.redraw();
      });
  }

  globalLogout() {
    this.state.loadingGlobalLogout = true;

    return app
      .request({
        method: 'POST',
        url: app.site.attribute<string>('baseUrl') + '/global-logout',
      })
      .then(() => window.location.reload())
      .finally(() => {
        this.state.loadingGlobalLogout = false;
        m.redraw();
      });
  }
}
