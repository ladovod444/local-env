import React from "react";
import {Text} from 'react-form';

export default class NewCloneInstallForm extends React.Component {
  render() {
    return <div>
      <label htmlFor="repository-url">Git repository URL</label><span className="required"/>
      <Text field="repository-url" id="repository-url" type="url" required/>

      <label htmlFor="branch">Git branch</label><span className="required"/>
      <Text field="branch" id="branch" type="text" required/>

      <label htmlFor="clone-name">Clone name</label><span className="required"/>
      <Text field="clone-name" id="clone-name" type="text" required/>

      <label htmlFor="master-name">Master name</label><span className="required"/>
      <Text field="master-name" id="master-name" type="text" required/>
    </div>
  }
}