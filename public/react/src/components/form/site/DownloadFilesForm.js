import React from "react";
import {Text} from "react-form";

export default class DownloadFilesForm extends React.Component {
    render() {
        return <div>
          <label htmlFor="url">Site URL</label><span className="required"/>
          <Text field="url" id="url" type="url" required/>

          <label htmlFor="site">Site</label>
          <Text field="site" id="site" type="text" placeholder="jbaby-us_3"/>
        </div>
    }
}

