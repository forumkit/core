import app from '../../common/app';

import Component from '../Component';
import ItemList from '../utils/ItemList';
import listItems from '../helpers/listItems';
import Button from './Button';

import BasicEditorDriver from '../utils/BasicEditorDriver';
import Tooltip from './Tooltip';

/**
 * `TextEditor` 组件显示一个带有控件的文本区域，包括一个提交按钮。
 *
 * ### 属性（Attrs）
 *
 * - `composer`
 * - `submitLabel`
 * - `value`
 * - `placeholder`
 * - `disabled`
 * - `preview`
 */
export default class TextEditor extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * 编辑器的值。
     *
     * @type {String}
     */
    this.value = this.attrs.value || '';

    /**
     * 编辑器是否被禁用。
     */
    this.disabled = !!this.attrs.disabled;
  }

  view() {
    return (
      <div className="TextEditor">
        <div className="TextEditor-editorContainer"></div>

        <ul className="TextEditor-controls Composer-footer">
          {listItems(this.controlItems().toArray())}
          <li className="TextEditor-toolbar">{this.toolbarItems().toArray()}</li>
        </ul>
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.attrs.composer.editor = this.buildEditor(this.$('.TextEditor-editorContainer')[0]);
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    const newDisabled = !!this.attrs.disabled;

    if (this.disabled !== newDisabled) {
      this.disabled = newDisabled;
      this.attrs.composer.editor.disabled(newDisabled);
    }
  }

  buildEditorParams() {
    return {
      classNames: ['FormControl', 'Composer-flexible', 'TextEditor-editor'],
      disabled: this.disabled,
      placeholder: this.attrs.placeholder || '',
      value: this.value,
      oninput: this.oninput.bind(this),
      inputListeners: [],
      onsubmit: () => {
        this.onsubmit();
        m.redraw();
      },
    };
  }

  buildEditor(dom) {
    return new BasicEditorDriver(dom, this.buildEditorParams());
  }

  /**
   * 构建文本编辑器控件的项目列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  controlItems() {
    const items = new ItemList();

    items.add(
      'submit',
      <Button icon="fas fa-paper-plane" className="Button Button--primary" itemClassName="App-primaryControl" onclick={this.onsubmit.bind(this)}>
        {this.attrs.submitLabel}
      </Button>
    );

    if (this.attrs.preview) {
      items.add(
        'preview',
        <Tooltip text={app.translator.trans('core.site.composer.preview_tooltip')}>
          <Button icon="far fa-eye" className="Button Button--icon" onclick={this.attrs.preview} />
        </Tooltip>
      );
    }

    return items;
  }

  /**
   * 构建工具栏控件的项目列表。
   *
   * @return {ItemList<import('mithril').Children>}
   */
  toolbarItems() {
    return new ItemList();
  }

  /**
   * 处理文本区域的输入事件。
   *
   * @param {string} value
   */
  oninput(value) {
    this.value = value;

    this.attrs.onchange(this.value);
  }

  /**
   * 处理正在单击的提交按钮。
   */
  onsubmit() {
    this.attrs.onsubmit(this.value);
  }
}
