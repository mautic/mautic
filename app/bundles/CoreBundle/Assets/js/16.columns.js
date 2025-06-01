window.columnConfigs = {
  '/s/emails': {
    tableSelector: 'table.email-list',
    columnPrefix: 'col-email-',
    allColumns: ['name', 'category', 'stats', 'dateAdded', 'dateModified', 'createdByUser', 'id'],
    labels: {
      name: 'Name',
      category: 'Category',
      stats: 'Stats',
      dateAdded: 'Date Added',
      dateModified: 'Date Modified',
      createdByUser: 'Created By',
      id: 'ID'
    },
    preferenceKey: 'user_column_visibility_emails',
    preferenceVar: 'userColumnPrefs'
  },
  '/s/contacts': {
    tableSelector: 'table.lead-list',
    columnPrefix: 'col-lead-',
    allColumns: ['name', 'email', 'location', 'stage', 'points', 'last_active', 'id'],
    labels: {
        name: 'Name',
        email: 'Email',
        location: 'Location',
        stage: 'Stage',
        points: 'Points',
        last_active: 'Last Active',
        id: 'ID'
    },
    preferenceKey: 'user_column_visibility_contacts',
    preferenceVar: 'contactColPrefs'
  },
  '/s/companies': {
    tableSelector: 'table.company-list',
    columnPrefix: 'col-company-',
    allColumns: ['name', 'email', 'website', 'score', 'contacts', 'id'],
    labels: {
      name: 'Company name',
      email: 'Company email',
      website: 'Company website',
      score: 'Score',
      contacts: '# contacts',
      id: 'ID'
    },
    preferenceKey: 'user_column_visibility_company',
    preferenceVar: 'companyColPrefs'
  }
};


window.applyColumnPrefsForRoute = function () {
  const path = window.location.pathname;
  const configEntry = Object.entries(window.columnConfigs).find(([key]) => path.includes(key));

  if (configEntry && typeof window.applyColumnPrefs === 'function') {
    const [, tableConfig] = configEntry;
    const prefsVar = window[tableConfig.preferenceVar] || [];

    window.applyColumnPrefs(
      prefsVar,
      tableConfig.tableSelector,
      tableConfig.columnPrefix,
      tableConfig.allColumns
    );
  }
};
