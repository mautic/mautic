/**
 * Column Preferences Manager for Mautic
 * Centralized configuration for table column visibility preferences
 */
(function() {
    'use strict';

    // Configuration for all supported tables
    const COLUMN_CONFIGS = {
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

    /**
     * Applies column visibility preferences
     * @param {Array} prefs - User preferences array
     * @param {string} tableSelector - CSS selector for the table
     * @param {string} columnPrefix - CSS class prefix for columns
     * @param {Array} allColumns - All available columns
     */
    function applyColumnPrefs(prefs, tableSelector, columnPrefix, allColumns) {
        if (!prefs || !tableSelector || !columnPrefix || !allColumns) {
            console.warn('Missing required parameters for applyColumnPrefs');
            return;
        }

        allColumns.forEach(col => {
            const showColumn = prefs.includes(col);
            const elements = document.querySelectorAll(
                `${tableSelector} th.${columnPrefix}${col}, ${tableSelector} td.${columnPrefix}${col}`
            );

            elements.forEach(el => {
                el.style.setProperty('display', showColumn ? '' : 'none', 'important');
            });
        });
    }

    /**
     * Applies preferences based on current route
     */
    function applyColumnPrefsForRoute() {
        const currentPath = window.location.pathname;
        const configEntry = Object.entries(COLUMN_CONFIGS).find(([path]) => currentPath.includes(path));

        if (!configEntry) return;

        const [_, tableConfig] = configEntry;
        const userPreferences = window[tableConfig.preferenceVar] || [];

        if (Array.isArray(userPreferences)) {
            applyColumnPrefs(
                userPreferences,
                tableConfig.tableSelector,
                tableConfig.columnPrefix,
                tableConfig.allColumns
            );
        } else {
            console.error(`Invalid preferences format for ${tableConfig.preferenceVar}`);
        }
    }

    // Public API
    window.MauticColumnPrefs = {
        applyColumnPrefs,
        applyColumnPrefsForRoute,
        getConfigs: () => COLUMN_CONFIGS
    };

    // Auto-initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Allow time for other scripts to set the preference variables
        setTimeout(applyColumnPrefsForRoute, 100);
    });

    document.addEventListener('turbolinks:load', applyColumnPrefsForRoute);
})();