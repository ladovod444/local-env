import React from 'react';
import PopupButton from "./PopupButton";
import axios from "axios/index";
import command from "../model/command";

class SitesList extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      sites: [],
      loading: ''
    }
  }

  componentDidMount() {
    axios.get('/api/v1/sites-list')
      .then((response) => {
        this.setState({
          sites: response.data,
        });
      })
  }

  handleClick = (sitelink, location, id) => {
    this.setState({ loading: sitelink });
    axios
      .post("/api/v1/drush-uli", {
        sitelink: sitelink,
        location: location,
        id: id
      })
      .then(response => {
        if (
          response.data["drush_uli_link"] !== undefined &&
          response.data["drush_uli_link"].length !== 0
        ) {
          let a = document.createElement('a');
          a.href = sitelink + response.data["drush_uli_link"];
          a.setAttribute('target', '_blank');
          a.click();
        } else {
          alert("Sorry, Drush can't find the website");
        }
        this.setState({ loading: '' });
      })
      .catch(() => {
        this.setState({ loading: '' });
        alert("Sorry, looks like the operation met error on backend");
      });
  };

  render() {
    return <div className="sites-list">
      <table>
        <thead>
        <tr>
          <td>Site name</td>
          <td>Version</td>
          <td>Links</td>
          <td>Location</td>
          <td>Drush uli</td>
          <td>Actions</td>
        </tr>
        </thead>
        <tbody>
        {this.state.sites.map((site, index) => {
          return <tr key={index}>
            <td>{site.name}</td>
            <td>{site.version}</td>
            <td>
              {site.links.map((link, linkIndex) => {
                return <div key={linkIndex}>
                  <a href={link} target="_blank">{link}</a>
                  <small style={{paddingLeft: '5px'}}>
                    <i className="fas fa-external-link-square-alt"> </i>
                  </small>
                </div>
              })}
            </td>
            <td>
              {site.location}
            </td>
            <td>
              {site.links.map((link, linkIndex) => {
                return <div key={linkIndex}>
                  { this.state.loading == link ? <div style={{marginLeft:'30px'}}><i className="fas fa-spinner fa-spin"> </i></div> :
                  <a href="#" onClick={() => { this.handleClick(link, site.location, site.id) }}><i class="fas fa-sign-in-alt"></i> Drush uli</a> }
                </div>
              })}
            </td>
            <td style={{display:'flex',justifyContent:'space-between', paddingRight: '40px', minWidth: '150px'}}>
              <PopupButton icon="fas fa-sync-alt" hasTooltip={true} title="Sync database" payload={{id:site.id}} command={command.site.databaseSync}/>
              <PopupButton icon="fas fa-clone" hasTooltip={true} title="Clone database" payload={{id:site.id}} command={command.site.databaseClone}/>
              <PopupButton icon="far fa-trash-alt" hasTooltip={true} title="Delete site & database" payload={{id:site.id}} command={command.site.siteCleanup}/>
            </td>
          </tr>
        })}
        </tbody>
      </table>
    </div>
  }
}

export default SitesList;