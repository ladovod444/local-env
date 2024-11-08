import React from 'react';

const Databases = () => (
    <div>
      <header className="App-header">
        <h1>Databases</h1>
      </header>
      <iframe src="/adminer" title="Databases" style={{width:'100%', border:0, height: '90vh'}}> </iframe>
    </div>
);

export default Databases;