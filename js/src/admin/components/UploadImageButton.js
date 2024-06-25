import app from '../../admin/app';
import Button from '../../common/components/Button';
import classList from '../../common/utils/classList';

export default class UploadImageButton extends Button {
  loading = false;

  view(vnode) {
    this.attrs.loading = this.loading;
    this.attrs.className = classList(this.attrs.className, 'Button');

    if (app.data.settings[this.attrs.name + '_path']) {
      this.attrs.onclick = this.remove.bind(this);

      return (
        <div>
          <p>
            <img src={app.site.attribute(this.attrs.name + 'Url')} alt="" />
          </p>
          <p>{super.view({ ...vnode, children: app.translator.trans('core.admin.upload_image.remove_button') })}</p>
        </div>
      );
    } else {
      this.attrs.onclick = this.upload.bind(this);
    }

    return super.view({ ...vnode, children: app.translator.trans('core.admin.upload_image.upload_button') });
  }

  /**
   * 提示用户上传图片。
   */
  upload() {
    if (this.loading) return;

    const $input = $('<input type="file">');

    $input
      .appendTo('body')
      .hide()
      .trigger('click')
      .on('change', (e) => {
        const body = new FormData();
        body.append(this.attrs.name, $(e.target)[0].files[0]);

        this.loading = true;
        m.redraw();

        app
          .request({
            method: 'POST',
            url: this.resourceUrl(),
            serialize: (raw) => raw,
            body,
          })
          .then(this.success.bind(this), this.failure.bind(this));
      });
  }

  /**
   * 移除 LOGO
   */
  remove() {
    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'DELETE',
        url: this.resourceUrl(),
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  resourceUrl() {
    return app.site.attribute('apiUrl') + '/' + this.attrs.name;
  }

  /**
   * 上传/删除成功后，重新加载页面。
   *
   * @param {object} response
   * @protected
   */
  success(response) {
    window.location.reload();
  }

  /**
   * 如果上传/删除失败，停止加载。
   *
   * @param {object} response
   * @protected
   */
  failure(response) {
    this.loading = false;
    m.redraw();
  }
}
