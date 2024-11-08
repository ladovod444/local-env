import React from "react";
import {Text} from "react-form";

export default class CleanupEnvironmentForm extends React.Component {
  render() {
    return <div>
      <label htmlFor="id">Site ID</label><span className="required"/>
      <Text field="id" id="id" type="text" required defaultValue={this.props.id}/>
    </div>
  }
}