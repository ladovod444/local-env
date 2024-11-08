import React from 'react';
import './App.css';
import routes from './routes'
import {BrowserRouter as Router, Route, NavLink} from "react-router-dom";
import {Provider} from "react-redux";
import {combineReducers, createStore} from "redux";
import Reveal from "./components/Reveal";
import revealReducer from "./reducers/reveal";
import CommandPreviewReducer from "./reducers/CommandPreview";

const store = createStore(combineReducers({
  revealReducer,
  CommandPreviewReducer
}));

const App = () => (
    <div className="App">
      <Provider store={store}>
        <Router>
          <div className="off-canvas-wrapper">
            <div className="off-canvas-wrapper-inner" data-off-canvas-wrapper>
              <div className="off-canvas position-left reveal-for-large"
                   id="my-info" data-off-canvas
                   data-position="left">
                <div className="row column">
                  <div className="text-center logo-w">
                    <i className="fas fa-cogs"> </i>
                  </div>
                  <ul className="menu vertical">
                    {routes.map((route, index) => {
                      if(route.external){
                        return <div key={index}>{route.title}</div>
                      }else{
                        return <NavLink key={index} to={route.path} activeClassName="active" exact={route.exact}>
                          {route.title} <i className="fas fa-angle-right"> </i>
                        </NavLink>
                      }
                    })}
                  </ul>
                </div>
              </div>

              <div className="off-canvas-content" data-off-canvas-content>
                <div className="title-bar hide-for-large">
                  <div className="title-bar-left">
                    <button className="menu-icon" type="button"
                            data-open="my-info">
                    </button>
                  </div>
                </div>

                <div className="row column">
                  <div id="page-content">
                    {routes.map((route, index) => (
                        <Route key={index} path={route.path} exact={route.exact} component={route.main}/>
                    ))}
                  </div>
                  <Reveal/>
                </div>
              </div>
            </div>
          </div>
        </Router>
      </Provider>
    </div>
);

export default App;
