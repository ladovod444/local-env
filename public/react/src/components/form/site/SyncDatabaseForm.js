import React from "react";
import {Text} from "react-form";

export default class SyncDatabaseForm extends React.Component {
  render() {
    return <div>
      <label htmlFor="source-url">Source site URL</label><span className="required"/>
      <Text field="source-url" id="source-url" type="url" required/>

      <label htmlFor="target">Site ID</label><span className="required"/>
      <Text field="target" id="target" type="text" required defaultValue={this.props.id}/>
    </div>
  }
}