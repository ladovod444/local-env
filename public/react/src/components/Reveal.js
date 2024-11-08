import React from 'react';
import {connect} from 'react-redux';
import {CommandPreviewActions, PopupActions} from '../actions/Actions'

class Reveal extends React.Component {
  static defaultProps = {
    title: 'No title'
  };

  handleCloseClick = () => {
    this.props.dispatch({type: PopupActions.hide});
    this.props.dispatch({type: CommandPreviewActions.preBuildCommandCancel});
  };

  render() {
    if (!this.props.isVisible) {
      return null;
    }

    let style = {
      display: 'block',
    };

    return <div className="reveal-wrapper">
      <div className="reveal-overlay" style={style}>
        <div className="small reveal" style={style}>
          <div>
            <h1>{this.props.title}</h1>
            <button className="close-button" type="button" onClick={this.handleCloseClick}>
              close
            </button>
            { this.props.revealContent }
          </div>
        </div>
      </div>
    </div>
  }
}

export default connect((state) => {
  return {
    isVisible: state.revealReducer.isVisible,
    title: state.revealReducer.title,
    revealContent: state.revealReducer.revealContent,
  };
})(Reveal);