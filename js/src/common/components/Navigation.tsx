import app from '../../common/app';
import Component from '../Component';
import Button from './Button';
import LinkButton from './LinkButton';
import type Mithril from 'mithril';
import classList from '../utils/classList';

/**
 * `Navigation` 组件展示了一组导航按钮。通常，这只是一个返回按钮，用于回退应用的历史记录。如果用户在根页面上且没有历史记录可以回退，那么在某些情况下，它可能会显示一个用于切换应用抽屉的按钮。
 *
 * 如果应用有一个面板，它还会包含一个“固定”按钮，用于切换面板的固定状态。
 *
 * 该组件接受以下属性：
 *
 * - `className` 要在根元素上设置的类的名称。
 * - `drawer` 如果没有更多的历史记录可以回退，是否显示一个用于切换应用抽屉的按钮
 */
export default class Navigation extends Component {
  view() {
    const { history, pane } = app;

    return (
      <div
        className={classList('Navigation ButtonGroup', this.attrs.className)}
        onmouseenter={pane && pane.show.bind(pane)}
        onmouseleave={pane && pane.onmouseleave.bind(pane)}
      >
        {history?.canGoBack() ? [this.getBackButton(), this.getPaneButton()] : this.getDrawerButton()}
      </div>
    );
  }

  /**
   * 获取返回按钮
   */
  protected getBackButton(): Mithril.Children {
    const { history } = app;
    const previous = history?.getPrevious();

    return (
      <LinkButton
        className="Button Navigation-back Button--icon"
        href={history?.backUrl()}
        icon="fas fa-chevron-left"
        aria-label={previous?.title}
        onclick={(e: MouseEvent) => {
          if (e.shiftKey || e.ctrlKey || e.metaKey || e.which === 2) return;
          e.preventDefault();
          history?.back();
        }}
      />
    );
  }

  /**
   * 获取面板固定切换按钮
   */
  protected getPaneButton(): Mithril.Children {
    const { pane } = app;

    if (!pane || !pane.active) return null;

    return (
      <Button
        className={classList('Button Button--icon Navigation-pin', { active: pane.pinned })}
        onclick={pane.togglePinned.bind(pane)}
        icon="fas fa-thumbtack"
      />
    );
  }

  /**
   * 获取抽屉切换按钮
   */
  protected getDrawerButton(): Mithril.Children {
    if (!this.attrs.drawer) return null;

    const { drawer } = app;
    const user = app.session.user;

    return (
      <Button
        className={classList('Button Button--icon Navigation-drawer', { new: user?.newNotificationCount() })}
        onclick={(e: MouseEvent) => {
          e.stopPropagation();
          drawer.show();
        }}
        icon="fas fa-bars"
        aria-label={app.translator.trans('core.lib.nav.drawer_button')}
      />
    );
  }
}
