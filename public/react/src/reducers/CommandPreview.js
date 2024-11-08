import * as _ from 'lodash';

import {CommandPreviewActions} from "../actions/Actions";

const CommandPreviewInitialState = {
  isPreLoaderVisible: false,
  errorMessage: '',
  completeCommand: '',
};

export default function CommandPreviewReducer(state = CommandPreviewInitialState, action) {
  switch (action.type) {
    case CommandPreviewActions.preBuildCommand:
      return _.merge({}, state, {
        isPreLoaderVisible: true,
      });
    case CommandPreviewActions.preBuildCommandSuccess:
      return _.merge({}, state, {
        isPreLoaderVisible: false,
        errorMessage: '',
        completeCommand: action.command,
      });
    case CommandPreviewActions.preBuildCommandFail:
      return _.merge({}, state, {
        isPreLoaderVisible: false,
        errorMessage: action.errorMessage,
        completeCommand: null,
      });
    case CommandPreviewActions.preBuildCommandCancel:
      return _.merge({}, state, {
        isPreLoaderVisible: false,
        errorMessage: '',
        completeCommand: null,
      });
    default:
      return state;
  }
}