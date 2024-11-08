import React from "react";
import {Text} from "react-form";

export default class InfoForm extends React.Component {
  render() {
    return <div>
      <label htmlFor="url">Site URL</label><span className="required"/>
      <Text field="url" id="url" type="url" required/>
    </div>
  }
}