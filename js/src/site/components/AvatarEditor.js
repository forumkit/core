import app from '../../site/app';
import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import icon from '../../common/helpers/icon';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';

/**
 * `AvatarEditor` 组件显示用户的头像，并附带一个下拉菜单，允许用户上传/删除头像。
 *
 * ### 属性 Attrs
 *
 * - `className` 类名
 * - `user` 用户
 */
export default class AvatarEditor extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * 是否正在进行头像上传。
     *
     * @type {Boolean}
     */
    this.loading = false;

    /**
     * 是否有图片被拖动到上传区域上方。
     *
     * @type {Boolean}
     */
    this.isDraggedOver = false;
  }

  view() {
    const user = this.attrs.user;

    return (
      <div className={classList(['AvatarEditor', 'Dropdown', this.attrs.className, this.loading && 'loading', this.isDraggedOver && 'dragover'])}>
        {avatar(user, { loading: 'eager' })}
        <a
          className={user.avatarUrl() ? 'Dropdown-toggle' : 'Dropdown-toggle AvatarEditor--noAvatar'}
          title={app.translator.trans('core.site.user.avatar_upload_tooltip')}
          data-toggle="dropdown"
          onclick={this.quickUpload.bind(this)}
          ondragover={this.enableDragover.bind(this)}
          ondragenter={this.enableDragover.bind(this)}
          ondragleave={this.disableDragover.bind(this)}
          ondragend={this.disableDragover.bind(this)}
          ondrop={this.dropUpload.bind(this)}
        >
          {this.loading ? (
            <LoadingIndicator display="unset" size="large" />
          ) : user.avatarUrl() ? (
            icon('fas fa-pencil-alt')
          ) : (
            icon('fas fa-plus-circle')
          )}
        </a>
        <ul className="Dropdown-menu Menu">{listItems(this.controlItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * 获取编辑头像下拉菜单中的项目。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  controlItems() {
    const items = new ItemList();

    items.add(
      'upload',
      <Button icon="fas fa-upload" onclick={this.openPicker.bind(this)}>
        {app.translator.trans('core.site.user.avatar_upload_button')}
      </Button>
    );

    items.add(
      'remove',
      <Button icon="fas fa-times" onclick={this.remove.bind(this)}>
        {app.translator.trans('core.site.user.avatar_remove_button')}
      </Button>
    );

    return items;
  }

  /**
   * 启用拖拽样式
   *
   * @param {DragEvent} e
   */
  enableDragover(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = true;
  }

  /**
   * 禁用拖拽样式
   *
   * @param {DragEvent} e
   */
  disableDragover(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = false;
  }

  /**
   * 当文件被拖放到放置区域时上传头像
   *
   * @param {DragEvent} e
   */
  dropUpload(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = false;
    this.upload(e.dataTransfer.files[0]);
  }

  /**
   * 如果用户没有头像，那么显示控件下拉菜单就没有意义了，
   * 因为只有一个选项是可用的：上传。因此，当点击头像编辑器的下拉菜单切换按钮时，
   * 我们会立即提示用户上传头像。
   *
   * @param {MouseEvent} e
   */
  quickUpload(e) {
    if (!this.attrs.user.avatarUrl()) {
      e.preventDefault();
      e.stopPropagation();
      this.openPicker();
    }
  }

  /**
   * 使用文件选择器上传头像
   */
  openPicker() {
    if (this.loading) return;

    // 创建一个隐藏的HTML输入元素并点击它以使用户可以选择头像文件。
    // 一旦用户选择了文件，我们就会通过API上传它。
    const $input = $('<input type="file" accept=".jpg, .jpeg, .png, .bmp, .gif">');

    $input
      .appendTo('body')
      .hide()
      .click()
      .on('input', (e) => {
        this.upload($(e.target)[0].files[0]);
      });
  }

  /**
   * 上传头像
   *
   * @param {File} file
   */
  upload(file) {
    if (this.loading) return;

    const user = this.attrs.user;
    const data = new FormData();
    data.append('avatar', file);

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: `${app.site.attribute('apiUrl')}/users/${user.id()}/avatar`,
        serialize: (raw) => raw,
        body: data,
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  /**
   * 删除用户的头像
   */
  remove() {
    const user = this.attrs.user;

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'DELETE',
        url: `${app.site.attribute('apiUrl')}/users/${user.id()}/avatar`,
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  /**
   * 上传/删除成功后，将更新的用户数据推送到存储中，
   * 并强制重新计算用户的头像颜色。
   *
   * @param {object} response
   * @protected
   */
  success(response) {
    app.store.pushPayload(response);
    delete this.attrs.user.avatarColor;

    this.loading = false;
    m.redraw();
  }

  /**
   * 如果头像上传/删除失败，停止加载。
   *
   * @param {object} response
   * @protected
   */
  failure(response) {
    this.loading = false;
    m.redraw();
  }
}
