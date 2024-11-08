import React from "react";
import * as _ from 'lodash';
import {Form, Select} from 'react-form';
import axios from "axios/index";

class SelfUpdate extends React.Component {
  constructor(props) {
    super(props);

    this.state = _.merge({}, {
      currentTag: null,
      availableTags: []
    });
  }

  componentDidMount() {
    axios.get('/api/v1/app-status/current-tag')
        .then((response) => {
          this.setState({
            currentTag: response.data.tag,
          });
        });
    axios.get('/api/v1/app-status/available-tags')
        .then((response) => {
          let tags = [];
          _.forEach(response.data.tags, (item) => {
            tags.push(this.buildTagValue(item))
          });
          this.setState({
            availableTags: tags,
          });
        })
  }

  switchTag(values, event, formApi) {
    axios.post('/api/v1/app-status/switch-tag', {values})
        .then(response => {
          alert('Tag has been switched to ' + response.data.tag);
          window.location.reload();
        })
        .catch(response => {
        });
  };

  checkUpdates(event) {
    axios.get('/api/v1/app-status/check-updates')
        .then((response) => {
          alert(response.data.message);
        })
  }

  buildTagValue(option) {
    return _.merge({}, {
      label: option,
      value: option
    })
  }

  render() {
    return <div>
      <header className="App-header">
        <h3>Current tag: {this.state.currentTag}</h3>
      </header>
      <div className="switcher">
        <Form onSubmit={this.switchTag}>
          {formApi => {
            return <form onSubmit={formApi.submitForm} id="site-command-form">
              <div style={{display: 'flex'}}>
                <Select options={this.state.availableTags}
                        id="new_tag"
                        field="new_tag"
                        name="new_tag"
                        style={{flexGrow: 1, width: 'auto'}}/>
                <button className="small button" type="submit" disabled>
                  Switch tag
                </button>
                <button className="small button" type="button" onClick={this.checkUpdates} disabled>
                  Check for updates
                </button>
              </div>
            </form>
          }}
        </Form>
      </div>
    </div>
  }
}

export default SelfUpdate;