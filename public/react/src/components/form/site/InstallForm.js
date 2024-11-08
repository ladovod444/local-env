import React from "react";
import {Text} from 'react-form';

export default class InstallForm extends React.Component {
  render() {
    return <div>
      <label htmlFor="url">Site URL</label><span className="required"/>
      <Text field="url" id="url" type="url" required/>

      <label htmlFor="repository-url">Git repository URL</label><span className="required"/>
      <Text field="repository-url" id="repository-url" type="url" required/>

      <label htmlFor="branch">Git branch</label><span className="required"/>
      <Text field="branch" id="branch" type="text" required/>

      <label htmlFor="platform-version">Platform version</label>
      <Text field="platform-version" id="platform-version" type="text"/>
      <p className="help-text">Enter a valid tag / branch. If you left this field empty - default platform version will be used.</p>
    </div>
  }
}