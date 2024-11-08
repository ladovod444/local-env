import React from 'react';
import PopupButton from "../components/PopupButton";
import SitesList from "../components/SitesList";
import command from "../model/command";

const stylePanel = {
  marginBottom: '10px',
};

const Sites = () => (
    <div>
      <div className="panel" style={stylePanel}>
        <header className="App-header">
          <h1>Platform</h1>
        </header>
        <ul className="menu">
          <li>
            <PopupButton icon="fas fa-download" title="Download" command={command.platform.download}/>
          </li>
          <li>
            <PopupButton icon="fas fa-sync-alt" title="Update" command={command.platform.update}/>
          </li>
          <li>
            <PopupButton icon="fas fa-list-ul" title="List tags" command={command.platform.listTags}/>
          </li>
          <li>
            <PopupButton icon="fas fa-key" title="Change creds" command={command.platform.changeCreds}/>
          </li>
        </ul>
      </div>

      <div className="panel" style={stylePanel}>
        <header className="App-header">
          <h1>Site</h1>
        </header>
        <ul className="menu">
          <li><PopupButton icon="fas fa-globe" title="Install white-site" command={command.site.installWhiteSite}/></li>
          <li><PopupButton icon="fas fa-info" title="Get info" command={command.site.info}/></li>
          <li><PopupButton icon="fas fa-download" title="Install" command={command.site.install}/></li>
          <li><PopupButton icon="fas fa-database" title="Download database" command={command.site.downloadDatabase}/></li>
          <li><PopupButton icon="fas fa-copy" title="Download files" command={command.site.downloadFiles}/></li>
          <li><PopupButton icon="fas fa-plus-square" title="New clone" command={command.site.installNewClone}/></li>
        </ul>
      </div>

      <div className="panel" style={stylePanel}>
        <h3 style={{paddingLeft:'15px'}}>Available sites</h3>
        <SitesList/>
      </div>
    </div>
);

export default Sites;