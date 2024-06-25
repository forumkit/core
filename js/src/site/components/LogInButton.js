import app from '../../site/app';
import Button from '../../common/components/Button';
import classList from '../../common/utils/classList';

/**
 * `LogInButton` 组件显示一个社交登录按钮，该按钮将打开一个包含指定路径的弹出窗口。
 *
 * ### 属性（Attrs）
 *
 * - `path`
 */
export default class LogInButton extends Button {
  static initAttrs(attrs) {
    attrs.className = classList(attrs.className, 'LogInButton');

    attrs.onclick = function () {
      const width = 580;
      const height = 400;
      const $window = $(window);

      window.open(
        app.site.attribute('baseUrl') + attrs.path,
        'logInPopup',
        `width=${width},` +
          `height=${height},` +
          `top=${$window.height() / 2 - height / 2},` +
          `left=${$window.width() / 2 - width / 2},` +
          'status=no,scrollbars=yes,resizable=no'
      );
    };

    super.initAttrs(attrs);
  }
}
