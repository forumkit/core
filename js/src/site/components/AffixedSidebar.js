import Component from '../../common/Component';

/**
 * `AffixedSidebar` 组件使用 Bootstrap 的 "affix" 插件，在滚动时保持侧边栏导航在视口顶部。
 *
 * ### 子元素 Children
 *
 * 该组件必须包裹一个元素，该元素自身又包裹一个 <ul> 元素，该 <ul> 元素将被 "affixed".
 *
 * @see https://getbootstrap.com/docs/3.4/javascript/#affix
 */
export default class AffixedSidebar extends Component {
  view(vnode) {
    return vnode.children[0];
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // 注册 affix 插件，在每次窗口大小调整时（以及触发时）执行
    this.boundOnresize = this.onresize.bind(this);
    $(window).on('resize', this.boundOnresize).resize();
  }

  onremove(vnode) {
    super.onremove(vnode);

    $(window).off('resize', this.boundOnresize);
  }

  onresize() {
    const $sidebar = this.$();  // 侧边栏的 jQuery 对象
    const $header = $('#header'); // 页眉的 jQuery 对象
    const $footer = $('#footer'); // 页脚的 jQuery 对象
    const $affixElement = $sidebar.find('> ul'); // 需要 affix 的 ul 元素的 jQuery 对象

    $(window).off('.affix');
    $affixElement.removeClass('affix affix-top affix-bottom').removeData('bs.affix');

    // 如果侧边栏的高度超过视口高度（减去页眉高度），则不应用 affix（否则无法滚动查看其内容）。
    if ($sidebar.outerHeight(true) > $(window).height() - $header.outerHeight(true)) return;

    // 应用 affix 插件
    $affixElement.affix({
      offset: {
        // 当侧边栏的顶部距离视口顶部超过页眉高度和侧边栏上边距之和时，触发 affix
        top: () => $sidebar.offset().top - $header.outerHeight(true) - parseInt($sidebar.css('margin-top'), 10),
        // 当侧边栏的底部距离视口底部小于页脚高度时，触发 affix-bottom
        bottom: () => (this.bottom = $footer.outerHeight(true)),
      },
    });
  }
}
