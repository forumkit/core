import Component from '../Component';

/**
 * `Separator` 组件定义了一个菜单分隔符项。
 */
class Separator extends Component {
  view() {
    return <li className="Dropdown-separator" />;
  }
}

Separator.isListItem = true;

export default Separator;
