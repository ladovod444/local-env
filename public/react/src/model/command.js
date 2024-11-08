const command = {
  platform: {
    download: 'app:platform:download',
    update: 'app:platform:update',
    listTags: 'app:platform:list-tags',
    changeCreds: 'app:platform:change-creds'
  },
  site: {
    info: 'app:site:info',
    install: 'app:site:install',
    downloadDatabase: 'app:site:download:database',
    downloadFiles: 'app:site:download:files',
    installWhiteSite: 'app:whitesite:install',
    databaseSync: 'app:site:database-sync',
    databaseClone: 'app:site:database-clone',
    siteCleanup: 'app:site:cleanup',
    loginLink: 'app:site:login-link',
    installNewClone: 'app:newclone:install',
  }
};

export default command;