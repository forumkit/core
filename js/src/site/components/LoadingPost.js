import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';

/**
 * `LoadingPost` 组件显示一个看起来像帖子的占位符，表示帖子正在加载中。
 */
export default class LoadingPost extends Component {
  view() {
    return (
      <div className="Post CommentPost LoadingPost">
        <header className="Post-header">
          {avatar(null, { className: 'PostUser-avatar' })}
          <div className="fakeText" />
        </header>

        <div className="Post-body">
          <div className="fakeText" />
          <div className="fakeText" />
          <div className="fakeText" />
        </div>
      </div>
    );
  }
}
