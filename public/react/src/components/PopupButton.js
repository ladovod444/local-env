import React from 'react';
import {connect} from 'react-redux';
import {PopupActions} from '../actions/Actions'
import ReactTooltip from 'react-tooltip'

class PopupButton extends React.Component {
  static defaultProps = {
    icon: "fas",
    title: 'No title',
    command: '',
    hasTooltip: false,
  };

  handleClick = (event) => {
    event.preventDefault();
    this.props.dispatch({
      type: PopupActions.show,
      title: this.props.title,
      command: this.props.command,
      payload: this.props.payload,
    });
  };

  render() {
    let content = <a href="" onClick={this.handleClick} title={this.props.title}>
      <i className={this.props.icon}> </i> <span>{this.props.title}</span>
    </a>;
      if(this.props.hasTooltip){
        content =
            <a href="" onClick={this.handleClick} data-tip={this.props.title}>
              <i className={this.props.icon}> </i>
              <ReactTooltip/>
            </a>
      }
    return <div>{content}</div>
  }
}

export default connect((state) => {
      return {
        isVisible: state.revealReducer.isVisible
      };
    }
)(PopupButton);