/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

Mautic.testFullContactApi = function (btn) {
    mQuery(btn).prop('disabled', true);
    var apikey = mQuery('#integration_details_apiKeys_apikey').val();
    var d = new Date();
    var month = d.getMonth() + 1;
    var period = d.getFullYear() + '-' + ((month < 10) ? '0' + month : month);
    var months = new Array();
    months[0] = "January";
    months[1] = "February";
    months[2] = "March";
    months[3] = "April";
    months[4] = "May";
    months[5] = "June";
    months[6] = "July";
    months[7] = "August";
    months[8] = "September";
    months[9] = "October";
    months[10] = "November";
    months[11] = "December";
    var dateString = months[month - 1] + ' ' + d.getFullYear();
    var EOL = String.fromCharCode(13);
    mQuery.get('https://api.fullcontact.com/v2/stats.json?apiKey=' + apikey + '&period=' + period, function (stats) {
        var person = null;
        var company = null;
        var free = null;
        mQuery.each(stats.metrics, function (i, m) {
            if ('200' === m.metricId) {
                person = m;
            } else if ('company_200' === m.metricId) {
                company = m;
            } else if ('200_free' === m.metricId) {
                free = m;
            }
        });
        var result = 'Plan Details: ' + stats.plan + EOL + EOL +
            'Quick Usage Stats for ' + dateString + ':' + EOL;

        if (person) {
            result += ' - Person API: ' + person.usage + ' matches used from ' + person.planLevel + ' (' + person.remaining + ' remaining)' + EOL;
        }

        if (company) {
            result += ' - Company API: ' + company.usage + ' matches used from ' + company.planLevel + ' (' + company.remaining + ' remaining)' + EOL;
        }

        if (free) {
            result += ' - Name/Location/Stats: ' + free.usage + ' matches used from ' + free.planLevel + ' (' + free.remaining + ' remaining)' + EOL;
        }

        mQuery('#integration_details_apiKeys_stats').val(result);
    }).fail(function(error) {
        mQuery('#integration_details_apiKeys_stats').val((error.responseJSON && error.responseJSON.message)?error.responseJSON.message:'Error: ' + JSON.stringify(error));
    });
    mQuery(btn).prop('disabled', false);
};
