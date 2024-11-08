import React from 'react';
import axios from 'axios';
import SelfUpdate from "../components/app/SelfUpdate";

class AppStatus extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      rows: [],
    }
  }

  componentDidMount() {
    axios.get('/api/v1/app-status')
        .then((response) => {
          let rows = [];
          for (var property in response.data) {
            if (response.data.hasOwnProperty(property)) {
              rows.push({
                name: response.data[property].key,
                value: response.data[property].value,
              })
            }
          }
          this.setState({
            rows: rows,
          })
        });
  }

  render() {
    return <div className="application-status-page">
      <header className="App-header">
        <h1>Application status</h1>
      </header>
      <table>
        <tbody>
        {this.state.rows.map((item, index) => {
          return <tr key={index}>
            <td>{item.name}</td>
            <td>{item.value}</td>
          </tr>
        })}
        </tbody>
      </table>

        <SelfUpdate/>
    </div>
  }
}

export default AppStatus;