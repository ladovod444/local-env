import React from "react";
import Sites from "./controller/Sites";
import AppStatus from "./controller/AppStatus";

const routes = [
  {
    path: "/",
    title: "Sites",
    exact: true,
    main: () => Sites()
  },
  {
    path: "/app-status",
    title: "App status",
    main: () => <AppStatus/>
  },
  {
    path: "/db",
    external: true,
    title: <a  href="/adminer" target="_blank">Databases <i className="fas fa-angle-right"> </i></a>,
  },
  {
    path: "/phpinfo",
    external: true,
    title: <a  href="/phpinfo" target="_blank">PHP info <i className="fas fa-angle-right"> </i></a>,
  },
  {
    path: "/mailcatcher",
    external: true,
    title: <a  href={'//'+window.location.hostname + ':1080'} target="_blank">Mailcatcher <i className="fas fa-angle-right"> </i></a>,
  },
];

export default routes;