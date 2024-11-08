import React from 'react';
import {connect} from 'react-redux';
import {CommandPreviewActions} from "../actions/Actions";
import axios from "axios";
import * as _ from 'lodash';
import { Form } from 'react-form';

class CommandPreview extends React.Component {
  handleClick = ( values, event, formApi) => {
    // Show pre-loader.
    this.props.dispatch({type: CommandPreviewActions.preBuildCommand});

    axios.post('/api/v1/command-build/' + this.props.command, values)
        .then((response) => {
          let data = response.data;
          this.props.dispatch({
            type: CommandPreviewActions.preBuildCommandSuccess,
            command: data.command,
          });
        })
        .catch((response) => {
          this.props.dispatch({
            type: CommandPreviewActions.preBuildCommandFail,
            errorMessage: response.message,
          });
        });
  };

  render() {
    const st = {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center'
    };
    let completeCommand = null;

    if (this.props.completeCommand) {
      completeCommand = <div className="complete-command">
        <strong>Copy & paste following command:</strong> <br/><br/>
        <code>{this.props.completeCommand}</code>
      </div>
    }

    let errorMessage = null;
    if (this.props.errorMessage) {
      errorMessage = <p>{this.props.errorMessage}</p>
    }

    return <div className="command-preview">
      <Form onSubmit={this.handleClick}>
        {formApi => <form onSubmit={formApi.submitForm} id="site-command-form">
          <div className="preview-form-wrapper">{this.props.form}</div>

          <button className="expanded small button" type="submit">
            <div style={st}>
              <div>Build command <i className="fas fa-angle-double-right"> </i></div>
              {this.props.isPreLoaderVisible && <div><i className="fas fa-spinner fa-spin"> </i></div>}
            </div>
          </button>
        </form>}
      </Form>

      {completeCommand}

      {errorMessage}
    </div>
  }
}

export default connect((state) => {
  return _.merge({}, state.CommandPreviewReducer);
})(CommandPreview);