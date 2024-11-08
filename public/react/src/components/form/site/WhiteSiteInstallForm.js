import React from "react";
import {Text} from "react-form";

export default class WhiteSiteInstallForm extends React.Component {
    render() {
        return <div>
          <label htmlFor="repository-url">Git repository URL</label><span className="required"/>
          <Text field="repository-url" id="repository-url" type="url" required/>

          <label htmlFor="branch">Git branch</label><span className="required"/>
          <Text field="branch" id="branch" type="text" required/>
        </div>
    }
}

