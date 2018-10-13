## Changelog

3.1.3
- Fix possible combination with event name and event value may not return a result

3.1.2
- Add dimensions and metrics information to glossary
- Support new "Write" role

3.1.1
- Make sure pie and bar graphs show available columns

3.1.0
- Support [Roll-Up Reporting](https://plugins.matomo.org/RollUpReporting). Create custom reports across multiple sites.

3.0.6
- Prevent possible fatal error when opening manage screen for all websites
- New config setting `custom_reports_validate_report_content_all_websites` which, when enabled under the `[CustomReports]` section, allows the creation of Custom Reports on "All websites", even those that contain "Custom dimensions" or other entities which may not be present on all websites. This is useful when you have many (or all) websites with the exact same dimensions Ids and/or Goals Ids across all websites.


3.0.5
- Renamed Piwik to Matomo

3.0.4
- Prevent possible error when putting a custom report to another custom report page

3.0.3
- Prevent possible problems with custom dimensions in custom reports when also using roll-ups.

3.0.2
- Added German translation
- When generating report data and data needs to be truncated, make sure to sort the data by the first column of the report
- Make number of rows within a datatable configurable 
- Make sure aggregated reports are truncated if needed

3.0.1
- Make sure custom reports category can be always selected when creating a new custom report

3.0.0
- Custom Reports for Piwik 3
