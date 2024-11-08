import {PopupActions} from "../actions/Actions";
import * as _ from 'lodash';
import React from "react";
import CommandPreview from "../components/CommandPreview";
import ListTagsForm from "../components/form/platform/ListTagsForm";
import ChangeCredsForm from "../components/form/platform/ChangeCredsForm";
import command from "../model/command";
import InfoForm from "../components/form/site/InfoForm";
import BlankForm from "../components/form/BlankForm";
import InstallForm from "../components/form/site/InstallForm";
import DownloadDatabaseForm from "../components/form/site/DownloadDatabaseForm";
import DownloadFilesForm from "../components/form/site/DownloadFilesForm";
import WhiteSiteInstallForm from "../components/form/site/WhiteSiteInstallForm";
import CloneDatabaseForm from "../components/form/site/CloneDatabaseForm";
import CleanupEnvironmentForm from "../components/form/site/CleanupEnvironmentForm";
import SyncDatabaseForm from "../components/form/site/SyncDatabaseForm";
import NewCloneInstallForm from "../components/form/site/NewCloneInstallForm";

const revealInitialState = {
  isVisible: false,
  title: null,
  revealContent: null,
  payload: {},
};

export default function revealReducer(state = revealInitialState, action) {
  switch (action.type) {
    case PopupActions.show:
      return _.merge({}, state, {
        isVisible: true,
        title: action.title,
        revealContent: React.createElement(CommandPreview, {
          command: action.command,
          form: React.createElement(buildCommandForm(action.command), action.payload),
          payload: action.payload
        })
      });
    case PopupActions.hide:
      return _.merge({}, state, {
        isVisible: false,
        title: null,
        payload: {},
      });
    default:
      return state;
  }
}

/**
 * @param commandName
 * @returns {ListTagsForm}
 */
function buildCommandForm(commandName) {
  switch (commandName) {
    case command.platform.download:
    case command.platform.update:
      return BlankForm;
    case command.platform.listTags:
      return ListTagsForm;
    case command.platform.changeCreds:
      return ChangeCredsForm;
    case command.site.info:
      return InfoForm;
    case command.site.install:
      return InstallForm;
    case command.site.downloadDatabase:
      return DownloadDatabaseForm;
    case command.site.downloadFiles:
      return DownloadFilesForm;
    case command.site.installWhiteSite:
      return WhiteSiteInstallForm;
    case command.site.databaseClone:
      return CloneDatabaseForm;
    case command.site.siteCleanup:
      return CleanupEnvironmentForm;
    case command.site.databaseSync:
      return SyncDatabaseForm;
    case command.site.installNewClone:
        return NewCloneInstallForm;
    default:
      return BlankForm;
  }
}
