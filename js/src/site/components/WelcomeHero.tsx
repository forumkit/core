import app from '../app';
import Component from '../../common/Component';
import Button from '../../common/components/Button';
import type Mithril from 'mithril';

export interface IWelcomeHeroAttrs {}

const LOCAL_STORAGE_KEY = 'welcomeHidden';

/**
 * `WelcomeHero` 组件用于显示一个欢迎用户进入网站的英雄（横幅）元素。
 */
export default class WelcomeHero extends Component<IWelcomeHeroAttrs> {
  /**
   * @deprecated 已弃用 请改为扩展 `isHidden` 方法。
   */
  hidden: boolean = false;

  oninit(vnode: Mithril.Vnode<IWelcomeHeroAttrs, this>) {
    super.oninit(vnode);
  }

  view(vnode: Mithril.Vnode<IWelcomeHeroAttrs, this>) {
    if (this.isHidden()) return null;

    const slideUp = () => {
      this.$().slideUp(this.hide.bind(this));
    };

    return (
      <header className="Hero WelcomeHero">
        <div className="container">
          <Button
            icon="fas fa-times"
            onclick={slideUp}
            className="Hero-close Button Button--icon Button--link"
            aria-label={app.translator.trans('core.site.welcome_hero.hide')}
          />

          <div className="containerNarrow">
            <h1 className="Hero-title">{app.site.attribute('welcomeTitle')}</h1>
            <div className="Hero-subtitle">{m.trust(app.site.attribute('welcomeMessage'))}</div>
          </div>
        </div>
      </header>
    );
  }

  /**
   * 隐藏欢迎横幅。
   */
  hide() {
    localStorage.setItem(LOCAL_STORAGE_KEY, 'true');
  }

  /**
   * 判断欢迎横幅是否应该被隐藏。
   *
   * @returns 返回一个布尔值，表示欢迎横幅是否被隐藏
   */
  isHidden(): boolean {
    if (!app.site.attribute<string>('welcomeTitle')?.trim()) return true;
    if (localStorage.getItem(LOCAL_STORAGE_KEY)) return true;
    if (this.hidden) return true;

    return false;
  }
}
