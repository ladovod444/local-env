import React from "react";
import {Text} from "react-form";


export default class ListTagsForm extends React.Component {
  render() {
    return <div>
      <label htmlFor="length">length</label>
      <Text field="length" id="length" type="number" step="1" min="0"/>
    </div>
  }
}
