import React from "react";
import {Text} from 'react-form';

export default class ChangeCredsForm extends React.Component {
  render() {
    return <div>
      <label htmlFor="login">Login:</label><span className="required"/>
      <Text field="login" id="login" type="text" required/>

      <label htmlFor="password">Password</label><span className="required"/>
      <Text field="password" id="password" type="text" required/>
    </div>
  }
}
